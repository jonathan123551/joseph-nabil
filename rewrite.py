import re

file_path = r"C:\Users\Jonathan-pc\.gemini\antigravity\scratch\joseph-nabil\resources\views\admin\dashboard.blade.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Replace the CSS block at the end of <style>
css_target = """    @media (prefers-reduced-motion: reduce) {
        .sta-ring-fill, .sta-stack-seg { transition: none; }
        .sta-status-chip.is-live .sta-status-dot { animation: none; }
    }

    @media (max-width: 640px) {
        .sta-ring-wrap { width: 96px; height: 96px; }
        .sta-ring-percent { font-size: 22px; }
        .sta-rev-value { font-size: 18px; }
    }
</style>"""

css_replacement = """    /* Accordion Showtime Card Layout */
    .sta-card {
        border-radius: var(--prism-radius);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    .sta-card-compact {
        padding: 16px 20px;
        background: rgba(255, 255, 255, 0.02);
        cursor: pointer;
        user-select: none;
        transition: background 0.2s var(--prism-ease);
    }
    .sta-card-compact:hover {
        background: rgba(255, 255, 255, 0.04);
    }
    .sta-card-details {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.35s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.25s ease-out;
    }
    .sta-card.is-expanded .sta-card-details {
        opacity: 1;
    }

    /* Optimized smaller chart sizing */
    .sta-ring-wrap.is-mini { width: 80px; height: 80px; }
    .sta-ring-wrap.is-mini .sta-ring-percent { font-size: 20px; }
    .sta-ring-wrap.is-mini .sta-ring-caption { font-size: 9px; margin-top: 2px; }
    .sta-stack.is-mini { height: 8px; }

    @media (prefers-reduced-motion: reduce) {
        .sta-ring-fill, .sta-stack-seg { transition: none; }
        .sta-status-chip.is-live .sta-status-dot { animation: none; }
        .sta-card-details, .sta-card { transition: none; }
    }

    @media (max-width: 640px) {
        .sta-ring-wrap { width: 96px; height: 96px; }
        .sta-ring-percent { font-size: 22px; }
        .sta-rev-value { font-size: 18px; }
        .sta-card-compact { padding: 14px 16px; }
    }
</style>"""

content = content.replace(css_target, css_replacement)

