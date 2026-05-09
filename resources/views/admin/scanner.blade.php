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

        {{-- Attendee --}}
        <div class="scan-sheet-name" id="scan-sheet-title" data-scan-name>—</div>
        <div class="scan-sheet-ref" data-scan-ref></div>

        {{-- Show / showtime --}}
        <div class="scan-sheet-row">
            <span class="scan-sheet-row-icon" aria-hidden="true">🎭</span>
            <span class="scan-sheet-row-text" data-scan-show>—</span>
        </div>
        <div class="scan-sheet-row">
            <span class="scan-sheet-row-icon" aria-hidden="true">🕒</span>
            <span class="scan-sheet-row-text" data-scan-when>—</span>
        </div>

        {{-- Section + seat chips --}}
        <div class="scan-sheet-section" data-scan-section-row hidden>
            <span class="scan-sheet-section-label" data-i18n="adm_scanner_section_label">
                القاعة
            </span>
            <span class="scan-sheet-section-chips" data-scan-section-chips></span>
        </div>

        <div class="scan-sheet-seats" data-scan-seats-row hidden>
            <span class="scan-sheet-seats-label">
                <span data-i18n="adm_scanner_seats_label">المقاعد</span>
                <span data-scan-seat-count></span>
            </span>
            <div class="scan-sheet-seats-list" data-scan-seats-list></div>
        </div>

        {{-- Already-scanned note --}}
        <div class="scan-sheet-used-note" data-scan-used-note hidden>
            <span aria-hidden="true">⚠️</span>
            <span data-i18n="adm_scanner_used_note">
                هذه التذكرة تم استخدامها سابقًا
            </span>
            <strong data-scan-used-time></strong>
        </div>

        {{-- Footer --}}
        <div class="scan-sheet-foot">
            <button type="button"
                    class="prism-btn-ghost text-xs"
                    data-scan-dismiss
                    data-i18n="adm_scanner_close">
                إغلاق
            </button>
            <span class="scan-sheet-cooldown" data-scan-cooldown></span>
        </div>
    </div>
</div>

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

.scan-sheet-section,
.scan-sheet-seats {
    display: grid;
    gap: 6px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--prism-border);
}
.scan-sheet-section-label,
.scan-sheet-seats-label {
    font-size: 11px;
    letter-spacing: .14em;
    color: var(--prism-text-3);
    text-transform: uppercase;
    display: inline-flex;
    gap: 6px;
}
.scan-sheet-section-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.scan-section-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
    border: 1px solid rgba(129,140,248,0.45);
    color: var(--prism-text);
}
.scan-section-chip[data-section="balcony"] {
    background: linear-gradient(135deg, rgba(192,132,252,0.20), rgba(251,191,36,0.10));
    border-color: rgba(192,132,252,0.50);
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
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-top: 4px;
}
.scan-sheet-cooldown {
    font-size: 11px;
    color: var(--prism-text-3);
    letter-spacing: .12em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.scan-sheet-cooldown::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--prism-text-3);
    animation: dotPulse 1.2s ease-in-out infinite;
}
@keyframes dotPulse {
    0%, 100% { opacity: .35; transform: scale(1);   }
    50%      { opacity: 1;   transform: scale(1.2); }
}

