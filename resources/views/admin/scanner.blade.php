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
         a native BarcodeDetector). Pinned to the same major
         version everywhere.

      3. html5-qrcode (jsQR) — last-resort fallback if neither of
         the above is available. Kept for safety only.
--}}
<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

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
    background: var(--prism-glass);
    border: 1px solid var(--prism-border);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
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
    const COOLDOWN_MS = 1500;

    let busy = false;          // mid-flight backend round-trip
    let lastCode = null;
    let lastScanTime = 0;
    let sheetOpen = false;     // pause scans while showing a result
    let qrInstance = null;     // assigned once html5-qrcode boots

    /* ============================================================
       Audio + haptic feedback
       ============================================================ */
    let audioCtx = null;
    function beep(type) {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
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
    function vibrate(type) {
        if (!('vibrate' in navigator)) return;
        if      (type === 'ok')   navigator.vibrate(120);
        else if (type === 'used') navigator.vibrate([100, 50, 100]);
        else                       navigator.vibrate(220);
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
       Path B — ZXing-js (the iPhone-Safari path)

       iOS Safari does not expose BarcodeDetector by default, so on
       iPhones we previously fell all the way through to html5-qrcode
       + jsQR. ZXing-js is the same engine many professional event-
       entry scanners use and is dramatically more tolerant of tilt /
       distance / partial framing / low-light than jsQR.

       We do our own getUserMedia + <video> mount (matching Path A's
       capture setup) and then hand the live <video> element to
       ZXing.BrowserMultiFormatReader, with TRY_HARDER + ALSO_INVERTED
       hints and a tight scan interval. ZXing manages the decode loop
       internally; we just gate results on sheetOpen / lastCode in
       the shared onScanSuccess() funnel.

       If anything fails (no ZXing global, no getUserMedia, no
       camera, etc.) we fall through to Path C (html5-qrcode).
       ============================================================ */
    async function startZXing() {
        // Cheap pre-checks before we ever touch the camera. The
        // installed @zxing/library UMD must expose BrowserMultiFormatReader
        // — if not (older / partial bundle), bail to Path C without
        // grabbing the camera at all.
        if (typeof ZXing === 'undefined' || !ZXing.BrowserMultiFormatReader) return null;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;
        let codeReader = null;
        let controls = null;

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

            // Constructor signature has shifted across ZXing versions
            // (and sometimes the `new X(hints, ms)` form throws on the
            // very first run). Try a few defensively.
            try {
                codeReader = new ZXing.BrowserMultiFormatReader(hints, 50);
            } catch (_) {
                try { codeReader = new ZXing.BrowserMultiFormatReader(hints); }
                catch (__) {
                    try { codeReader = new ZXing.BrowserMultiFormatReader(); }
                    catch (___) {
                        releaseStream(stream);
                        return null;
                    }
                }
            }
            try { codeReader.timeBetweenScansMillis      = 50; } catch (_) {}
            try { codeReader.timeBetweenDecodingAttempts = 50; } catch (_) {}

            let stopped = false;
            let paused  = false;

            // decodeFromVideoElement may not exist on older builds.
            if (typeof codeReader.decodeFromVideoElement !== 'function') {
                releaseStream(stream);
                return null;
            }

            controls = await codeReader.decodeFromVideoElement(video, (result /*, err */) => {
                if (stopped || paused || sheetOpen) return;
                if (result) {
                    let text = '';
                    try { text = (typeof result.getText === 'function') ? result.getText() : (result.text || ''); }
                    catch (_) { text = ''; }
                    if (text) onScanSuccess(text);
                }
                // Per-frame errors mean "no QR in this frame" — ignore.
            });

            return {
                // Same shape as Path A so showSheet/hideSheet can
                // pause/resume transparently across paths.
                pause:  () => { paused = true; },
                resume: () => { paused = false; },
                stop:   () => {
                    stopped = true;
                    try {
                        if (controls && typeof controls.stop === 'function') controls.stop();
                        else if (codeReader && typeof codeReader.reset === 'function') codeReader.reset();
                    } catch (_) {}
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            // Anything we didn't anticipate (decodeFromVideoElement
            // threw, applyConstraints rejected synchronously, etc.) —
            // tear down everything and let Path C try a clean start.
            try {
                if (controls && typeof controls.stop === 'function') controls.stop();
                else if (codeReader && typeof codeReader.reset === 'function') codeReader.reset();
            } catch (__) {}
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
       Bootstrap — Path A (native BarcodeDetector) -> Path C (html5-qrcode)

       Path B (ZXing-js) is intentionally DISABLED here. After PR #72
       it caused a hang on iPhone Safari: ZXing's
       BrowserMultiFormatReader.decodeFromVideoElement awaits a
       'playing' / 'loadeddata' event-state that iPhone Safari does
       not always fire. The result was the loading spinner staying
       up forever even though the camera itself was streaming. The
       startZXing() function is left in place above so this is a
       single-line re-enable once we have a way to verify it on a
       real iPhone.

       Until then we fall back through the proven Path A -> Path C
       flow we had before PR #72.

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

        // Path C — html5-qrcode (jsQR). Proven flow: this is what
        // we shipped from PR #69 through PR #71 and what iPhone
        // Safari actually used in production. Used now for
        // everything Path A can't handle.
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
        } catch (_) {
            alert(tt('adm_scanner_no_torch', 'الفلاش غير مدعوم'));
            flashOn = false;
            $flashBtn.classList.remove('is-on');
        }
    });
    document.getElementById('restartBtn').addEventListener('click', () => location.reload());
})();
</script>
@endsection
