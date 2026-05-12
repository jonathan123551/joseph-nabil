@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
{{--
    Premium gate scanner.

    The page is structured around three layers, all scoped under
    [data-scanner-root] so the rest of the app's CSS is unaffected:

      1. Camera frame (`#qr-reader`)         — html5-qrcode mounts the
         <video> here. Wrapped in a tall, full-bleed cinematic shell.
      2. Status pill                          — top-of-camera state
         feedback (Ready / Scanning / OK / Used / Invalid).
      3. Premium result sheet (`#scan-sheet`) — slides up from the bottom
         after every scan with the attendee + booking details. Auto-
         dismisses after a configurable cooldown so the operator can
         keep scanning without tapping anything.

    Backend contract (POST /admin/scanner/check) returns:
      { status: 'ok'|'used'|'error',
        name, phone, show_title, date, time,
        tickets_count, reference, sections[], seats[{label, section, ...}],
        scanned_at }

    Reliability improvements over the previous version:
      - Prefers the native BarcodeDetector API when supported (much
        faster than jsQR on iOS Safari + Android Chrome).
      - Larger qrbox computed from the live camera frame so tilted /
        partial QRs are still inside the decode region.
      - Continuous-focus camera constraints + advanced focusMode hint
        so autofocus keeps tracking when the operator moves closer.
      - Higher fps (24) with a per-code cooldown to avoid pinging the
        backend on every frame.
--}}

<section data-scanner-root class="prism-fade-up">

    {{-- HEADER --}}
    <div class="scanner-header">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                <span data-i18n="adm_scanner_pill">Gate Scanner</span>
            </span>
            <h1 class="prism-headline text-base">
                <span data-i18n-html="adm_scanner_title"
                      style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    🎫 Gate Scanner
                </span>
            </h1>
        </div>

        @auth
            <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="adm_back">رجوع</span>
            </a>
        @else
            <a href="{{ url('/') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="adm_back">رجوع</span>
            </a>
        @endauth
    </div>

    {{-- SCANNER CHROME --}}
    <div class="scanner-stage" data-scanner-stage>

        {{-- Camera mount. html5-qrcode injects its <video> here. --}}
        <div id="qr-reader" class="scanner-video"></div>

        {{-- Stage 2: live zoom indicator. Only shows when zoom > 1.0×
             via the is-visible class. Sits outside #qr-reader so it
             survives the innerHTML wipe Path A / Path B do at mount
             time. --}}
        <div id="scan-zoom-chip" class="scan-zoom-chip" aria-hidden="true">
            <span data-zoom-text>1.0×</span>
        </div>

        {{-- Reticle frame + corner brackets + scan line. Pointer-events:
             none so taps fall through to the camera. --}}
        <div class="scanner-overlay" aria-hidden="true">
            <div class="scanner-reticle">
                <span class="reticle-corner tl"></span>
                <span class="reticle-corner tr"></span>
                <span class="reticle-corner bl"></span>
                <span class="reticle-corner br"></span>
                <span class="reticle-line"></span>
            </div>
        </div>

        {{-- Live status pill. --}}
        <div id="status"
             class="scanner-status state-ready"
             data-i18n="adm_scanner_ready"
             role="status"
             aria-live="polite">
            جاهز للفحص
        </div>

        {{-- Loading state shown until the camera produces its first
             frame. Replaced as soon as html5-qrcode resolves. --}}
        <div id="scanner-loading" class="scanner-loading">
            <div class="prism-spinner" aria-hidden="true"></div>
            <div class="text-xs" data-i18n="adm_scanner_loading">
                جاري تشغيل الكاميرا…
            </div>
        </div>
    </div>

    {{-- CONTROLS --}}
    <div class="scanner-controls">
        <button id="flashBtn" class="prism-btn-ghost text-xs py-3"
                type="button"
                data-i18n-html="adm_scanner_flash">
            🔦 Flash
        </button>
        <button id="restartBtn" class="prism-btn-ghost text-xs py-3"
                type="button"
                data-i18n-html="adm_scanner_restart">
            🔄 Restart
        </button>
    </div>

</section>

{{-- PREMIUM SCAN-RESULT SHEET --}}
<div id="scan-sheet"
     class="scan-sheet"
     data-state="hidden"
     role="dialog"
     aria-modal="false"
     aria-live="polite"
     aria-labelledby="scan-sheet-title">

    <div class="scan-sheet-card" data-scan-card>

        {{-- Status badge — color reflects ok / used / error. --}}
        <div class="scan-sheet-badge" data-scan-badge>
            <span class="scan-sheet-badge-icon" data-scan-icon>✓</span>
            <span class="scan-sheet-badge-text" data-scan-badge-text>
                دخول مسموح
            </span>
        </div>

        {{-- Attendee — large, prominent. PR #70: per-ticket identity
             so this name is the holder of THIS specific QR, not the
             booking owner. --}}
        <div class="scan-sheet-name" id="scan-sheet-title" data-scan-name>—</div>
        <div class="scan-sheet-ref" data-scan-ref></div>

        {{-- BIG SEAT BADGE — readable across an event entrance.
             Hidden on tickets that don't have a specific seat (manual
             / "Other" venue bookings). --}}
        <div class="scan-seat-hero" data-scan-seat-hero hidden>
            <span class="scan-seat-hero-section" data-scan-seat-hero-section>—</span>
            <span class="scan-seat-hero-label"   data-scan-seat-hero-label>—</span>
        </div>

        {{-- Show / showtime --}}
        <div class="scan-sheet-row">
            <span class="scan-sheet-row-icon" aria-hidden="true">🎭</span>
            <span class="scan-sheet-row-text" data-scan-show>—</span>
        </div>
        <div class="scan-sheet-row">
            <span class="scan-sheet-row-icon" aria-hidden="true">🕒</span>
            <span class="scan-sheet-row-text" data-scan-when>—</span>
        </div>

        {{-- Already-scanned note --}}
        <div class="scan-sheet-used-note" data-scan-used-note hidden>
            <span aria-hidden="true">⚠️</span>
            <span data-i18n="adm_scanner_used_note">
                هذه التذكرة تم استخدامها سابقًا
            </span>
            <strong data-scan-used-time></strong>
        </div>

        {{-- Footer — operator dismisses manually now. The scanner
             stays paused while the sheet is open and resumes the
             instant the operator taps Done / outside / Esc. --}}
        <div class="scan-sheet-foot">
            <button type="button"
                    class="prism-btn-gold text-sm scan-sheet-done"
                    data-scan-dismiss
                    data-i18n="adm_scanner_done">
                تم — التالي
            </button>
            <span class="scan-sheet-hint" data-i18n="adm_scanner_dismiss_hint">
                اضغط للإغلاق ومتابعة المسح
            </span>
        </div>
    </div>
</div>

{{-- Detection engines, in priority order:

      1. The browser's NATIVE BarcodeDetector API
         (Android Chrome / Edge — uses platform VisionKit / ML Kit
          for hardware-accelerated decoding). Wired in PR #71.

      2. ZXing-js — same engine many professional event-entry
         scanners use, dramatically more tolerant of tilt /
         distance / partial framing / low-light than jsQR.
         Loaded as a global `ZXing` from the UMD bundle. Used as
         the iPhone-Safari path (and any other browser without
         a native BarcodeDetector).

      3. html5-qrcode (jsQR) — last-resort fallback if neither of
         the above is available. Kept for safety only.

      Both bundles are SELF-HOSTED out of public/vendor/. We were
      pulling them from unpkg.com which added 200–800ms of cold-load
      latency on weak venue Wi-Fi BEFORE the camera could even start.
      Self-hosting drops first-scan time noticeably and removes the
      "camera area stays black for ages on a fresh page load"
      operator complaint.
--}}
<script src="{{ asset('vendor/zxing/library-0.21.3.min.js') }}"></script>
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode-2.3.8.min.js') }}"></script>

<style>
/* =========================================================
   GATE SCANNER — premium chrome
   Scoped to [data-scanner-root] so the rest of the app's
   layout / Prism tokens are untouched.
   ========================================================= */

[data-scanner-root] {
    --scan-shell-radius: 26px;
    --scan-ok:    #34d399;
    --scan-used:  #fbbf24;
    --scan-error: #fb7185;
    max-width: 28rem;
    margin: 0 auto;
    padding: 12px 12px 24px;
    padding-bottom: max(24px, env(safe-area-inset-bottom));
    display: grid;
    gap: 14px;
}

.scanner-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px;
    border-radius: 18px;
    /* `var(--prism-glass)` was never defined — falls back to transparent.
       Use the same dark-glass tokens as the rest of the chrome so the
       header has a visible surface on both themes. */
    background: linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
    border: 1px solid var(--prism-border);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
}
:root[data-pt-theme="light"] .scanner-header {
    background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.86));
    border-color: rgba(15,23,42,0.14);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.95),
        0 12px 28px -16px rgba(15,23,42,0.18),
        0 2px 6px -2px rgba(15,23,42,0.08);
}

.scanner-stage {
    position: relative;
    overflow: hidden;
    border-radius: var(--scan-shell-radius);
    background: rgba(8,10,20,0.92);
    border: 1px solid var(--prism-border);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.04),
        0 24px 48px -22px rgba(0,0,0,0.85);
    aspect-ratio: 3 / 4;
    isolation: isolate;
}
@media (min-width: 480px) {
    .scanner-stage { aspect-ratio: 4 / 5; }
}

.scanner-video,
.scanner-video > video,
#qr-reader > video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    background: rgba(8,10,20,0.92);
    border-radius: var(--scan-shell-radius);
}
#qr-reader { width: 100%; height: 100%; }
/* Hide the default html5-qrcode UI (we render our own). */
#qr-reader__dashboard,
#qr-reader__header_message,
#qr-reader__camera_selection,
#qr-reader__scan_region img { display: none !important; }

.scanner-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 2;
}

.scanner-reticle {
    position: relative;
    width: min(72%, 280px);
    aspect-ratio: 1 / 1;
    border-radius: 22px;
}