@media (prefers-reduced-motion: reduce) {
    .reticle-line,
    .scan-sheet-cooldown::before,
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
    // Cooldown = how long before the SAME code can be re-scanned. The
    // result sheet auto-dismisses on this same window so the operator
    // can immediately scan the next ticket without tapping anything.
    const COOLDOWN_MS = 2400;
    const SHEET_DISMISS_MS = 2400;

    let busy = false;
    let lastCode = null;
    let lastScanTime = 0;
    let dismissTimer = null;
    let cooldownTimer = null;

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
    const $sheetSecRow   = $sheet.querySelector('[data-scan-section-row]');
    const $sheetSecChips = $sheet.querySelector('[data-scan-section-chips]');
    const $sheetSeatRow  = $sheet.querySelector('[data-scan-seats-row]');
    const $sheetSeatList = $sheet.querySelector('[data-scan-seats-list]');
    const $sheetSeatCnt  = $sheet.querySelector('[data-scan-seat-count]');
    const $sheetUsedNote = $sheet.querySelector('[data-scan-used-note]');
    const $sheetUsedTime = $sheet.querySelector('[data-scan-used-time]');
    const $sheetCooldown = $sheet.querySelector('[data-scan-cooldown]');
    const $sheetDismiss  = $sheet.querySelector('[data-scan-dismiss]');

    function sectionLabel(s) {
        if (!s) return '';
        const k = ('' + s).toLowerCase();
        if (k === 'hall')    return tt('section_hall',    'الصالة');
        if (k === 'balcony') return tt('section_balcony', 'البلكون');
        return s;
    }

    function showSheet(result, payload) {
        clearTimeout(dismissTimer);
        clearInterval(cooldownTimer);

        $sheet.dataset.result = result; // ok | used | error
        $sheet.dataset.state  = 'visible';

        // Badge text + icon
        const badgeText =
            result === 'ok'   ? tt('adm_scanner_ok',     '✅ دخول مسموح') :
            result === 'used' ? tt('adm_scanner_used',   '⚠️ مستخدمة')   :
                                tt('adm_scanner_invalid','❌ غير صالح');
        $sheetBadge.textContent = badgeText.replace(/^[^\p{L}\p{N}]+\s*/u, '');
        $sheetIcon.textContent  = result === 'ok' ? '✓' : result === 'used' ? '!' : '✕';

        const p = payload || {};

        // Attendee
        $sheetName.textContent = p.name || tt('adm_scanner_unknown_name', '—');
        $sheetRef.textContent  = p.reference ? '#' + p.reference : '';

        // Show + when
        $sheetShow.textContent = p.show_title || '—';
        const dateStr = p.date || '';
        const timeStr = p.time || '';
        $sheetWhen.textContent = [dateStr, timeStr].filter(Boolean).join(' · ') || '—';

        // Sections
        const sections = Array.isArray(p.sections) ? p.sections : [];
        if (sections.length) {
            $sheetSecChips.innerHTML = '';
            sections.forEach((sec) => {
                const chip = document.createElement('span');
                chip.className = 'scan-section-chip';
                chip.dataset.section = ('' + sec).toLowerCase();
                chip.textContent = sectionLabel(sec);
                $sheetSecChips.appendChild(chip);
            });
            $sheetSecRow.hidden = false;
        } else {
            $sheetSecRow.hidden = true;
        }

        // Seats
        const seats = Array.isArray(p.seats) ? p.seats : [];
        if (seats.length) {
            $sheetSeatList.innerHTML = '';
            seats.forEach((s) => {
                const chip = document.createElement('span');
                chip.className = 'scan-seat-chip';
                chip.textContent = s.label || (s.row_letter + s.seat_number);
                $sheetSeatList.appendChild(chip);
            });
            $sheetSeatCnt.textContent = ' · ' + seats.length;
            $sheetSeatRow.hidden = false;
        } else {
            $sheetSeatCnt.textContent = '';
            $sheetSeatRow.hidden = true;
        }

        // Used note
        if (result === 'used' && p.scanned_at) {
            $sheetUsedNote.hidden = false;
            $sheetUsedTime.textContent = ' · ' + p.scanned_at;
        } else {
            $sheetUsedNote.hidden = true;
            $sheetUsedTime.textContent = '';
        }

        // Cooldown countdown — visible signal that the next scan
        // will be unlocked, so the operator knows when to move on.
        const start = Date.now();
        const updateCooldown = () => {
            const remaining = Math.max(0, SHEET_DISMISS_MS - (Date.now() - start));
            const secs = (remaining / 1000).toFixed(1);
            $sheetCooldown.textContent = tt('adm_scanner_next_in', 'التالية خلال') + ' ' + secs + 's';
            if (remaining <= 0) clearInterval(cooldownTimer);
        };
        updateCooldown();
        cooldownTimer = setInterval(updateCooldown, 100);

        dismissTimer = setTimeout(hideSheet, SHEET_DISMISS_MS);
    }

    function hideSheet() {
        clearTimeout(dismissTimer);
        clearInterval(cooldownTimer);
        $sheet.dataset.state = 'hidden';
        // Reset stage glow back to ready when the sheet leaves.
        delete $stage.dataset.state;
        setStatus(tt('adm_scanner_ready', 'جاهز للفحص'), 'ready');
    }
    $sheetDismiss.addEventListener('click', hideSheet);
    // Tap outside the card to dismiss.
    $sheet.addEventListener('click', (e) => {
        if (e.target === $sheet) hideSheet();
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
       html5-qrcode bootstrap with reliability tuning
       ============================================================ */
    // BarcodeDetector — when supported (iOS 17+, Android Chrome) — runs
    // QR decoding off the main thread and is dramatically faster than
    // the jsQR fallback. html5-qrcode picks it up via the experimental
    // flag; everything else degrades to jsQR with no code changes.
    const qr = new Html5Qrcode('qr-reader', {
        verbose: false,
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
        formatsToSupport: (window.Html5QrcodeSupportedFormats && [
            Html5QrcodeSupportedFormats.QR_CODE,
        ]) || undefined,
    });

    // Dynamic qrbox: ~70% of the shorter camera frame side, capped so
    // the decoder doesn't process more pixels than it has to. Larger
    // box = more tolerance for tilted/partial QRs. The function form
    // is re-evaluated on every frame, so the box scales with viewport.
    const qrbox = (vw, vh) => {
        const side = Math.floor(Math.min(vw, vh) * 0.70);
        return { width: Math.max(220, Math.min(side, 480)), height: Math.max(220, Math.min(side, 480)) };
    };

    const config = {
        fps: 24,
        qrbox: qrbox,
        aspectRatio: 1.0,
        disableFlip: false,
        rememberLastUsedCamera: true,
        videoConstraints: {
            facingMode: { ideal: 'environment' },
            // High resolution helps small / far QR codes resolve. The
            // browser will downscale if the device can't deliver.
            width:  { ideal: 1920 },
            height: { ideal: 1080 },
            // Continuous focus tracks the QR as the operator moves.
            // Some Android devices honor `focusMode`, others use the
            // advanced array; we set both for max coverage.
            focusMode: 'continuous',
            advanced: [
                { focusMode: 'continuous' },
                { focusMode: 'continuous-picture' },
                { focusDistance: { ideal: 0.05 } },
            ],
        },
    };

    function onScanSuccess(text /*, decodedResult */) {
        const now = Date.now();
        if (text === lastCode && now - lastScanTime < COOLDOWN_MS) return;
        if (busy) return;
        busy = true;
        lastCode = text;
        lastScanTime = now;
        setStatus(tt('adm_scanner_processing', '⏳ جارٍ التحقق'), 'scanning');
        check(text);
    }
    function onScanFailure(/* err */) { /* silent — we run continuously */ }

    qr.start({ facingMode: 'environment' }, config, onScanSuccess, onScanFailure)
        .then(() => {
            $loading.classList.add('is-hidden');
            // Try to upgrade autofocus once the stream is live; some
            // Android browsers only honor `applyConstraints` after the
            // track is active, not in `videoConstraints`.
            try {
                qr.applyVideoConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                    ],
                }).catch(() => {});
            } catch (_) {}
        })
        .catch(() => {
            $loading.classList.add('is-hidden');
            setStatus(tt('adm_scanner_camera_err', '⚠️ تعذّر تشغيل الكاميرا'), 'error');
        });

    /* ============================================================
       Flash / restart controls
       ============================================================ */
    let flashOn = false;
    const $flashBtn = document.getElementById('flashBtn');
    $flashBtn.addEventListener('click', async () => {
        try {
            flashOn = !flashOn;
            await qr.applyVideoConstraints({ advanced: [{ torch: flashOn }] });
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
