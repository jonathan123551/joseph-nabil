@extends('layouts.app')

@section('title', 'تعديل العرض - ' . $show->title)

@section('content')
<section class="max-w-3xl mx-auto space-y-4 prism-fade-up">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="space-y-1">
                <span class="prism-pill prism-pill-neon">
                    <span class="prism-dot prism-dot-emerald"></span>
                    Edit Show
                </span>
                <h1 class="prism-headline text-xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        تعديل العرض
                    </span>
                </h1>
                <p class="text-xs text-[color:var(--prism-text-3)]">{{ $show->title }}</p>
            </div>

            <a href="{{ route('admin.shows.index') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true">→</span>
                رجوع
            </a>
        </div>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="rounded-xl px-4 py-3 text-xs prism-fade-up"
             style="background: rgba(244,63,94,0.10); border: 1px solid rgba(251,113,133,0.45); color: #fda4af;">
            <ul class="list-disc pr-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.shows.update', $show) }}" method="POST" enctype="multipart/form-data"
          class="space-y-4" autocomplete="off">
        @csrf
        @method('PUT')

        {{-- Section: basic info --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">🎭</span>
                <span class="pt-form-section-head-title">بيانات العرض</span>
            </div>

            <div class="pt-form-field">
                <label class="pt-form-field-label">
                    اسم العرض
                    <span class="pt-form-req" aria-hidden="true">*</span>
                </label>
                <input type="text" name="title"
                       value="{{ old('title', $show->title) }}"
                       class="prism-input text-sm">
            </div>

            <div class="pt-form-field">
                <label class="pt-form-field-label">الوصف</label>
                <textarea name="description" rows="4" class="prism-input text-sm">{{ old('description', $show->description) }}</textarea>
            </div>
        </div>

        {{-- Section: theater type + section pricing --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">🏛️</span>
                <span class="pt-form-section-head-title">نوع المسرح والأسعار</span>
            </div>

            <div class="pt-radio-group">
                @foreach(\App\Models\Show::THEATER_TYPES as $value => $label)
                    <label class="pt-radio-card">
                        <input type="radio"
                               name="theater_type"
                               value="{{ $value }}"
                               data-theater-type
                               {{ old('theater_type', $show->theater_type ?? \App\Models\Show::THEATER_OTHER) === $value ? 'checked' : '' }}>
                        <span class="text-sm font-medium">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div data-anba-ruweis-fields
                 class="space-y-3 {{ old('theater_type', $show->theater_type) === \App\Models\Show::THEATER_ANBA_RUWEIS ? '' : 'hidden' }}">
                <p class="pt-form-helper">
                    الأنبا رويس بيستخدم تسعير لكل فئة (بلكون / صالة).
                </p>
                <div class="pt-form-grid">
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">سعر تذكرة البلكون (EGP)</label>
                        <input type="number" min="0" name="balcony_price"
                               value="{{ old('balcony_price', $show->balcony_price) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">سعر تذكرة الصالة (EGP)</label>
                        <input type="number" min="0" name="hall_price"
                               value="{{ old('hall_price', $show->hall_price) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: poster --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">🖼️</span>
                <span class="pt-form-section-head-title">بوستر العرض</span>
            </div>

            @if($show->poster_path)
                @php
                    $posterUrl = str_starts_with($show->poster_path, 'http')
                        ? $show->poster_path
                        : $show->poster_path;
                @endphp

                <img id="posterPreview"
                     src="{{ $posterUrl }}"
                     class="w-full max-h-60 object-contain rounded-xl p-2"
                     style="background: rgba(8,10,20,0.5); border: 1px solid var(--prism-border);">
            @endif

            <label class="pt-file-zone">
                <span class="pt-file-zone-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </span>
                <span class="pt-file-zone-title">
                    @if($show->poster_path) استبدال البوستر @else اضغط لاختيار صورة البوستر @endif
                </span>
                <span class="pt-file-zone-sub">PNG / JPG · ينصح بنسبة عمودية (2:3)</span>
                <input type="file" name="poster" id="posterInput" accept="image/*">
            </label>
        </div>

        {{-- Section: ticket template + QR --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">🎟️</span>
                <span class="pt-form-section-head-title">تصميم التذكرة وموضع الـ QR</span>
            </div>

            <label class="pt-file-zone">
                <span class="pt-file-zone-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                </span>
                <span class="pt-file-zone-title">
                    @if($show->ticket_template_path) استبدال تصميم التذكرة @else اضغط لرفع تصميم التذكرة @endif
                </span>
                <span class="pt-file-zone-sub">بعد الرفع تقدر تحرك مربع الـ QR وتغيّر حجمه</span>
                <input type="file" name="ticket_template" id="ticketInput" accept="image/*">
            </label>

            @if($show->ticket_template_path)
                @php
                    $ticketUrl = str_starts_with($show->ticket_template_path, 'http')
                        ? $show->ticket_template_path
                        : asset('storage/'.$show->ticket_template_path);
                @endphp

                <div class="relative rounded-xl overflow-hidden"
                     style="background: rgba(8,10,20,0.55); border: 1px solid var(--prism-border);">

                    <img id="ticketTemplatePreview"
                         src="{{ $ticketUrl }}"
                         class="w-full h-auto block select-none pointer-events-none">

                    <div id="qrBox"
                         class="absolute cursor-move"
                         style="width: 120px; height: 120px; left: 10px; top: 10px;
                                background: rgba(52,211,153,0.18);
                                border: 2px solid #34d399;
                                box-shadow: 0 0 18px rgba(52,211,153,0.55), inset 0 0 12px rgba(52,211,153,0.3);">

                        <div id="qrResizeHandle"
                             class="absolute w-3 h-3 bottom-0 right-0 cursor-nwse-resize"
                             style="background: #34d399; box-shadow: 0 0 8px rgba(52,211,153,0.7);"></div>

                    </div>

                </div>

                <div class="pt-form-grid-3">
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">X (من الشمال)</label>
                        <input type="number" name="ticket_qr_x" id="ticket_qr_x_input"
                               value="{{ old('ticket_qr_x', $show->ticket_qr_x ?? 0) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">Y (من فوق)</label>
                        <input type="number" name="ticket_qr_y" id="ticket_qr_y_input"
                               value="{{ old('ticket_qr_y', $show->ticket_qr_y ?? 0) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">حجم الـ QR</label>
                        <input type="number" name="ticket_qr_size" id="ticket_qr_size_input"
                               value="{{ old('ticket_qr_size', $show->ticket_qr_size ?? 220) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                </div>
            @endif
        </div>

        {{-- Section: visibility --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">👁️</span>
                <span class="pt-form-section-head-title">الظهور</span>
            </div>

            <label class="pt-switch-row cursor-pointer">
                <span class="text-xs text-[color:var(--prism-text-2)]">عرض هذا العرض على الموقع</span>
                <input type="checkbox" name="is_active" value="1" class="w-5 h-5"
                       {{ old('is_active', $show->is_active) ? 'checked' : '' }}
                       style="accent-color: #34d399;">
            </label>
            <p class="pt-form-helper">
                لما تلغي التحديد، العرض هيختفي من صفحة العروض ومش هيقدر أي حد يحجزه.
            </p>
        </div>

        {{-- Sticky action bar --}}
        <div class="pt-form-actions-sticky">
            <a href="{{ route('admin.shows.index') }}"
               class="prism-btn-ghost text-sm flex items-center justify-center">
                <span aria-hidden="true">→</span>
                إلغاء
            </a>
            <button type="submit" class="prism-btn text-sm pt-form-actions-primary flex items-center justify-center">
                حفظ التعديلات
                <span aria-hidden="true">←</span>
            </button>
        </div>
    </form>
</section>


{{-- QR editor + live previews — original behaviour preserved --}}

 <script>
        document.addEventListener('DOMContentLoaded', function () {
            const img      = document.getElementById('ticketTemplatePreview');
            const qrBox    = document.getElementById('qrBox');
            const handle   = document.getElementById('qrResizeHandle');

            const inputX   = document.getElementById('ticket_qr_x_input');
            const inputY   = document.getElementById('ticket_qr_y_input');
            const inputS   = document.getElementById('ticket_qr_size_input');

            if (!img || !qrBox || !handle || !inputX || !inputY || !inputS) {
                return;
            }

            let scale = 1;
            let isDragging = false;
            let isResizing = false;
            let startX = 0, startY = 0;
            let startLeft = 0, startTop = 0;
            let startWidth = 0, startHeight = 0;

            function recalcScaleAndPositionFromInputs() {
                if (!img.naturalWidth) return;

                scale = img.clientWidth / img.naturalWidth;

                const xVal = parseInt(inputX.value || '0', 10);
                const yVal = parseInt(inputY.value || '0', 10);
                const sVal = parseInt(inputS.value || '220', 10);

                qrBox.style.left   = (xVal * scale) + 'px';
                qrBox.style.top    = (yVal * scale) + 'px';
                qrBox.style.width  = (sVal * scale) + 'px';
                qrBox.style.height = (sVal * scale) + 'px';
            }

            function updateInputsFromBox() {
                const imgRect = img.getBoundingClientRect();
                const boxRect = qrBox.getBoundingClientRect();

                const left = boxRect.left - imgRect.left;
                const top  = boxRect.top  - imgRect.top;
                const size = boxRect.width;

                inputX.value = Math.max(0, Math.round(left / scale));
                inputY.value = Math.max(0, Math.round(top  / scale));
                inputS.value = Math.max(10, Math.round(size / scale));
            }

            img.addEventListener('load', function () {
                recalcScaleAndPositionFromInputs();
            });

            if (img.complete) {
                recalcScaleAndPositionFromInputs();
            }

            [inputX, inputY, inputS].forEach(function (el) {
                el.addEventListener('input', function () {
                    recalcScaleAndPositionFromInputs();
                });
            });

            qrBox.addEventListener('mousedown', function (e) {
                if (e.target === handle) return;

                isDragging = true;
                const rect = qrBox.getBoundingClientRect();

                startX = e.clientX;
                startY = e.clientY;
                startLeft = rect.left;
                startTop  = rect.top;

                e.preventDefault();
            });

            handle.addEventListener('mousedown', function (e) {
                isResizing = true;
                const rect = qrBox.getBoundingClientRect();

                startX = e.clientX;
                startY = e.clientY;
                startWidth  = rect.width;
                startHeight = rect.height;

                e.stopPropagation();
                e.preventDefault();
            });

            window.addEventListener('mousemove', function (e) {
                if (!isDragging && !isResizing) return;

                const dx = e.clientX - startX;
                const dy = e.clientY - startY;

                if (isDragging) {
                    const imgRect = img.getBoundingClientRect();

                    let newLeft = startLeft + dx - imgRect.left;
                    let newTop  = startTop  + dy - imgRect.top;

                    const maxLeft = imgRect.width  - qrBox.offsetWidth;
                    const maxTop  = imgRect.height - qrBox.offsetHeight;

                    newLeft = Math.min(Math.max(0, newLeft), maxLeft);
                    newTop  = Math.min(Math.max(0, newTop ), maxTop);

                    qrBox.style.left = newLeft + 'px';
                    qrBox.style.top  = newTop  + 'px';
                } else if (isResizing) {
                    let newSize = Math.max(40, startWidth + dx);

                    const imgRect = img.getBoundingClientRect();
                    const boxRect = qrBox.getBoundingClientRect();

                    const maxSize = Math.min(
                        imgRect.width  - (boxRect.left - imgRect.left),
                        imgRect.height - (boxRect.top  - imgRect.top)
                    );

                    newSize = Math.min(newSize, maxSize);

                    qrBox.style.width  = newSize + 'px';
                    qrBox.style.height = newSize + 'px';
                }

                updateInputsFromBox();
            });

            window.addEventListener('mouseup', function () {
                isDragging = false;
                isResizing = false;
            });

            qrBox.addEventListener('touchstart', function (e) {
                const touch = e.touches[0];
                if (!touch) return;

                if (e.target === handle) {
                    isResizing = true;
                    const rect = qrBox.getBoundingClientRect();
                    startX = touch.clientX;
                    startY = touch.clientY;
                    startWidth  = rect.width;
                    startHeight = rect.height;
                } else {
                    isDragging = true;
                    const rect = qrBox.getBoundingClientRect();
                    startX = touch.clientX;
                    startY = touch.clientY;
                    startLeft = rect.left;
                    startTop  = rect.top;
                }

                e.preventDefault();
            }, { passive: false });

            window.addEventListener('touchmove', function (e) {
                const touch = e.touches[0];
                if (!touch || (!isDragging && !isResizing)) return;

                const dx = touch.clientX - startX;
                const dy = touch.clientY - startY;

                if (isDragging) {
                    const imgRect = img.getBoundingClientRect();

                    let newLeft = startLeft + dx - imgRect.left;
                    let newTop  = startTop  + dy - imgRect.top;

                    const maxLeft = imgRect.width  - qrBox.offsetWidth;
                    const maxTop  = imgRect.height - qrBox.offsetHeight;

                    newLeft = Math.min(Math.max(0, newLeft), maxLeft);
                    newTop  = Math.min(Math.max(0, newTop ), maxTop);

                    qrBox.style.left = newLeft + 'px';
                    qrBox.style.top  = newTop  + 'px';
                } else if (isResizing) {
                    let newSize = Math.max(40, startWidth + dx);

                    const imgRect = img.getBoundingClientRect();
                    const boxRect = qrBox.getBoundingClientRect();

                    const maxSize = Math.min(
                        imgRect.width  - (boxRect.left - imgRect.left),
                        imgRect.height - (boxRect.top  - imgRect.top)
                    );

                    newSize = Math.min(newSize, maxSize);

                    qrBox.style.width  = newSize + 'px';
                    qrBox.style.height = newSize + 'px';
                }

                updateInputsFromBox();
                e.preventDefault();
            }, { passive: false });

            window.addEventListener('touchend', function () {
                isDragging = false;
                isResizing = false;
            });

            window.addEventListener('resize', function () {
                recalcScaleAndPositionFromInputs();
            });
        });
        </script>
        <script>
/* 🔥 LIVE PREVIEW */

// Poster Preview
document.getElementById('posterInput')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    if(!file) return;

    const url = URL.createObjectURL(file);
    const img = document.getElementById('posterPreview');

    if(img){
        img.src = url;
    }
});

// Ticket Preview
document.getElementById('ticketInput')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    if(!file) return;

    const url = URL.createObjectURL(file);
    const img = document.getElementById('ticketTemplatePreview');
    const qrBox = document.getElementById('qrBox');

    if(img && qrBox){
        img.src = url;

        // 🔥 رجّع QR لنقطة البداية
        qrBox.style.left = "10px";
        qrBox.style.top = "10px";
        qrBox.style.width = "120px";
        qrBox.style.height = "120px";

        // 🔥 حدّث inputs كمان
        document.getElementById('ticket_qr_x_input').value = 0;
        document.getElementById('ticket_qr_y_input').value = 0;
        document.getElementById('ticket_qr_size_input').value = 220;

        // 🔥 أهم حاجة: recalc بعد تحميل الصورة
        img.onload = function(){
            img.dispatchEvent(new Event('load'));
        };
    }
});

    </script>

    {{-- تبديل ظهور أسعار البلكون/الصالة بناءً على نوع المسرح --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = document.querySelectorAll('[data-theater-type]');
            const fields = document.querySelector('[data-anba-ruweis-fields]');
            if (!fields || radios.length === 0) return;

            function sync() {
                const checked = document.querySelector('[data-theater-type]:checked');
                const isAnba = checked && checked.value === '{{ \App\Models\Show::THEATER_ANBA_RUWEIS }}';
                fields.classList.toggle('hidden', !isAnba);
            }

            radios.forEach(r => r.addEventListener('change', sync));
            sync();
        });
    </script>

@endsection