.reticle-corner {
    --c-len: 28px;
    --c-thick: 3px;
    position: absolute;
    width: var(--c-len);
    height: var(--c-len);
    border-color: rgba(34,211,238,0.85);
    box-shadow: 0 0 18px rgba(34,211,238,0.4);
}
.reticle-corner.tl { top: 0; left: 0;  border-top:    var(--c-thick) solid; border-left:  var(--c-thick) solid; border-top-left-radius:  20px; }
.reticle-corner.tr { top: 0; right: 0; border-top:    var(--c-thick) solid; border-right: var(--c-thick) solid; border-top-right-radius: 20px; }
.reticle-corner.bl { bottom: 0; left: 0;  border-bottom: var(--c-thick) solid; border-left:  var(--c-thick) solid; border-bottom-left-radius:  20px; }
.reticle-corner.br { bottom: 0; right: 0; border-bottom: var(--c-thick) solid; border-right: var(--c-thick) solid; border-bottom-right-radius: 20px; }

.reticle-line {
    position: absolute;
    left: 6%;
    right: 6%;
    height: 2px;
    background: linear-gradient(90deg,
        transparent, #22d3ee 30%, #818cf8 50%, #c084fc 70%, transparent);
    box-shadow: 0 0 14px rgba(34,211,238,0.7);
    border-radius: 999px;
    animation: scanLine 1.6s cubic-bezier(.2,.7,.2,1) infinite;
}
@keyframes scanLine {
    0%,100% { top: 8%;  opacity: .55; }
    50%     { top: 88%; opacity: 1;   }
}

.scanner-status {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 4;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    border: 1px solid var(--prism-border-strong);
    background: rgba(8,10,20,0.85);
    color: var(--prism-text);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    transition: background .2s, color .2s, transform .2s;
    box-shadow: 0 12px 28px -14px rgba(0,0,0,0.85);
}
.scanner-status.state-ready { color: #c2cad8; }
.scanner-status.state-scanning {
    background: rgba(34,211,238,0.92);
    color: #051923;
    border-color: rgba(165,243,252,0.7);
    box-shadow: 0 0 22px rgba(34,211,238,0.45);
}
.scanner-status.state-ok {
    background: rgba(16,185,129,0.92);
    color: #022c22;
    border-color: rgba(110,231,183,0.7);
    box-shadow: 0 0 22px rgba(52,211,153,0.55);
}
.scanner-status.state-used {
    background: rgba(251,191,36,0.92);
    color: #1b1208;
    border-color: rgba(254,240,138,0.7);
    box-shadow: 0 0 22px rgba(251,191,36,0.55);
}
.scanner-status.state-error {
    background: rgba(244,63,94,0.92);
    color: #fff1f2;
    border-color: rgba(253,164,175,0.7);
    box-shadow: 0 0 22px rgba(251,113,133,0.55);
}
.scanner-status.is-pop {
    transform: translateX(-50%) scale(1.08);
}

/* Stage edge glow per state — instantly visible from a distance. */
.scanner-stage[data-state="ok"]    { box-shadow: 0 0 0 2px rgba(52,211,153,0.55), 0 24px 48px -22px rgba(0,0,0,0.85); }
.scanner-stage[data-state="used"]  { box-shadow: 0 0 0 2px rgba(251,191,36,0.55), 0 24px 48px -22px rgba(0,0,0,0.85); }
.scanner-stage[data-state="error"] { box-shadow: 0 0 0 2px rgba(251,113,133,0.55), 0 24px 48px -22px rgba(0,0,0,0.85); animation: stageShake .35s cubic-bezier(.36,.07,.19,.97); }
@keyframes stageShake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px);  }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px);  }
}

.scanner-loading {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--prism-text-2);
    background: rgba(8,10,20,0.88);
    transition: opacity .2s ease;
}
.scanner-loading.is-hidden { opacity: 0; pointer-events: none; }
.prism-spinner {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 3px solid rgba(129,140,248,0.25);
    border-top-color: rgba(129,140,248,0.95);
    animation: spin 0.85s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.scanner-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.scanner-controls .prism-btn-ghost.is-on {
    background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.04));
    border-color: rgba(253,224,71,0.45);
    color: #fef3c7;
}

/* =========================================================
   STAGE 2 — low-light flash suggest pulse + zoom indicator
   =========================================================
   The flash button pulses softly when the watchdog detects
   prolonged low ambient luminance AND the device exposes a
   torch capability AND the operator hasn't already turned
   the flash on. We never auto-toggle the torch — the visual
   suggest respects operator preference. */
.scanner-controls .prism-btn-ghost.is-suggest:not(.is-on) {
    animation: scanner-flash-suggest 1.4s ease-in-out infinite;
    border-color: rgba(253,224,71,0.55);
    color: #fef3c7;
}
@keyframes scanner-flash-suggest {
    0%, 100% { box-shadow: 0 0 0 0 rgba(253,224,71,0.45); }
    50%      { box-shadow: 0 0 0 6px rgba(253,224,71,0.00); }
}

/* Live zoom indicator chip, used by both the pinch-to-zoom
   handler and the auto-zoom recovery logic so the operator
   knows the camera is currently zoomed in. */
.scanner-stage { position: relative; }
.scan-zoom-chip {
    position: absolute;
    top: 12px;
    inset-inline-start: 12px;
    z-index: 5;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(2,4,12,0.55);
    -webkit-backdrop-filter: blur(6px);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.18);
    color: #fef3c7;
    font: 600 11px/1 ui-sans-serif, system-ui, sans-serif;
    letter-spacing: 0.02em;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-4px);
    transition: opacity .18s ease, transform .18s ease;
}
.scan-zoom-chip.is-visible {
    opacity: 1;
    transform: translateY(0);
}

/* =========================================================
   PREMIUM RESULT SHEET
   ========================================================= */

.scan-sheet {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 16px;
    padding-bottom: max(16px, env(safe-area-inset-bottom));
    background: rgba(2,4,12,0.55);
    backdrop-filter: blur(8px) saturate(140%);
    -webkit-backdrop-filter: blur(8px) saturate(140%);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s cubic-bezier(.2,.7,.2,1);
}
/* Light-mode override: soften the scrim so the cream scanner chrome
   isn't darkened too aggressively. The result card itself stays dark
   (intentional — operators need it readable at venue entrances under
   harsh / glare lighting). */
:root[data-pt-theme="light"] .scan-sheet {
    background: rgba(15,23,42,0.32);
}
.scan-sheet[data-state="visible"] {
    opacity: 1;
    pointer-events: auto;
}
.scan-sheet-card {
    width: 100%;
    max-width: 28rem;
    padding: 18px;
    border-radius: 26px;
    background: linear-gradient(180deg, rgba(20,24,38,0.96), rgba(8,10,20,0.96));
    border: 1px solid rgba(129,140,248,0.32);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 24px 48px -22px rgba(0,0,0,0.85),
        0 0 32px rgba(34,211,238,0.18);
    display: grid;
    gap: 10px;
    transform: translateY(28px) scale(.97);
    transition: transform .28s cubic-bezier(.2,.7,.2,1);
}
.scan-sheet[data-state="visible"] .scan-sheet-card {
    transform: translateY(0) scale(1);
}
.scan-sheet[data-result="ok"]    .scan-sheet-card { border-color: rgba(110,231,183,0.55); box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 24px 48px -22px rgba(0,0,0,0.85), 0 0 36px rgba(52,211,153,0.30); }
.scan-sheet[data-result="used"]  .scan-sheet-card { border-color: rgba(254,240,138,0.55); box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 24px 48px -22px rgba(0,0,0,0.85), 0 0 36px rgba(251,191,36,0.30); }
.scan-sheet[data-result="error"] .scan-sheet-card { border-color: rgba(253,164,175,0.55); box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 24px 48px -22px rgba(0,0,0,0.85), 0 0 36px rgba(251,113,133,0.30); }

