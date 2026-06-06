{{-- ============================================================
     Wallet-only payment block (single official payment method).

     The customer pays through ONE method only: the e-wallet number.
     This partial makes that crystal clear — prominent wallet number,
     a large copy button, and strong explanatory messaging so there's
     zero ambiguity about where to transfer the money.

     Self-contained: hardcoded palette (no --prism-/--p- var dependency)
     and its own copy handler, so it renders + works identically inside
     both the main app layout and the fullscreen seat-picker layout.

     Display only — backend payment + verification logic is untouched.
     Renders nothing when no wallet number is configured.

     Optional vars:
       $amount  — booking total to surface inline (int|string|null)
       $compact — tighter spacing for narrow side panels (bool)
     ============================================================ --}}
@php
    $walletAmount  = $amount  ?? null;
    $walletCompact = $compact ?? false;
@endphp
@if (!empty($transferWallet))
@once
<style>
    .wallet-pay {
        background:
            radial-gradient(120% 120% at 50% 0%, rgba(52,211,153,0.10), transparent 60%),
            rgba(255,255,255,0.025);
        border: 1px solid rgba(52,211,153,0.35);
        border-radius: 18px;
        padding: 18px 16px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        text-align: center;
        box-shadow: 0 10px 30px -18px rgba(16,185,129,0.45);
    }
    .wallet-pay.is-compact { padding: 14px 12px; gap: 11px; border-radius: 16px; }

    .wallet-pay-method {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        align-self: center;
        font-size: 17px;
        font-weight: 800;
        line-height: 1.3;
        color: #ffffff;
        letter-spacing: .01em;
    }
    .wallet-pay.is-compact .wallet-pay-method { font-size: 15px; }

    /* Explanatory checklist — the four reassurance lines that make the
       single-method rule unmistakable. */
    .wallet-pay-points {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
        text-align: start;
    }
    .wallet-pay-points li {
        font-size: 12.5px;
        line-height: 1.55;
        font-weight: 600;
        color: #d6fae8;
    }
    .wallet-pay.is-compact .wallet-pay-points li { font-size: 11.5px; }

    /* The focal point: the wallet number itself. Large, high-contrast,
       LTR digits, generous letter-spacing so it reads cleanly on mobile. */
    .wallet-pay-number-card {
        background: rgba(8,10,20,0.55);
        border: 1px solid rgba(52,211,153,0.40);
        border-radius: 14px;
        padding: 14px 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
    .wallet-pay-number-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6ee7b7;
    }
    .wallet-pay-number {
        font-family: "Space Grotesk", ui-monospace, "SFMono-Regular", Menlo, monospace;
        font-size: clamp(26px, 8vw, 34px);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: .06em;
        color: #ffffff;
        word-break: break-all;
        direction: ltr;
        unicode-bidi: isolate;
    }
    .wallet-pay.is-compact .wallet-pay-number { font-size: clamp(22px, 7vw, 28px); }

    /* Large, finger-friendly copy button — the primary action. */
    .wallet-pay-copy {
        -webkit-tap-highlight-color: transparent;
        appearance: none;
        cursor: pointer;
        width: 100%;
        min-height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 12px;
        border: 1px solid rgba(52,211,153,0.55);
        background: linear-gradient(135deg, rgba(16,185,129,0.22), rgba(52,211,153,0.12));
        color: #eafff5;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: .01em;
        transition: transform .12s cubic-bezier(.2,.7,.2,1), background .15s ease, border-color .15s ease;
    }
    .wallet-pay-copy:hover { background: linear-gradient(135deg, rgba(16,185,129,0.30), rgba(52,211,153,0.18)); }
    .wallet-pay-copy:active { transform: scale(0.98); }
    .wallet-pay-copy .wallet-pay-copy-icon { font-size: 16px; }
    .wallet-pay-copy.is-copied {
        border-color: rgba(52,211,153,0.85);
        background: linear-gradient(135deg, rgba(16,185,129,0.40), rgba(52,211,153,0.26));
    }

    .wallet-pay-note {
        font-size: 12px;
        font-weight: 700;
        line-height: 1.5;
        color: #fcd34d;
        margin: 0;
    }
    .wallet-pay.is-compact .wallet-pay-note { font-size: 11px; }

    @media (max-width: 380px) {
        .wallet-pay { padding: 16px 12px; }
    }
</style>
<script>
    /* Self-contained copy handler for the wallet-only block. Delegated +
       guarded so it binds exactly once and works in either layout (the
       fullscreen seat-picker layout has no global [data-pt-copy] helper).
       Provides inline button feedback so it doesn't depend on a toast. */
    (function () {
        if (window.__walletCopyBound) return;
        window.__walletCopyBound = true;

        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            return new Promise(function (resolve, reject) {
                try {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.top = '-1000px';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    var ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    ok ? resolve() : reject();
                } catch (e) { reject(e); }
            });
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-wallet-copy]');
            if (!btn) return;
            e.preventDefault();
            var value = btn.getAttribute('data-wallet-copy');
            if (!value) return;
            copyText(value).then(function () {
                var labelEl = btn.querySelector('[data-wallet-copy-label]');
                var lang = document.documentElement.getAttribute('data-pt-lang') || 'ar';
                var doneText = lang === 'en' ? 'Copied ✓' : 'تم النسخ ✓';
                btn.classList.add('is-copied');
                if (labelEl) {
                    if (btn.__origLabel == null) btn.__origLabel = labelEl.textContent;
                    labelEl.textContent = doneText;
                }
                clearTimeout(btn.__copyTimer);
                btn.__copyTimer = setTimeout(function () {
                    btn.classList.remove('is-copied');
                    if (labelEl && btn.__origLabel != null) labelEl.textContent = btn.__origLabel;
                }, 1600);
            }).catch(function () { /* clipboard unavailable — number is still visible */ });
        });
    })();
</script>
@endonce
<div class="wallet-pay {{ $walletCompact ? 'is-compact' : '' }}" data-wallet-pay>
    <span class="wallet-pay-method" data-i18n="pay_wallet_method">💳 المحفظة الإلكترونية</span>

    <ul class="wallet-pay-points">
        <li data-i18n="pay_only_method">  يرجي التحويل على رقم المحفظة فقط و ليس انستاباي‼️</li>
    </ul>

    <div class="wallet-pay-number-card">
        <span class="wallet-pay-number-label" data-i18n="pay_wallet_number_label">رقم المحفظة</span>
        <span class="wallet-pay-number" dir="ltr">{{ $transferWallet }}</span>
        <button type="button"
                class="wallet-pay-copy"
                data-wallet-copy="{{ $transferWallet }}"
                data-i18n-attr="aria-label:pay_copy_number"
                aria-label="نسخ الرقم">
            <span class="wallet-pay-copy-icon" aria-hidden="true">⧉</span>
            <span data-wallet-copy-label data-i18n="pay_copy_number">نسخ الرقم</span>
        </button>
    </div>

    <p class="wallet-pay-note" data-i18n="pay_official_note">⚠️ هذا هو رقم الدفع الرسمي الوحيد للحجز</p>
</div>
@endif