# 2. Replace the HTML structure
# We'll use regex to match the article block precisely
html_regex = r'(<article class="prism-glass prism-glow-border p-5 sm:p-6 space-y-5 prism-fade-up">.*?)(</article>)'
match = re.search(html_regex, content, flags=re.DOTALL)
if match:
    old_html = match.group(0)
    html_replacement = r'''<article class="sta-card prism-glass prism-glow-border prism-fade-up" data-showtime-id="{{ $time->id }}">
                        
                        {{-- ── 1. COMPACT HEADER (Always Visible, Clickable) ─── --}}
                        <div class="sta-card-compact">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex items-center gap-2 text-[color:var(--prism-text)]">
                                        <span aria-hidden="true">🎭</span>
                                        <span class="font-semibold text-sm sm:text-base truncate">{{ $time->show->title }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="prism-pill prism-pill-neon py-0.5 px-2 text-[10px] sm:text-[11px]">
                                            <span class="prism-dot prism-dot-emerald"></span>
                                            {{ $time->date?->format('d/m/Y') }}
                                        </span>
                                        <span class="prism-pill prism-pill-amber py-0.5 px-2 text-[10px] sm:text-[11px]">
                                            🕔 {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-[color:var(--prism-text-3)] pt-0.5">
                                        @if ($cardUsesSection)
                                            <span data-i18n="adm_sta_price_split">صالة / بلكون</span>:
                                            <span style="color: var(--prism-gold);">{{ $cardSectionLabel }} <span data-i18n="common_currency_short">ج</span></span>
                                        @else
                                            <span data-i18n="adm_times_col_price">السعر</span>:
                                            <span style="color: var(--prism-gold);">{{ $stafmt($a['ticket_price'] ?? 0) }} <span data-i18n="common_currency_short">ج</span></span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="sta-status-chip {{ $isSoldOut ? 'is-soldout' : 'is-live' }} text-[10px] py-1 px-2.5">
                                        <span class="sta-status-dot"></span>
                                        <span data-i18n="{{ $isSoldOut ? 'adm_status_sold_out' : 'adm_status_available' }}">
                                            {{ $isSoldOut ? 'Sold Out' : 'متاح' }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Compact KPIs Grid --}}
                            <div class="grid grid-cols-4 gap-2 pt-3 text-center">
                                <div class="bg-white/[0.02] border border-white/[0.04] rounded-[10px] py-1.5 px-1 flex flex-col items-center justify-center">
                                    <span class="text-[9px] text-[color:var(--prism-text-3)] mb-0.5 whitespace-nowrap" data-i18n="adm_sta_ring_caption">إشغال</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-[color:var(--prism-cyan)]" dir="ltr">{{ (int) round($occupancyPct) }}%</span>
                                </div>
                                <div class="bg-white/[0.02] border border-white/[0.04] rounded-[10px] py-1.5 px-1 flex flex-col items-center justify-center">
                                    <span class="text-[9px] text-[color:var(--prism-text-3)] mb-0.5 whitespace-nowrap" data-i18n="adm_revenue">الإيرادات</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-[color:var(--prism-emerald)] truncate max-w-full" dir="ltr">
                                        {{ $stafmt($a['approved_revenue'] ?? 0) }}<span class="text-[9px] font-normal opacity-70"> ج</span>
                                    </span>
                                </div>
                                <div class="bg-white/[0.02] border border-white/[0.04] rounded-[10px] py-1.5 px-1 flex flex-col items-center justify-center">
                                    <span class="text-[9px] text-[color:var(--prism-text-3)] mb-0.5 whitespace-nowrap" data-i18n="adm_sta_remaining">المتبقي</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-[color:var(--prism-text)]" dir="ltr">{{ $stafmt($a['remaining'] ?? 0) }}</span>
                                </div>
                                <div class="bg-white/[0.02] border border-white/[0.04] rounded-[10px] py-1.5 px-1 flex flex-col items-center justify-center">
                                    <span class="text-[9px] text-[color:var(--prism-text-3)] mb-0.5 whitespace-nowrap" data-i18n="adm_sta_approved">معتمد</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-[color:var(--prism-text)]" dir="ltr">{{ $stafmt($a['approved_tickets'] ?? 0) }}</span>
                                </div>
                            </div>
                            
                            {{-- Expand Trigger --}}
                            <div class="flex items-center justify-center gap-1.5 text-[11px] text-[color:var(--prism-text-3)] font-bold pt-3.5 opacity-80 hover:opacity-100 transition-opacity">
                                <span class="sta-expand-arrow transition-transform duration-300 text-[9px]">▼</span>
                                <span class="sta-expand-text" data-i18n="adm_sta_show_details">عرض التفاصيل</span>
                            </div>
                        </div>

                        {{-- ── 2. EXPANDABLE DETAILS ─── --}}
                        <div class="sta-card-details">
                            <div class="p-4 sm:p-5 pt-0 border-t border-[color:var(--prism-border)] border-dashed mt-1 space-y-5">
                                
                                {{-- ── occupancy ring + 4-tile breakdown ────── --}}
                                <div class="flex items-center gap-4 sm:gap-5 flex-wrap">
                                    <div class="sta-ring-wrap is-mini" aria-hidden="true">
                                        <svg viewBox="0 0 100 100">
                                            <circle class="sta-ring-track" cx="50" cy="50" r="{{ $staRingRadius }}"></circle>
                                            <circle class="sta-ring-fill"
                                                    cx="50" cy="50" r="{{ $staRingRadius }}"
                                                    stroke-dasharray="{{ $staRingCircumference }}"
                                                    stroke-dashoffset="{{ $ringOffset }}"></circle>
                                        </svg>
                                        <div class="sta-ring-center">
                                            <span class="sta-ring-percent">{{ (int) round($occupancyPct) }}%</span>
                                            <span class="sta-ring-caption" data-i18n="adm_sta_ring_caption">إشغال</span>
                                        </div>
                                    </div>
        
                                    <div class="grid grid-cols-2 gap-2 flex-1 min-w-[200px]">
                                        <div class="pt-mini-card pt-mini-card-emerald">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_approved">معتمد</div>
                                            <div class="pt-mini-card-value">{{ $stafmt($a['approved_tickets'] ?? 0) }}</div>
                                        </div>
                                        <div class="pt-mini-card pt-mini-card-gold">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_pending">قيد المراجعة</div>
                                            <div class="pt-mini-card-value">{{ $stafmt($a['pending_tickets'] ?? 0) }}</div>
                                        </div>
                                        <div class="pt-mini-card" style="border-color: rgba(251,113,133,0.32); background: rgba(244,63,94,0.06);">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_blocked">محجوب</div>
                                            <div class="pt-mini-card-value" style="color: #fda4af;">{{ $stafmt($a['blocked'] ?? 0) }}</div>
                                        </div>
                                        <div class="pt-mini-card">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_remaining">المتبقي</div>
                                            <div class="pt-mini-card-value" style="color: var(--prism-text);">
                                                {{ $stafmt($a['remaining'] ?? 0) }}
                                                <span class="text-[10px] opacity-50">/ {{ $stafmt($a['capacity'] ?? 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        
                                {{-- ── stacked progress bar + legend ───────── --}}
                                <div>
                                    <div class="sta-stack is-mini" role="img"
                                         aria-label="Approved {{ $approvedPct }}%, pending {{ $pendingPct }}%, blocked {{ $blockedPct }}%, remaining {{ $remainingPct }}%">
                                        <div class="sta-stack-seg is-approved"  style="width: {{ $approvedPct }}%;"></div>
                                        <div class="sta-stack-seg is-pending"   style="width: {{ $pendingPct }}%;"></div>
                                        <div class="sta-stack-seg is-blocked"   style="width: {{ $blockedPct }}%;"></div>
                                        <div class="sta-stack-seg is-remaining" style="width: {{ $remainingPct }}%;"></div>
                                    </div>
                                    <div class="sta-legend">
                                        <span><span class="sta-legend-dot is-approved"></span><span data-i18n="adm_sta_approved">معتمد</span></span>
                                        <span><span class="sta-legend-dot is-pending"></span><span data-i18n="adm_sta_pending">قيد المراجعة</span></span>
                                        @if(($a['blocked'] ?? 0) > 0)
                                            <span><span class="sta-legend-dot is-blocked"></span><span data-i18n="adm_sta_blocked">محجوب</span></span>
                                        @endif
                                        <span><span class="sta-legend-dot is-remaining"></span><span data-i18n="adm_sta_remaining">المتبقي</span></span>
                                    </div>
                                </div>
        
                                {{-- ── revenue split: approved vs pending ──── --}}
                                <div class="sta-rev-split">
                                    <div class="sta-rev-tile is-approved">
                                        <div class="sta-rev-label" data-i18n="adm_sta_rev_approved">إيراد مؤكد</div>
                                        <div class="sta-rev-value">
                                            {{ $stafmt($a['approved_revenue'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                        <div class="sta-rev-sub">
                                            {{ $stafmt($a['approved_bookings'] ?? 0) }}
                                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                                            · <span data-i18n="adm_sta_avg_short">متوسط</span>
                                            {{ $stafmt($a['average_booking_value'] ?? 0) }}
                                            <span data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
        
                                    <div class="sta-rev-tile is-pending">
                                        <div class="sta-rev-label" data-i18n="adm_sta_rev_pending">إيراد معلَّق</div>
                                        <div class="sta-rev-value">
                                            {{ $stafmt($a['pending_revenue'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                        <div class="sta-rev-sub">
                                            {{ $stafmt($a['pending_bookings'] ?? 0) }}
                                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                                            · <span data-i18n="adm_sta_conv_short">تحويل</span>
                                            {{ $staPct($a['conversion_percent'] ?? 0) }}
                                        </div>
                                    </div>
                                </div>
        
                                {{-- ── hall / balcony breakdown (Anba only) ─── --}}
                                @if($cardUsesSection && (($a['hall'] ?? null) || ($a['balcony'] ?? null)))
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        
                                        @php $h = $a['hall'] ?? null; @endphp
                                        @if($h)
                                            <div class="sta-section-card is-hall">
                                                <div class="sta-section-title">
                                                    <span><span data-i18n="adm_sta_section_hall">صالة</span></span>
                                                    <span class="text-[10px] opacity-80">{{ $stafmt($a['hall_price']) }} <span data-i18n="common_currency_short">ج</span></span>
                                                </div>
                                                <div class="sta-section-rows">
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_sold">تذاكر مُباعة</div>
                                                        <div class="sta-section-row-value">{{ $stafmt($h['tickets_sold']) }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_list"> الأجمالى قبل الخصومات  </div>
                                                        <div class="sta-section-row-value">{{ $stafmt($h['list_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span></div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_final">صافي الإيراد</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-emerald);">
                                                            {{ $stafmt($h['final_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_savings">الخصومات</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-gold);">
                                                            {{ $stafmt($h['discount_amount']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
        
                                        @php $b = $a['balcony'] ?? null; @endphp
                                        @if($b)
                                            <div class="sta-section-card is-balcony">
                                                <div class="sta-section-title">
                                                    <span><span data-i18n="adm_sta_section_balcony">بلكون</span></span>
                                                    <span class="text-[10px] opacity-80">{{ $stafmt($a['balcony_price']) }} <span data-i18n="common_currency_short">ج</span></span>
                                                </div>
                                                <div class="sta-section-rows">
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_sold">تذاكر مُباعة</div>
                                                        <div class="sta-section-row-value">{{ $stafmt($b['tickets_sold']) }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_list">  الأجمالى قبل الخصومات</div>
                                                        <div class="sta-section-row-value">{{ $stafmt($b['list_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span></div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_final">صافي الإيراد</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-emerald);">
                                                            {{ $stafmt($b['final_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_savings">الخصومات</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-gold);">
                                                            {{ $stafmt($b['discount_amount']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
        
                                {{-- ── advanced expandable ──────────────────── --}}
                                <div class="sta-advanced-grid border-t border-[color:var(--prism-border)] border-dashed pt-4 mt-2">
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_discount">الخصومات المُطبَّقة</div>
                                        <div class="pt-mini-card-value" style="color: var(--prism-gold);">
                                            {{ $stafmt($a['total_discount'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_discounted_count">عدد الحجوزات المخصومة</div>
                                        <div class="pt-mini-card-value">{{ $stafmt($a['discounted_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_avg">متوسط قيمة الحجز</div>
                                        <div class="pt-mini-card-value">
                                            {{ $stafmt($a['average_booking_value'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_conv">نسبة الموافقة</div>
                                        <div class="pt-mini-card-value" style="color: var(--prism-cyan);">
                                            {{ $staPct($a['conversion_percent'] ?? 0) }}
                                        </div>
                                    </div>
                                    <div class="pt-mini-card pt-mini-card-emerald">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_approved">حجوزات معتمدة</div>
                                        <div class="pt-mini-card-value">{{ $stafmt($a['approved_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card pt-mini-card-gold">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_pending">حجوزات معلَّقة</div>
                                        <div class="pt-mini-card-value">{{ $stafmt($a['pending_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card" style="border-color: rgba(251,113,133,0.32); background: rgba(244,63,94,0.06);">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_rejected">حجوزات مرفوضة</div>
                                        <div class="pt-mini-card-value" style="color: #fda4af;">{{ $stafmt($a['rejected_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_total_rev">الإيراد الكلي</div>
                                        <div class="pt-mini-card-value">
                                            {{ $stafmt($a['total_revenue'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>'''
    content = content.replace(old_html, html_replacement)