.scan-sheet-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px 6px 8px;
    align-self: flex-start;
    border-radius: 999px;
    border: 1px solid var(--prism-border);
    font-weight: 800;
    font-size: 12px;
    letter-spacing: .06em;
    color: var(--prism-text);
}
.scan-sheet-badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    font-weight: 800;
    font-size: 14px;
}
.scan-sheet[data-result="ok"]    .scan-sheet-badge       { background: rgba(16,185,129,0.18); border-color: rgba(110,231,183,0.45); color: #d1fae5; }
.scan-sheet[data-result="ok"]    .scan-sheet-badge-icon  { background: rgba(52,211,153,0.32); color: #022c22; animation: badgePop .35s cubic-bezier(.2,.7,.2,1); }
.scan-sheet[data-result="used"]  .scan-sheet-badge       { background: rgba(251,191,36,0.18); border-color: rgba(254,240,138,0.45); color: #fef3c7; }
.scan-sheet[data-result="used"]  .scan-sheet-badge-icon  { background: rgba(251,191,36,0.32); color: #1b1208; }
.scan-sheet[data-result="error"] .scan-sheet-badge       { background: rgba(244,63,94,0.18); border-color: rgba(253,164,175,0.45); color: #ffe4e6; }
.scan-sheet[data-result="error"] .scan-sheet-badge-icon  { background: rgba(244,63,94,0.32); color: #fff1f2; }
@keyframes badgePop {
    0%   { transform: scale(.5); opacity: 0; }
    60%  { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); }
}

.scan-sheet-name {
    font-size: 18px;
    font-weight: 800;
    color: var(--prism-text);
    letter-spacing: .01em;
    line-height: 1.25;
}
.scan-sheet-ref {
    font-size: 11px;
    color: var(--prism-text-3);
    letter-spacing: .12em;
    text-transform: uppercase;
}
.scan-sheet-ref:empty { display: none; }

.scan-sheet-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--prism-border);
    color: var(--prism-text-2);
    font-size: 13px;
}
.scan-sheet-row-icon { font-size: 14px; opacity: .9; }
.scan-sheet-row-text { font-weight: 600; }

.scan-sheet-seats {
    display: grid;
    gap: 6px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--prism-border);
}
.scan-sheet-seats-label {
    font-size: 11px;
    letter-spacing: .14em;
    color: var(--prism-text-3);
    text-transform: uppercase;
    display: inline-flex;
    gap: 6px;
}

/* =========================================================
   BIG SEAT BADGE — the headline seat for THIS QR.
   Designed to be readable across a venue entrance: the
   section label sits above the seat label, both centered.
   ========================================================= */
.scan-seat-hero {
    display: grid;
    justify-items: center;
    align-content: center;
    gap: 4px;
    padding: 16px 18px;
    border-radius: 22px;
    background:
        radial-gradient(circle at 50% 0%, rgba(255,255,255,0.06), transparent 60%),
        linear-gradient(180deg, rgba(20,24,38,0.96), rgba(8,10,20,0.96));
    border: 1px solid rgba(129,140,248,0.45);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 18px 40px -22px rgba(0,0,0,0.85),
        0 0 28px rgba(34,211,238,0.18);
    text-align: center;
}
.scan-seat-hero[hidden] { display: none; }
.scan-seat-hero-section {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .26em;
    text-transform: uppercase;
    color: var(--prism-text-3);
}
.scan-seat-hero-label {
    font-size: 56px;
    font-weight: 900;
    line-height: 1;
    letter-spacing: .03em;
    background: linear-gradient(135deg, #f9fafb 10%, #fde68a 60%, #fbbf24 95%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 0 0 18px rgba(251,191,36,0.20);
}
@media (max-width: 360px) {
    .scan-seat-hero-label { font-size: 44px; }
}

/* Per-state hero color — matches the rest of the sheet. */
.scan-sheet[data-result="ok"]    .scan-seat-hero {
    border-color: rgba(110,231,183,0.55);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 18px 40px -22px rgba(0,0,0,0.85),
        0 0 32px rgba(52,211,153,0.30);
}
.scan-sheet[data-result="ok"]    .scan-seat-hero-label {
    background: linear-gradient(135deg, #ecfdf5 10%, #6ee7b7 60%, #10b981 95%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    text-shadow: 0 0 18px rgba(52,211,153,0.30);
}
.scan-sheet[data-result="used"]  .scan-seat-hero {
    border-color: rgba(254,240,138,0.55);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 18px 40px -22px rgba(0,0,0,0.85),
        0 0 32px rgba(251,191,36,0.30);
}
.scan-sheet[data-result="used"]  .scan-seat-hero-label {
    background: linear-gradient(135deg, #fffbeb 10%, #fde68a 60%, #f59e0b 95%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    text-shadow: 0 0 18px rgba(251,191,36,0.30);
}
.scan-sheet[data-result="error"] .scan-seat-hero {
    border-color: rgba(253,164,175,0.55);
}
.scan-sheet[data-result="error"] .scan-seat-hero-label {
    background: linear-gradient(135deg, #fff1f2 10%, #fda4af 60%, #f43f5e 95%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    text-shadow: 0 0 18px rgba(251,113,133,0.30);
}
.scan-sheet-seats-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.scan-seat-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .04em;
    background: rgba(251,191,36,0.10);
    border: 1px solid rgba(251,191,36,0.40);
    color: #fef3c7;
}

.scan-sheet-used-note {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 12px;
    background: rgba(251,191,36,0.10);
    border: 1px solid rgba(251,191,36,0.40);
    color: #fef3c7;
    font-size: 12px;
}

.scan-sheet-foot {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    padding-top: 4px;
}
.scan-sheet-done {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .04em;
}
.scan-sheet-hint {
    font-size: 11px;
    color: var(--prism-text-3);
    letter-spacing: .12em;
    text-transform: uppercase;
    text-align: center;
}

/* First-scan success is intentionally calm: no warning chrome, no
   instructional dismiss-hint. The big green ✓ badge + name + seat
   + show is the whole story. We only keep the dismiss-hint visible
   on `used` and `error` results, where the operator may need the
   "tap to close" reminder before the next attendee. The used-note
   is `hidden` from JS on OK too — these CSS rules are belt-and-
   braces so an OK first-scan can NEVER surface duplicate-scan
   chrome. */
.scan-sheet[data-result="ok"] .scan-sheet-used-note { display: none !important; }
.scan-sheet[data-result="ok"] .scan-sheet-hint      { display: none !important; }

/* =========================================================
   LIGHT MODE POLISH — scanner page chrome + result sheet
   =========================================================
   The default scanner palette was tuned for the dark theme.
   Several surfaces relied on Prism tokens (--prism-text /
   --prism-text-2 / --prism-text-3 / --prism-border) which
   flip to DARK slate in light mode and disappear against
   the scanner's dark chrome — that's the "washed-out / hard
   to read" feeling operators reported on bright-light /
   daylight venue setups.

   Strategy:
   1. Scanner page chrome (status pill / loading scrim /
      zoom chip / flash button) gets light-glass surfaces so
      it reads cleanly against the cream page background.
   2. The result sheet card switches to a premium cream
      glass surface for native light-theme parity. State
      accents (ok / used / error) keep their semantic
      colors but become darker, more confident shades that
      sit well on cream.
   3. Every text inside the card gets pinned to a fixed
      dark-slate color so prism-token bleed-through can't
      erase contrast.
   ========================================================= */

/* --- Scanner page chrome ------------------------------- */

/* Status pill — ready state on the dark video viewport
   reads fine, but the pill sits ABOVE the camera viewport
   so its surrounding context is the cream page chrome on
   light mode. Promote it to a confident light glass so the
   ready / scanning / ok / used / error states all read
   crisply from across the room. */
:root[data-pt-theme="light"] .scanner-status {
    background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.88));
    border-color: rgba(15,23,42,0.18);
    color: #1f2937;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 12px 28px -14px rgba(15,23,42,0.28);
}
:root[data-pt-theme="light"] .scanner-status.state-ready { color: #334155; }
/* scanning / ok / used / error already paint their own
   high-contrast surface + text colors and stay legible on
   the cream page chrome — leaving those rules untouched. */

/* Loading scrim — soft cream wash + darker label so the
   "starting camera" copy doesn't disappear into the page. */
:root[data-pt-theme="light"] .scanner-loading {
    background: rgba(244,241,234,0.94);
    color: #334155;
}
:root[data-pt-theme="light"] .prism-spinner {
    border: 3px solid rgba(79,70,229,0.22);
    border-top-color: rgba(79,70,229,0.95);
}

/* Live zoom indicator chip — flip to light glass + dark
   label so the "1.6×" text is readable when the camera is
   pointed at a bright surface (which is when zoom typically
   engages — operators framing a small distant QR). */
:root[data-pt-theme="light"] .scan-zoom-chip {
    background: rgba(255,255,255,0.94);
    border: 1px solid rgba(15,23,42,0.18);
    color: #334155;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 6px 14px -8px rgba(15,23,42,0.22);
}

/* Flash button — torch active state */
:root[data-pt-theme="light"] .scanner-controls .prism-btn-ghost.is-on {
    background: linear-gradient(135deg, rgba(245,158,11,0.22), rgba(245,158,11,0.10));
    border-color: rgba(180,83,9,0.55);
    color: #92400e;
}
/* Flash button — low-light suggest pulse: the gold halo
   stays gold (universal "tap me for light"), but text
   color flips to dark amber so the label reads on the
   cream surface. */
:root[data-pt-theme="light"] .scanner-controls .prism-btn-ghost.is-suggest:not(.is-on) {
    border-color: rgba(180,83,9,0.55);
    color: #92400e;
}

/* --- Result sheet (premium cream surface) -------------- */

/* The dark-card-for-glare default served the operator at
   night-time entrances but felt washed-out in light mode
   because every text token rendered dark-on-dark. Switch
   to a confident cream glass surface in light mode — the
   per-result outline (ok / used / error) still rings the
   card so an operator across the venue sees the verdict
   from peripheral vision. */
:root[data-pt-theme="light"] .scan-sheet-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.97), rgba(252,250,245,0.94));
    border-color: rgba(15,23,42,0.18);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 28px 56px -22px rgba(15,23,42,0.30),
        0 8px 18px -8px rgba(15,23,42,0.14);
}
:root[data-pt-theme="light"] .scan-sheet[data-result="ok"]    .scan-sheet-card {
    border-color: rgba(4,120,87,0.55);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 28px 56px -22px rgba(4,120,87,0.32),
        0 0 36px rgba(16,185,129,0.20);
}
:root[data-pt-theme="light"] .scan-sheet[data-result="used"]  .scan-sheet-card {
    border-color: rgba(180,83,9,0.60);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 28px 56px -22px rgba(180,83,9,0.32),
        0 0 36px rgba(245,158,11,0.22);
}
:root[data-pt-theme="light"] .scan-sheet[data-result="error"] .scan-sheet-card {
    border-color: rgba(190,18,60,0.60);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 28px 56px -22px rgba(190,18,60,0.32),
        0 0 36px rgba(244,63,94,0.22);
}

/* Sheet scrim — slightly stronger in light mode so the
   card lifts off the cream page without feeling murky. */
:root[data-pt-theme="light"] .scan-sheet {
    background: rgba(15,23,42,0.38);
}

/* Top badge — saturated semantic chip with dark label. */
:root[data-pt-theme="light"] .scan-sheet[data-result="ok"]    .scan-sheet-badge {
    background: rgba(16,185,129,0.16);
    border-color: rgba(4,120,87,0.55);
    color: #065f46;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="ok"]    .scan-sheet-badge-icon {
    background: rgba(16,185,129,0.96);
    color: #ffffff;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="used"]  .scan-sheet-badge {
    background: rgba(245,158,11,0.18);
    border-color: rgba(180,83,9,0.55);
    color: #92400e;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="used"]  .scan-sheet-badge-icon {
    background: rgba(245,158,11,0.96);
    color: #1b1208;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="error"] .scan-sheet-badge {
    background: rgba(244,63,94,0.16);
    border-color: rgba(190,18,60,0.55);
    color: #9f1239;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="error"] .scan-sheet-badge-icon {
    background: rgba(244,63,94,0.96);
    color: #fff1f2;
}

/* Headline (attendee name) + booking reference — pinned
   to fixed dark slate so prism token bleed can't erase
   contrast on the cream card. */
:root[data-pt-theme="light"] .scan-sheet-name { color: #0f172a; }
:root[data-pt-theme="light"] .scan-sheet-ref  { color: #64748b; }

/* Show / time rows — soft slate wash on cream so each
   row visibly separates from the card without yelling. */
:root[data-pt-theme="light"] .scan-sheet-row {
    background: rgba(15,23,42,0.045);
    border-color: rgba(15,23,42,0.14);
    color: #1f2937;
}
:root[data-pt-theme="light"] .scan-sheet-row-text { color: #0f172a; }
:root[data-pt-theme="light"] .scan-sheet-row-icon { opacity: 1; }

/* Big seat hero — keep the spotlight, but on cream with
   a darker semantic gradient label so the seat number
   reads from across the entrance. */
:root[data-pt-theme="light"] .scan-seat-hero {
    background:
        radial-gradient(circle at 50% 0%, rgba(15,23,42,0.08), transparent 60%),
        linear-gradient(180deg, rgba(255,255,255,0.97), rgba(248,245,239,0.94));
    border-color: rgba(79,70,229,0.40);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 18px 36px -20px rgba(15,23,42,0.24),
        0 0 28px rgba(79,70,229,0.12);
}
:root[data-pt-theme="light"] .scan-seat-hero-section { color: #475569; }
:root[data-pt-theme="light"] .scan-seat-hero-label {
    background: linear-gradient(135deg, #78350f 5%, #b45309 50%, #f59e0b 95%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    text-shadow: none;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="ok"] .scan-seat-hero {
    border-color: rgba(4,120,87,0.55);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 18px 36px -20px rgba(4,120,87,0.26),
        0 0 28px rgba(16,185,129,0.16);
}
:root[data-pt-theme="light"] .scan-sheet[data-result="ok"] .scan-seat-hero-label {
    background: linear-gradient(135deg, #065f46 5%, #047857 50%, #10b981 95%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    text-shadow: none;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="used"] .scan-seat-hero {
    border-color: rgba(180,83,9,0.55);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 18px 36px -20px rgba(180,83,9,0.26),
        0 0 28px rgba(245,158,11,0.16);
}
:root[data-pt-theme="light"] .scan-sheet[data-result="used"] .scan-seat-hero-label {
    background: linear-gradient(135deg, #78350f 5%, #92400e 50%, #b45309 95%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    text-shadow: none;
}
:root[data-pt-theme="light"] .scan-sheet[data-result="error"] .scan-seat-hero {
    border-color: rgba(190,18,60,0.55);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.97),
        0 18px 36px -20px rgba(190,18,60,0.26),
        0 0 28px rgba(244,63,94,0.16);
}
:root[data-pt-theme="light"] .scan-sheet[data-result="error"] .scan-seat-hero-label {
    background: linear-gradient(135deg, #881337 5%, #be123c 50%, #f43f5e 95%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    text-shadow: none;
}

/* Seats group (label + chips) on cream */
:root[data-pt-theme="light"] .scan-sheet-seats {
    background: rgba(15,23,42,0.045);
    border-color: rgba(15,23,42,0.14);
}
:root[data-pt-theme="light"] .scan-sheet-seats-label { color: #475569; }
:root[data-pt-theme="light"] .scan-seat-chip {
    background: rgba(245,158,11,0.16);
    border-color: rgba(180,83,9,0.50);
    color: #92400e;
}

/* "Already used at HH:MM" amber note */
:root[data-pt-theme="light"] .scan-sheet-used-note {
    background: rgba(245,158,11,0.16);
    border-color: rgba(180,83,9,0.50);
    color: #92400e;
}

/* Dismiss hint at the bottom */
:root[data-pt-theme="light"] .scan-sheet-hint { color: #64748b; }

@media (prefers-reduced-motion: reduce) {
    .reticle-line,
    .scan-sheet-card,
    .scanner-status { animation: none !important; transition: none !important; }
    .scanner-stage[data-state="error"] { animation: none !important; }
}
</style>

<script>
(() => {
    'use strict';

    /* ============================================================
       i18n shim
       ============================================================ */
    const tt = (key, fallback) => {
        try {
            if (typeof window.PT_T === 'function') return window.PT_T(key, fallback);
        } catch (_) {}
        return fallback;
    };

    /* ============================================================
       Scanner config
       ============================================================ */
    // Cooldown = how long before the SAME code can be re-scanned
    // (a small guard so a slowly-moving QR isn't pinged twice in a
    // row). PR #70: the result sheet no longer auto-dismisses — the
    // operator manually closes it, and the scanner is paused while
    // it's open and resumes the moment it closes.
    //
    // Scanner engine v5: 1500ms felt deliberately hesitant — the
    // operator could see a QR in frame for nearly two seconds before
    // a re-scan was allowed. The sheet-open gate plus the busy gate
    // already prevent double-pings, so 700ms is enough to dedupe a
    // slowly-moving QR without making the same operator wait on a
    // deliberate re-scan.
    const COOLDOWN_MS = 700;

    let busy = false;          // mid-flight backend round-trip
    let lastCode = null;
    let lastScanTime = 0;
    let sheetOpen = false;     // pause scans while showing a result
    let qrInstance = null;     // assigned once html5-qrcode boots

    /* ============================================================
       Audio + haptic feedback

       Scanner engine v5 introduces a TWO-STAGE feedback pattern
       that modern scanners (iOS Camera, Google Lens, professional
       event-entry scanners) use:

         Stage 1 — pre-confirmation. Fires the INSTANT a QR decodes
         locally, BEFORE the backend POST resolves. A short 40ms
         buzz + a soft, quiet 'tick' so the operator gets physical
         proof that a QR was seen. This is the single biggest
         perceived-latency win in this PR: the 100–400ms backend
         round-trip becomes invisible because the operator already
         got physical confirmation.

         Stage 2 — final confirmation. Fires when the server
         responds with ok / used / error. The long buzz + the loud
         tonal beep + the result-sheet slide-up.

       AudioContext prewarm: iOS Safari requires a user gesture
       before audio works. We resume() the context on the first
       touch/click anywhere on the page, so the first scan's beep
       isn't silent.
       ============================================================ */
    let audioCtx = null;
    let audioPrimed = false;

    function ensureAudio() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended' && typeof audioCtx.resume === 'function') {
                audioCtx.resume().catch(() => {});
            }
        } catch (_) {}
    }

    function primeAudio() {
        if (audioPrimed) return;
        audioPrimed = true;
        ensureAudio();
    }
    // Prime on the first user gesture anywhere on the scanner page.
    // Passive listeners + { once: true } so we don't fight scrolling
    // or other touch targets.
    ['touchstart', 'pointerdown', 'click', 'keydown'].forEach((ev) => {
        document.addEventListener(ev, primeAudio, { once: true, passive: true });
    });

    function beep(type) {
        try {
            ensureAudio();
            if (!audioCtx) return;
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = type === 'ok' ? 950 : type === 'used' ? 500 : 250;
            gain.gain.value = 0.22;
            osc.start();
            setTimeout(() => osc.stop(), 150);
        } catch (_) {}
    }

    // Soft 'tick' — quieter and shorter than the result beep.
    // Played at decode-time as Stage 1 confirmation. Sharp attack +
    // sharp release so it reads as a click, not a tone.
    function softTick() {
        try {
            ensureAudio();
            if (!audioCtx) return;
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type            = 'square';
            osc.frequency.value = 1500;
            // Fast attack/release envelope so it sounds like a click,
            // not a chirp. ~50ms total duration.
            const now = audioCtx.currentTime;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.10, now + 0.005);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.045);
            osc.start(now);
            osc.stop(now + 0.05);
        } catch (_) {}
    }

    function vibrate(type) {
        if (!('vibrate' in navigator)) return;
        if      (type === 'precheck') navigator.vibrate(40);
        else if (type === 'ok')       navigator.vibrate(120);
        else if (type === 'used')     navigator.vibrate([100, 50, 100]);
        else                           navigator.vibrate(220);
    }

    /* ============================================================
       Status pill
       ============================================================ */
    const $status  = document.getElementById('status');
    const $stage   = document.querySelector('[data-scanner-stage]');
    const $loading = document.getElementById('scanner-loading');
    function setStatus(text, type) {
        $status.textContent = text;
        $status.classList.remove('state-ready', 'state-scanning', 'state-ok', 'state-used', 'state-error', 'is-pop');
        $status.classList.add('state-' + (type || 'ready'), 'is-pop');
        setTimeout(() => $status.classList.remove('is-pop'), 200);
        if (type === 'ok' || type === 'used' || type === 'error') {
            $stage.dataset.state = type;
        } else {
            delete $stage.dataset.state;
        }
    }

    /* ============================================================
       Result sheet
       ============================================================ */
    const $sheet         = document.getElementById('scan-sheet');
    const $sheetBadge    = $sheet.querySelector('[data-scan-badge-text]');
    const $sheetIcon     = $sheet.querySelector('[data-scan-icon]');
    const $sheetName     = $sheet.querySelector('[data-scan-name]');
    const $sheetRef      = $sheet.querySelector('[data-scan-ref]');
    const $sheetShow     = $sheet.querySelector('[data-scan-show]');
    const $sheetWhen     = $sheet.querySelector('[data-scan-when]');
    const $sheetHero     = $sheet.querySelector('[data-scan-seat-hero]');
    const $sheetHeroSec  = $sheet.querySelector('[data-scan-seat-hero-section]');
    const $sheetHeroLbl  = $sheet.querySelector('[data-scan-seat-hero-label]');
    const $sheetUsedNote = $sheet.querySelector('[data-scan-used-note]');
    const $sheetUsedTime = $sheet.querySelector('[data-scan-used-time]');
    const $sheetDismiss  = $sheet.querySelector('[data-scan-dismiss]');

    function sectionLabel(s) {
        if (!s) return '';
        const k = ('' + s).toLowerCase();
        if (k === 'hall')    return tt('section_hall',    'الصالة');
        if (k === 'balcony') return tt('section_balcony', 'البلكون');
        return s;
    }

    function showSheet(result, payload) {
        $sheet.dataset.result = result; // ok | used | error
        $sheet.dataset.state  = 'visible';
        sheetOpen = true;

        // Badge text + icon — strip any leading emoji so the badge
        // box (which already has an icon slot) doesn't double-stamp it.
        const badgeText =
            result === 'ok'   ? tt('adm_scanner_ok',     '✅ دخول مسموح') :
            result === 'used' ? tt('adm_scanner_used',   '⚠️ مستخدمة')   :
                                tt('adm_scanner_invalid','❌ غير صالح');
        $sheetBadge.textContent = badgeText.replace(/^[^\p{L}\p{N}]+\s*/u, '');
        $sheetIcon.textContent  = result === 'ok' ? '✓' : result === 'used' ? '!' : '✕';

        const p = payload || {};

        // Attendee — PR #70: this is the per-ticket name, not the
        // booking owner's name.
        $sheetName.textContent = p.name || tt('adm_scanner_unknown_name', '—');
        $sheetRef.textContent  = p.reference ? '#' + p.reference : '';

        // Show + when
        $sheetShow.textContent = p.show_title || '—';
        const dateStr = p.date || '';
        const timeStr = p.time || '';
        $sheetWhen.textContent = [dateStr, timeStr].filter(Boolean).join(' · ') || '—';

        // BIG SEAT BADGE — the headline seat for THIS QR. Falls back
        // gracefully for tickets without a specific seat (manual /
        // "Other" venue bookings, or pre-PR-70 legacy rows the
        // back-fill missed).
        const heroSeat = (p.seat && (p.seat.label || p.seat.row_letter))
            ? p.seat
            : null;
        if (heroSeat) {
            $sheetHeroSec.textContent = sectionLabel(heroSeat.section);
            $sheetHeroLbl.textContent = heroSeat.label
                || ((heroSeat.row_letter || '') + (heroSeat.seat_number || ''));
            $sheetHero.hidden = false;
        } else {
            $sheetHero.hidden = true;
        }

        // PR #71: the popup now stays focused on the SCANNED attendee
        // only. The "other seats from the booking" group context that
        // existed in PR #70 has been removed per operator feedback —
        // the gate operator only needs to verify this one ticket.

        // Used note
        if (result === 'used' && p.scanned_at) {
            $sheetUsedNote.hidden = false;
            $sheetUsedTime.textContent = ' · ' + p.scanned_at;
        } else {
            $sheetUsedNote.hidden = true;
            $sheetUsedTime.textContent = '';
        }

        // Pause scanning while the sheet is up so we don't waste
        // CPU re-decoding the same QR the operator is reviewing.
        if (qrInstance) {
            try { qrInstance.pause(true); } catch (_) {}
        }
    }

    function hideSheet() {
        $sheet.dataset.state = 'hidden';
        sheetOpen = false;
        // Reset stage glow back to ready when the sheet leaves.
        delete $stage.dataset.state;
        setStatus(tt('adm_scanner_ready', 'جاهز للفحص'), 'ready');
        // Clear the lastCode lock so scanning the same QR again
        // (e.g. an already-used ticket the operator wants to re-check)
        // doesn't get silently swallowed by the cooldown.
        lastCode = null;
        lastScanTime = 0;
        // Resume the scanner instantly so the operator can move on.
        if (qrInstance) {
            try { qrInstance.resume(); } catch (_) {}
        }
    }
    $sheetDismiss.addEventListener('click', hideSheet);
    // Tap outside the card to dismiss.
    $sheet.addEventListener('click', (e) => {
        if (e.target === $sheet) hideSheet();
    });
    // Escape closes the sheet too — useful when the device has a
    // physical keyboard or external scanner attached.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sheetOpen) hideSheet();
    });

    /* ============================================================
       Backend round-trip
       ============================================================ */
    function check(code) {
        fetch('{{ route('admin.scanner.check') }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ code }),
        })
        .then((r) => r.json())
        .then((d) => {
            if (d.status === 'ok') {
                setStatus(tt('adm_scanner_ok', '✅ دخول مسموح'), 'ok');
                vibrate('ok');  beep('ok');
                showSheet('ok', d);
            } else if (d.status === 'used') {
                setStatus(tt('adm_scanner_used', '⚠️ مستخدمة'), 'used');
                vibrate('used'); beep('used');
                showSheet('used', d);
            } else {
                setStatus(tt('adm_scanner_invalid', '❌ غير صالح'), 'error');
                vibrate('error'); beep('error');
                showSheet('error', d || {});
            }
        })
        .catch(() => {
            setStatus(tt('adm_scanner_network_err', '⚠️ تعذّر الاتصال'), 'error');
            vibrate('error');
        })
        .finally(() => {
            // Release the busy flag fast — the cooldown check below
            // protects against re-pinging the backend with the same
            // code, so we don't need a long busy hold.
            setTimeout(() => { busy = false; }, 250);
        });
    }

    /* ============================================================
       Shared scan-success funnel

       This is the single funnel every decode engine (Path A native
       BarcodeDetector, Path B ZXing, Path C html5-qrcode) calls
       when it finds a QR. Gates dedupe / sheet-open / busy state
       and triggers Stage-1 pre-confirmation feedback (tick + short
       buzz) before kicking the backend POST.
       ============================================================ */
    function onScanSuccess(text) {
        // Drop frames while a sheet is already open — the operator
        // is reviewing the previous result. The scanner resumes
        // automatically when they tap Done / outside / Esc.
        if (sheetOpen) return;
        const now = Date.now();
        // Same-code guard so a slowly-moving QR isn't pinged twice
        // in quick succession. Cleared on hideSheet() so re-scanning
        // the same ticket for a second look isn't swallowed.
        if (text === lastCode && now - lastScanTime < COOLDOWN_MS) return;
        if (busy) return;
        busy = true;
        lastCode = text;
        lastScanTime = now;

        // Stage 1 — pre-confirmation. INSTANT, local-only feedback
        // so the operator gets physical proof that a QR was seen
        // before the backend has had time to respond. This is what
        // makes the scanner FEEL instant; the 100–400ms POST
        // round-trip becomes invisible because the haptic + tick
        // already landed.
        vibrate('precheck');
        softTick();

        setStatus(tt('adm_scanner_processing', '⏳ جارٍ التحقق'), 'scanning');
        check(text);
    }

    /* ============================================================
       Path A — Direct BarcodeDetector (Google-Lens-tier reliability)

       When the browser exposes the native BarcodeDetector API
       (Android Chrome + Edge, some Chromium-based mobile browsers,
       and recent iOS Safari builds), we bypass html5-qrcode entirely
       and run a tight requestVideoFrameCallback loop straight on the
       <video> element. This:
         - avoids html5-qrcode's canvas roundtrip per frame,
         - lets the platform-native scanner (VisionKit / ML Kit) handle
           tilt, scale, partial framing, and low-light far better than
           jsQR ever could,
         - frees us to ask for a much higher capture resolution and
           frame rate without burning the main thread.

       If anything in the native path fails (no BarcodeDetector, no
       getUserMedia, the camera rejects our constraints, etc.), we
       fall back transparently to Path B (ZXing-js) and finally to
       Path C (html5-qrcode + jsQR).
       ============================================================ */
    let activeTrack = null;       // MediaStreamTrack for the live camera

    /* ============================================================
       Stage 2 — advanced detection / reliability scaffolding

       These helpers layer ON TOP of the bootstrapped decode path
       (A native / B ZXing / C html5-qrcode) once a camera is live.
       Each feature is INDEPENDENTLY GATED on either a track
       capability or an operator gesture, so on iPhone Safari
       builds that don't expose `zoom` or `torch` the relevant code
       paths quietly no-op rather than regress the scanner.

       Concerns implemented here:
         • luminance helpers (packLuminance, meanLuminance) reused
           across Path B's multi-pass decode AND the low-light
           torch watchdog
         • image-preprocessing transforms used in Path B's miss
           recovery (adaptive threshold, invert, center-ROI 2×
           upscale) for compressed / inverted / small QRs
         • capability cache populated by updateCapabilities(track)
           so the rest of the system can cheaply check what the
           camera actually supports
         • zoom helpers (setZoom + pinch-to-zoom) for operator
           gestures AND auto-recovery
         • low-light watchdog that pulses the flash button when
           ambient luminance is genuinely dim AND torch is supported
           AND the operator hasn't already turned the torch on

       Every effect here is best-effort: applyConstraints failures,
       missing capabilities, or unexpected exceptions all degrade
       silently to baseline behaviour.
       ============================================================ */

    /* --- Luminance helpers --------------------------------------- */

    // Pack RGBA pixel buffer down to a single-channel luminance
    // array using the same Rec. 601 weights ZXing.RGBLuminanceSource
    // uses internally. Inlined here so we can also feed it to the
    // miss-recovery passes (threshold / invert / upscale) without
    // rebuilding it on every pass.
    function packLuminance(rgbaData) {
        const len = rgbaData.length >>> 2;
        const out = new Uint8ClampedArray(len);
        for (let i = 0, j = 0; i < rgbaData.length; i += 4, j++) {
            out[j] = (
                rgbaData[i]     * 0.299 +
                rgbaData[i + 1] * 0.587 +
                rgbaData[i + 2] * 0.114
            ) | 0;
        }
        return out;
    }

    // Sampled mean luminance — stride 16 keeps this <1ms even on
    // 1280×720. Good enough for an ambient-light estimate.
    function meanLuminance(lum) {
        if (!lum || lum.length === 0) return 255;
        let sum = 0, count = 0;
        for (let i = 0; i < lum.length; i += 16) {
            sum += lum[i];
            count++;
        }
        return count > 0 ? (sum / count) | 0 : 255;
    }

    // Adaptive threshold pass — push borderline pixels hard to
    // black/white based on the global mean. Helps WhatsApp-compressed
    // JPEG QR codes where the white background isn't really white
    // anymore.
    function thresholdLuminance(lum, mean) {
        const t = mean;
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = lum[i] >= t ? 255 : 0;
        }
        return out;
    }

    // Invert pass — for QRs that are printed light-on-dark (digital
    // tickets with dark themes, some sticker stocks, etc).
    function invertLuminance(lum) {
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = 255 - lum[i];
        }
        return out;
    }

    // Bilinear upscale of the center 50% of the frame to the full
    // canvas. Operates on the single-channel luminance buffer so we
    // skip a canvas roundtrip. Helps when the QR is small or far
    // from the camera and the operator hasn't pinched / zoomed yet.
    function centerRoiUpscale(lum, w, h) {
        const x0 = w >> 2;
        const y0 = h >> 2;
        const rw = w >> 1;
        const rh = h >> 1;
        const ow = w;
        const oh = h;
        const out = new Uint8ClampedArray(ow * oh);
        const sx = rw / ow;
        const sy = rh / oh;
        const xLast = x0 + rw - 1;
        const yLast = y0 + rh - 1;
        for (let y = 0; y < oh; y++) {
            const fy = y * sy + y0;
            const iy = fy | 0;
            const ay = fy - iy;
            const iyn = iy + 1 < yLast ? iy + 1 : yLast;
            const rowA = iy  * w;
            const rowB = iyn * w;
            const outRow = y * ow;
            for (let x = 0; x < ow; x++) {
                const fx = x * sx + x0;
                const ix = fx | 0;
                const ax = fx - ix;
                const ixn = ix + 1 < xLast ? ix + 1 : xLast;
                const p00 = lum[rowA + ix ];
                const p10 = lum[rowA + ixn];
                const p01 = lum[rowB + ix ];
                const p11 = lum[rowB + ixn];
                const top = p00 + (p10 - p00) * ax;
                const bot = p01 + (p11 - p01) * ax;
                out[outRow + x] = (top + (bot - top) * ay) | 0;
            }
        }
        return out;
    }

    // Wrapper around ZXing.MultiFormatReader.decode() that handles
    // the constructor-signature drift across @zxing/library builds.
    // Returns either a Result or null. Used by Path B's main pass
    // AND each miss-recovery pass.
    function tryZXingDecode(lum, w, h, mfr, hints) {
        if (!lum || !mfr) return null;
        let lumSource;
        try {
            lumSource = new ZXing.RGBLuminanceSource(lum, w, h);
        } catch (_) {
            try { lumSource = new ZXing.RGBLuminanceSource(w, h, lum); }
            catch (__) { return null; }
        }
        let result = null;
        try {
            const binarizer = new ZXing.HybridBinarizer(lumSource);
            const bitmap    = new ZXing.BinaryBitmap(binarizer);
            result = mfr.decode(bitmap, hints || undefined);
        } catch (_) {
            // NotFoundException is the common case — no QR in frame.
            result = null;
        } finally {
            try { mfr.reset(); } catch (_) {}
        }
        return result;
    }

    /* --- Capability cache + zoom / torch / pinch ----------------- */

    const capability = {
        torch:    false,
        zoom:     false,
        zoomMin:  1,
        zoomMax:  1,
        zoomStep: 0.1,
    };
    let zoomCurrent     = 1;
    let zoomBaseAtTouch = 1;
    let pinchStartDist  = 0;
    let lastLuminance   = 255;     // updated by Path B's tick + low-light watchdog
    let lowLightTimer   = null;
    let pinchAttachedTo = null;    // host element pinch handlers are bound to

    const $zoomChip      = document.getElementById('scan-zoom-chip');
    const $zoomChipText  = $zoomChip ? $zoomChip.querySelector('[data-zoom-text]') : null;

    function setZoomChip(level) {
        if (!$zoomChip || !$zoomChipText) return;
        if (level > 1.05) {
            $zoomChipText.textContent = level.toFixed(1) + '×';
            $zoomChip.classList.add('is-visible');
        } else {
            $zoomChip.classList.remove('is-visible');
        }
    }

    function updateCapabilities(track) {
        try {
            if (!track || typeof track.getCapabilities !== 'function') return;
            const caps = track.getCapabilities();
            capability.torch = !!('torch' in caps);
            // Some browsers expose `zoom` as either a MediaSettingsRange
            // object ({min, max, step}) or a plain number. Treat both.
            if (caps.zoom) {
                if (typeof caps.zoom === 'object') {
                    capability.zoom     = true;
                    capability.zoomMin  = caps.zoom.min  || 1;
                    capability.zoomMax  = caps.zoom.max  || 1;
                    capability.zoomStep = caps.zoom.step || 0.1;
                } else if (typeof caps.zoom === 'number') {
                    capability.zoom    = true;
                    capability.zoomMin = 1;
                    capability.zoomMax = caps.zoom;
                }
            }
            // Only register a true zoom capability if the range is
            // actually useful (Safari sometimes reports min==max==1).
            if (capability.zoomMax <= capability.zoomMin) {
                capability.zoom = false;
            }
        } catch (_) {}
    }

    async function setZoom(level) {
        if (!capability.zoom || !activeTrack ||
            typeof activeTrack.applyConstraints !== 'function') {
            return false;
        }
        const clamped = Math.max(
            capability.zoomMin,
            Math.min(capability.zoomMax, level || 1)
        );
        try {
            await activeTrack.applyConstraints({ advanced: [{ zoom: clamped }] });
            zoomCurrent = clamped;
            setZoomChip(clamped);
            return true;
        } catch (_) {
            return false;
        }
    }

    function touchDistance(touches) {
        if (!touches || touches.length < 2) return 0;
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    }

    // Pinch-to-zoom — only wired when the live track exposes a
    // meaningful `zoom` capability. We use passive listeners so we
    // don't fight the operator scrolling the page or interacting with
    // the result sheet; the only thing we ever block is the default
    // touchmove behaviour for two-finger gestures (so iPhone Safari
    // doesn't try to page-zoom on top of our camera zoom).
    function attachPinchZoom(host) {
        if (!host || !capability.zoom) return;
        if (pinchAttachedTo === host) return;
        pinchAttachedTo = host;

        host.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                pinchStartDist  = touchDistance(e.touches);
                zoomBaseAtTouch = zoomCurrent;
            }
        }, { passive: true });

        host.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2 && pinchStartDist > 0) {
                // Prevent the default Safari page-zoom on a two-finger
                // gesture, but ONLY for two-finger touches inside the
                // camera host. Single-finger scrolling / tapping is
                // untouched.
                try { e.preventDefault(); } catch (_) {}
                const newDist = touchDistance(e.touches);
                if (newDist > 0) {
                    const scale = newDist / pinchStartDist;
                    setZoom(zoomBaseAtTouch * scale);
                }
            }
        }, { passive: false });

        host.addEventListener('touchend', () => {
            pinchStartDist  = 0;
            zoomBaseAtTouch = zoomCurrent;
        }, { passive: true });

        host.addEventListener('touchcancel', () => {
            pinchStartDist  = 0;
            zoomBaseAtTouch = zoomCurrent;
        }, { passive: true });
    }

    // Low-light watchdog. Pulses the flash button (CSS class
    // .is-suggest) when ambient luminance has been dim for a while
    // AND the device has a torch AND the operator hasn't already
    // turned the torch on. We NEVER auto-toggle the torch — some
    // operators prefer to keep it off for spectator-comfort reasons,
    // and silently flipping a strobe on them is exactly the wrong UX.
    function startLowLightWatchdog() {
        if (lowLightTimer) return; // idempotent
        lowLightTimer = setInterval(() => {
            try {
                const $btn = document.getElementById('flashBtn');
                if (!$btn) return;
                // If flash is already on, or device doesn't support it,
                // clear the suggest state and bail.
                if (!capability.torch || flashOn) {
                    $btn.classList.remove('is-suggest');
                    return;
                }
                // <60 on the 0–255 scale reads as genuinely dim, not
                // just dim-but-readable. Tuned empirically against an
                // iPhone front-camera-as-test-device baseline.
                if (lastLuminance < 60) {
                    $btn.classList.add('is-suggest');
                } else {
                    $btn.classList.remove('is-suggest');
                }
            } catch (_) {}
        }, 1000);
    }

    // Single entry-point each decode path calls once the camera is
    // live. Refreshes capabilities, wires zoom / pinch if available,
    // starts the low-light watchdog if torch is available.
    function setupCapabilityFeatures(track, hostEl) {
        if (!track) return;
        updateCapabilities(track);
        if (capability.zoom && hostEl) attachPinchZoom(hostEl);
        // Torch watchdog runs always (cheap interval); the per-tick
        // guard inside handles the missing-capability case.
        startLowLightWatchdog();
    }

    /**
     * Stop every track on a stream and null out activeTrack if it
     * matches. Used by every null-return path below so we never leak
     * a live camera handle into the next bootstrap path. Without
     * this, on iPhones especially, a half-failed Path A would leave
     * the camera held open and Paths B/C would either hang or fail
     * with NotReadableError — i.e. "camera doesn't start at all".
     */
    function releaseStream(stream) {
        if (!stream) return;
        try {
            stream.getTracks().forEach((t) => {
                try { t.stop(); } catch (_) {}
                if (activeTrack === t) activeTrack = null;
            });
        } catch (_) {}
    }

    async function startNativeBarcodeDetector() {
        if (!('BarcodeDetector' in window)) return null;
        let supported;
        try { supported = await BarcodeDetector.getSupportedFormats(); }
        catch (_) { return null; }
        if (!supported || !supported.includes('qr_code')) return null;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;
        try {
            // Initial constraints are STANDARD ones only. Non-standard
            // hints (focusMode / exposureMode / whiteBalanceMode) used
            // to live here, but iOS Safari / some Android builds will
            // reject the whole getUserMedia call if they don't
            // recognize a constraint name — exactly the "camera
            // doesn't start at all" symptom we hit. We nudge those
            // post-start via track.applyConstraints({ advanced: ... })
            // where rejection is harmless.
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width:     { ideal: 1920 },
                    height:    { ideal: 1080 },
                    frameRate: { ideal: 60 },
                },
                audio: false,
            });

            const track = stream.getVideoTracks()[0];
            if (!track) {
                releaseStream(stream);
                return null;
            }
            activeTrack = track;

            // Mount the live video into the existing #qr-reader slot.
            // Order is intentional: the <video> element MUST be in the
            // DOM (and have its inline-attributes set) BEFORE we assign
            // srcObject. Some Safari versions silently drop srcObject
            // assignments on detached <video> nodes — that was the
            // suspected cause of "camera area stays black" on iPhone.
            const reader = document.getElementById('qr-reader');
            reader.innerHTML = '';
            const video = document.createElement('video');
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.muted    = true;
            video.autoplay = true;
            Object.assign(video.style, {
                width:     '100%',
                height:    '100%',
                objectFit: 'cover',
                display:   'block',
            });
            reader.appendChild(video);     // attach FIRST
            video.srcObject = stream;      // then bind stream
            try { await video.play(); } catch (_) {}

            // Post-start: nudge continuous focus / exposure / white-
            // balance + close focus distance. Failure is harmless —
            // we just degrade to whatever the camera ships with.
            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                });
            } catch (_) {}

            // Stage 2 — wire capability-gated features (torch suggest,
            // pinch-to-zoom) on top of the native decode path.
            try { setupCapabilityFeatures(track, video); } catch (_) {}

            let detector;
            try {
                detector = new BarcodeDetector({ formats: ['qr_code'] });
            } catch (_) {
                // Some browsers expose `BarcodeDetector` as a property
                // but throw on construction. Bail cleanly.
                releaseStream(stream);
                return null;
            }

            let stopped = false;
            let paused  = false;

            const schedule = (fn) => {
                if (typeof video.requestVideoFrameCallback === 'function') {
                    video.requestVideoFrameCallback(() => fn());
                } else {
                    requestAnimationFrame(fn);
                }
            };

            const tick = async () => {
                if (stopped) return;
                if (paused || sheetOpen || video.readyState < 2) {
                    schedule(tick);
                    return;
                }
                try {
                    const codes = await detector.detect(video);
                    if (codes && codes.length) {
                        // Prefer the largest QR by bounding-box (closest
                        // to the camera if there are accidentally
                        // multiple in frame).
                        let best = codes[0];
                        if (codes.length > 1) {
                            const area = (b) => (b && b.width && b.height) ? b.width * b.height : 0;
                            for (const c of codes) {
                                if (area(c.boundingBox) > area(best.boundingBox)) best = c;
                            }
                        }
                        if (best && best.rawValue) onScanSuccess(best.rawValue);
                    }
                } catch (_) { /* per-frame errors are transient */ }
                schedule(tick);
            };
            schedule(tick);

            return {
                pause:  () => { paused  = true; },
                resume: () => { paused  = false; },
                stop:   () => {
                    stopped = true;
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            // Anything we didn't anticipate — release and let the next
            // bootstrap path try a clean getUserMedia.
            releaseStream(stream);
            return null;
        }
    }

    /* ============================================================
       Path B — ZXing-js driven by a MANUAL frame loop
       (the iPhone-Safari path)

       iOS Safari does not expose BarcodeDetector in production
       builds. Before scanner engine v5, iPhone Safari fell all the
       way through to Path C (jsQR) which is the LEAST tolerant
       engine of the three. That gap is the single biggest reason
       the scanner felt slower than Google Lens / iOS native.

       The previous attempt to wire ZXing (PR #72) used
       BrowserMultiFormatReader.decodeFromVideoElement, which awaits
       a 'playing' / 'loadeddata' event-state that iPhone Safari
       does not reliably fire — the symptom was a stuck loading
       spinner and "camera doesn't start at all". PR #74 disabled
       Path B entirely as the safe response.

       Scanner engine v5 brings Path B back, but built on a MANUAL
       requestVideoFrameCallback loop instead of decodeFromVideoElement.
       We:
         - own the <video> mount (same DOM-attach-before-srcObject
           sequence as Path A so Safari doesn't drop the stream),
         - draw each frame to an OffscreenCanvas / fallback canvas,
         - hand the canvas to ZXing.MultiFormatReader.decode() with
           TRY_HARDER + ALSO_INVERTED hints,
         - gate decoded text through the shared onScanSuccess()
           funnel.

       Because we never await media event states, the PR #72 hang is
       structurally impossible.

       If anything fails (no ZXing global, no MultiFormatReader, no
       getUserMedia, etc.) we fall through to Path C cleanly.
       ============================================================ */
    async function startZXing() {
        // Cheap pre-checks before we ever touch the camera. The
        // installed @zxing/library UMD must expose either
        // MultiFormatReader (used in the manual loop) or at minimum
        // a usable Reader+BinaryBitmap+HybridBinarizer surface.
        if (typeof ZXing === 'undefined') return null;
        if (!ZXing.MultiFormatReader || !ZXing.BinaryBitmap ||
            !ZXing.HybridBinarizer || !ZXing.RGBLuminanceSource) return null;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;

        try {
            // STANDARD constraints only at getUserMedia time. See the
            // long comment in startNativeBarcodeDetector — non-standard
            // mode hints used to live here and were the prime suspect
            // for the iOS Safari "camera doesn't start at all"
            // regression. Post-start applyConstraints handles them.
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width:     { ideal: 1920 },
                    height:    { ideal: 1080 },
                    // 30fps is plenty for the ZXing decode loop and
                    // keeps thermal load reasonable on iPhones.
                    frameRate: { ideal: 30 },
                },
                audio: false,
            });

            const track = stream.getVideoTracks()[0];
            if (!track) {
                releaseStream(stream);
                return null;
            }
            activeTrack = track;

            // Same DOM-attach-before-srcObject ordering as Path A —
            // Safari can drop srcObject on detached <video> nodes.
            const reader = document.getElementById('qr-reader');
            reader.innerHTML = '';
            const video = document.createElement('video');
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.muted    = true;
            video.autoplay = true;
            Object.assign(video.style, {
                width:     '100%',
                height:    '100%',
                objectFit: 'cover',
                display:   'block',
            });
            reader.appendChild(video);
            video.srcObject = stream;
            // play() can reject on Safari if anything is off — we
            // catch it and continue. rVFC will simply not fire until
            // the video is actually playing, which is a self-healing
            // wait rather than a hang.
            try { await video.play(); } catch (_) {}

            // Post-start advanced constraints. Failure is harmless.
            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                });
            } catch (_) {}

            // ZXing hints — QR-only + TRY_HARDER + ALSO_INVERTED.
            // TRY_HARDER tells ZXing to spend extra cycles per frame
            // checking rotated / partial / damaged QRs; ALSO_INVERTED
            // adds a second pass on a colour-inverted bitmap so light-
            // QR-on-dark-background tickets decode without forcing
            // the operator to bring up brightness.
            let hints = null;
            try {
                hints = new Map();
                if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
                    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
                    hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                    if ('ALSO_INVERTED' in ZXing.DecodeHintType) {
                        hints.set(ZXing.DecodeHintType.ALSO_INVERTED, true);
                    }
                }
            } catch (_) { hints = null; }

            let mfr;
            try {
                mfr = new ZXing.MultiFormatReader();
                if (hints) mfr.setHints(hints);
            } catch (_) {
                releaseStream(stream);
                return null;
            }

            // Frame canvas — we draw the <video> into this on every
            // rVFC tick and hand the pixel buffer to ZXing. We size
            // it once at the first decode (when the video has real
            // intrinsic dimensions); resizing the canvas every frame
            // would thrash the GPU.
            const canvas = document.createElement('canvas');
            const ctx    = canvas.getContext('2d', { willReadFrequently: true });

            let stopped = false;
            let paused  = false;

            const schedule = (fn) => {
                if (typeof video.requestVideoFrameCallback === 'function') {
                    video.requestVideoFrameCallback(() => fn());
                } else {
                    requestAnimationFrame(fn);
                }
            };

            // Stage 2 — wire capability-gated features (torch suggest,
            // pinch-to-zoom) on top of Path B. Has to come AFTER
            // applyConstraints so getCapabilities() reflects the
            // settled track state.
            try { setupCapabilityFeatures(track, video); } catch (_) {}

            // Miss-recovery state machine — we only spend extra CPU
            // on the enhanced passes (threshold / invert / center-ROI
            // upscale) when the fast normal pass has been MISSING for
            // a while. Steady-state scan-and-go workflows keep the
            // CPU footprint the same as scanner engine v5.
            let lastSuccessAt   = Date.now();
            let lastLumSampleAt = 0;
            let autoZoomActive  = false;

            // Decode budget — we cap each ZXing.decode() call to one
            // attempt per frame. On iPhone Safari this is ~30 attempts
            // per second which is more than enough; TRY_HARDER does
            // the heavy lifting per attempt. Miss-recovery passes
            // (Stage 2 item C) add 2–3 extra decode calls per frame
            // BUT ONLY when the fast normal pass missed AND we've
            // been missing for longer than a per-pass threshold.
            const tick = () => {
                if (stopped) return;
                if (paused || sheetOpen || video.readyState < 2) {
                    schedule(tick);
                    return;
                }

                try {
                    const vw = video.videoWidth | 0;
                    const vh = video.videoHeight | 0;
                    if (vw > 0 && vh > 0) {
                        // Lazy-size the canvas to the video's native
                        // intrinsic resolution. Bigger = better
                        // small-QR decode; we cap at 1280 on the long
                        // edge to keep CPU sane on older phones.
                        const cap = 1280;
                        let cw = vw, ch = vh;
                        if (Math.max(vw, vh) > cap) {
                            const scale = cap / Math.max(vw, vh);
                            cw = Math.round(vw * scale);
                            ch = Math.round(vh * scale);
                        }
                        if (canvas.width !== cw || canvas.height !== ch) {
                            canvas.width  = cw;
                            canvas.height = ch;
                        }
                        ctx.drawImage(video, 0, 0, cw, ch);
                        const img = ctx.getImageData(0, 0, cw, ch);
                        const luminances = packLuminance(img.data);

                        const now    = Date.now();
                        const missMs = now - lastSuccessAt;

                        // Pass 1 — fast normal decode. ZXing already
                        // runs an internal HybridBinarizer so most
                        // well-lit, well-framed QRs resolve here.
                        let result = tryZXingDecode(luminances, cw, ch, mfr, hints);

                        // Sample mean luminance once every ~750ms.
                        // The low-light watchdog reads `lastLuminance`
                        // from this. Sampling per-frame would be
                        // wasteful and would flicker the suggest UI.
                        if (now - lastLumSampleAt > 750) {
                            lastLuminance   = meanLuminance(luminances);
                            lastLumSampleAt = now;
                        }

                        // Pass 2 — adaptive threshold. Triggered after
                        // >=250ms of misses. Helps WhatsApp-compressed
                        // QR screenshots whose whites have drifted
                        // grey, and dim-but-not-dark frames where the
                        // HybridBinarizer's local threshold is off.
                        if (!result && missMs > 250) {
                            const mean = meanLuminance(luminances);
                            const thr  = thresholdLuminance(luminances, mean);
                            result = tryZXingDecode(thr, cw, ch, mfr, hints);
                        }

                        // Pass 3 — inverted. Triggered after >=400ms
                        // of misses. Covers light-QR-on-dark-print
                        // tickets that the ALSO_INVERTED hint can
                        // sometimes still miss when combined with
                        // a difficult angle.
                        if (!result && missMs > 400) {
                            const inv = invertLuminance(luminances);
                            result = tryZXingDecode(inv, cw, ch, mfr, hints);
                        }

                        // Pass 4 — center ROI 2× upscale. Triggered
                        // after >=600ms of misses. Helps when the QR
                        // is held farther from the camera and is
                        // simply too small for ZXing to lock on at
                        // the native resolution.
                        if (!result && missMs > 600) {
                            const up = centerRoiUpscale(luminances, cw, ch);
                            result = tryZXingDecode(up, cw, ch, mfr, hints);
                        }

                        // Auto-zoom recovery — after >=1000ms of
                        // misses AND track exposes a usable zoom
                        // capability AND we're not already mid-bump,
                        // try a 2× zoom (clamped to capability) for
                        // ~1.6s. Reverts to 1× if still no decode.
                        // Idempotent: autoZoomActive guards against
                        // re-firing while a bump is in progress.
                        if (!result && capability.zoom && !autoZoomActive &&
                            missMs > 1000 && Math.abs(zoomCurrent - 1) < 0.05) {
                            autoZoomActive = true;
                            const target = Math.min(2, capability.zoomMax);
                            setZoom(target).then((ok) => {
                                if (!ok) {
                                    autoZoomActive = false;
                                    return;
                                }
                                setTimeout(() => {
                                    autoZoomActive = false;
                                    // Only revert to 1× if we still
                                    // haven't decoded — if a scan
                                    // landed at 2×, the operator
                                    // gets to keep the zoom.
                                    if (Date.now() - lastSuccessAt > 800) {
                                        setZoom(capability.zoomMin);
                                    }
                                }, 1600);
                            });
                        }

                        if (result) {
                            let text = '';
                            try { text = (typeof result.getText === 'function') ? result.getText() : (result.text || ''); }
                            catch (_) { text = ''; }
                            if (text) {
                                lastSuccessAt = now;
                                onScanSuccess(text);
                            }
                        }
                    }
                } catch (_) { /* per-frame errors are transient */ }

                schedule(tick);
            };
            schedule(tick);

            return {
                // Same shape as Path A / Path C so showSheet / hideSheet
                // can pause/resume transparently across paths.
                pause:  () => { paused  = true; },
                resume: () => { paused  = false; },
                stop:   () => {
                    stopped = true;
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            // Anything we didn't anticipate — tear down everything and
            // let Path C try a clean start.
            releaseStream(stream);
            return null;
        }
    }

    /* ============================================================
       Path C — html5-qrcode last-resort fallback (jsQR)

       Used when neither BarcodeDetector nor ZXing is usable. Tuned
       in PR #70 (reliability v2): full-bleed qrbox, 30fps, continuous
       focus / exposure / white-balance.
       ============================================================ */
    function startHtml5QrcodeFallback() {
        const qr = new Html5Qrcode('qr-reader', {
            verbose: false,
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            formatsToSupport: (window.Html5QrcodeSupportedFormats && [
                Html5QrcodeSupportedFormats.QR_CODE,
            ]) || undefined,
        });

        const qrbox = (vw, vh) => {
            // Push the box out to ~95% of the shorter side so tilted /
            // partially-framed QRs stay inside the decode region.
            const side = Math.floor(Math.min(vw, vh) * 0.95);
            return {
                width:  Math.max(240, Math.min(side, 600)),
                height: Math.max(240, Math.min(side, 600)),
            };
        };

        const config = {
            fps: 30,
            qrbox: qrbox,
            aspectRatio: 1.0,
            disableFlip: false,
            rememberLastUsedCamera: true,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1920 },
                height:     { ideal: 1080 },
                frameRate:  { ideal: 30 },
                focusMode:        'continuous',
                exposureMode:     'continuous',
                whiteBalanceMode: 'continuous',
                advanced: [
                    { focusMode: 'continuous' },
                    { focusMode: 'continuous-picture' },
                    { focusDistance: { ideal: 0.05 } },
                    { exposureMode: 'continuous' },
                    { whiteBalanceMode: 'continuous' },
                ],
            },
        };

        return qr.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            /* onScanFailure */ () => {}
        ).then(() => {
            qrInstance = qr;
            $loading.classList.add('is-hidden');
            try {
                const v = document.querySelector('#qr-reader video');
                if (v) {
                    v.setAttribute('playsinline', 'true');
                    v.setAttribute('webkit-playsinline', 'true');
                    v.muted = true;
                    // Expose the live track for the flash button.
                    try {
                        const s = v.srcObject;
                        if (s && s.getVideoTracks) {
                            activeTrack = s.getVideoTracks()[0] || null;
                        }
                    } catch (_) {}
                    // Stage 2 — wire capability-gated features (torch
                    // suggest, pinch-to-zoom) on top of Path C. Same
                    // entry point as Path A / Path B so the operator
                    // gets uniform UX regardless of which engine
                    // bootstrapped.
                    try { setupCapabilityFeatures(activeTrack, v); } catch (_) {}
                }
            } catch (_) {}
            try {
                qr.applyVideoConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                }).catch(() => {});
            } catch (_) {}
            return qr;
        });
    }

    /* ============================================================
       Bootstrap — Path A → Path B → Path C

       Scanner engine v5 re-enables Path B (ZXing) in the chain, but
       this time it's built on a manual requestVideoFrameCallback
       loop rather than ZXing's BrowserMultiFormatReader.decodeFrom-
       VideoElement (the PR #72 trap that hung on iPhone Safari).
       Because we never await media event states, the hang is
       structurally impossible.

       Each path returns either a controls object (`{pause, resume,
       stop, track}`) or null. The bootstrap walks them in priority
       order and keeps the first non-null result. Every null-return
       path is responsible for releasing its own camera stream so
       the next path gets a clean getUserMedia.

       SAFETY NET: a 6s timeout that force-dismisses the loading
       overlay and shows a clear error if NO path called us back.
       Combined with the global error handlers further down, this
       guarantees the operator never sees a stuck spinner.
       ============================================================ */
    let scannerStarted = false;

    function markScannerReady() {
        scannerStarted = true;
        $loading.classList.add('is-hidden');
        setStatus(tt('adm_scanner_ready', 'جاهز للفحص'), 'ready');
    }

    function markScannerFailed() {
        if (scannerStarted) return;
        scannerStarted = true;
        $loading.classList.add('is-hidden');
        setStatus(tt('adm_scanner_camera_err', '⚠️ تعذّر تشغيل الكاميرا'), 'error');
    }

    // Hard ceiling — even if every promise hangs, we WILL release the
    // UI back to the operator after this many ms. 6s is generous
    // enough to cover slow camera startup on older phones but short
    // enough that a stuck spinner is never the operator's experience.
    const BOOTSTRAP_TIMEOUT_MS = 6000;
    const bootstrapTimeoutId = setTimeout(markScannerFailed, BOOTSTRAP_TIMEOUT_MS);

    // Last-line-of-defense: if anything throws from inside an async
    // path that we forgot to wrap, dismiss the loading state so the
    // page is at least usable. The errors themselves are already
    // surfaced in DevTools — we only intervene in the visible UI.
    window.addEventListener('error', markScannerFailed);
    window.addEventListener('unhandledrejection', markScannerFailed);

    (async () => {
        // Path A — native BarcodeDetector (Android Chrome / Edge,
        // and iOS 17+ Safari with the experimental flag enabled).
        try {
            const native = await startNativeBarcodeDetector();
            if (native) {
                qrInstance = native;
                clearTimeout(bootstrapTimeoutId);
                markScannerReady();
                return;
            }
        } catch (_) { /* fall through */ }

        // Path B — ZXing-js driven by a manual frame loop. This is
        // the iPhone Safari fast-path. Same engine grade as
        // professional event-entry scanners; dramatically more
        // tolerant of tilt / partial framing / dim / damaged QRs
        // than jsQR.
        try {
            const zx = await startZXing();
            if (zx) {
                qrInstance = zx;
                clearTimeout(bootstrapTimeoutId);
                markScannerReady();
                return;
            }
        } catch (_) { /* fall through */ }

        // Path C — html5-qrcode (jsQR). Final fallback for browsers
        // that expose neither BarcodeDetector nor a usable ZXing
        // surface (very old / minimal builds). Proven flow we
        // shipped from PR #69 through PR #71.
        try {
            await startHtml5QrcodeFallback();
            clearTimeout(bootstrapTimeoutId);
            // startHtml5QrcodeFallback hides the loading overlay
            // itself in its .then() handler. Just sync our state
            // flag so the safety timeout doesn't re-fire.
            scannerStarted = true;
        } catch (_) {
            clearTimeout(bootstrapTimeoutId);
            markScannerFailed();
        }
    })();

    /* ============================================================
       Flash / restart controls
       ============================================================ */
    let flashOn = false;
    const $flashBtn = document.getElementById('flashBtn');
    $flashBtn.addEventListener('click', async () => {
        try {
            // The flash control sits on the live MediaStreamTrack —
            // the same track in both the native and html5-qrcode paths.
            if (!activeTrack || !activeTrack.applyConstraints) {
                alert(tt('adm_scanner_no_torch', 'الفلاش غير مدعوم'));
                return;
            }
            const caps = activeTrack.getCapabilities ? activeTrack.getCapabilities() : {};
            if (!('torch' in caps)) {
                alert(tt('adm_scanner_no_torch', 'الفلاش غير مدعوم'));
                return;
            }
            flashOn = !flashOn;
            await activeTrack.applyConstraints({ advanced: [{ torch: flashOn }] });
            $flashBtn.classList.toggle('is-on', flashOn);
            // Once the operator interacts with the torch, suppress the
            // low-light suggest pulse for this session — they've made
            // their preference clear and we shouldn't keep nagging.
            $flashBtn.classList.remove('is-suggest');
        } catch (_) {
            alert(tt('adm_scanner_no_torch', 'الفلاش غير مدعوم'));
            flashOn = false;
            $flashBtn.classList.remove('is-on');
            $flashBtn.classList.remove('is-suggest');
        }
    });
    document.getElementById('restartBtn').addEventListener('click', () => location.reload());
})();
</script>
@endsection