# 3. Add JS push stack to the bottom
js_target = "    </section>\n@endsection\n"
js_replacement = """    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.sta-card');
    cards.forEach(card => {
        const compact = card.querySelector('.sta-card-compact');
        const details = card.querySelector('.sta-card-details');
        const arrow = card.querySelector('.sta-expand-arrow');
        const text = card.querySelector('.sta-expand-text');
        
        compact.addEventListener('click', (e) => {
            if (e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            const isExpanded = card.classList.contains('is-expanded');
            if (isExpanded) {
                card.classList.remove('is-expanded');
                details.style.maxHeight = null;
                details.style.opacity = '0';
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                if (text) {
                    text.setAttribute('data-i18n', 'adm_sta_show_details');
                    text.textContent = window.PT_T ? window.PT_T('adm_sta_show_details') : 'عرض التفاصيل';
                }
            } else {
                card.classList.add('is-expanded');
                details.style.maxHeight = details.scrollHeight + 'px';
                details.style.opacity = '1';
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                if (text) {
                    text.setAttribute('data-i18n', 'adm_sta_hide_details');
                    text.textContent = window.PT_T ? window.PT_T('adm_sta_hide_details') : 'إخفاء التفاصيل';
                }
            }
        });
    });
});
</script>
@endpush
"""

content = content.replace(js_target, js_replacement)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Dashboard successfully rewritten.")
