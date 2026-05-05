@extends('layouts.app')

@section('title', 'تعديل العرض - ' . $show->title)

@section('content')
<form action="{{ route('admin.shows.update', $show) }}" method="POST" enctype="multipart/form-data" class="space-y-5 prism-fade-up">
    @csrf
    @method('PUT')

{{-- Header --}}
<div class="prism-glass prism-glow-border p-5 flex items-center justify-between gap-3">
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
    </div>

    <a href="{{ route('admin.shows.index') }}" class="prism-btn-ghost text-xs">
        <span aria-hidden="true">→</span>
        رجوع
    </a>
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

<div class="grid lg:grid-cols-2 gap-5">

    {{-- LEFT --}}
    <div class="prism-glass p-5 space-y-4">

        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">اسم العرض</label>
            <input type="text" name="title"
                   value="{{ old('title', $show->title) }}"
                   class="prism-input text-sm">
        </div>

        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">الوصف</label>
            <textarea name="description" rows="4" class="prism-input text-sm">{{ old('description', $show->description) }}</textarea>
        </div>

        {{-- نوع المسرح --}}
        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">نوع المسرح</label>
            <div class="flex flex-col sm:flex-row gap-2 text-xs">
                @foreach(\App\Models\Show::THEATER_TYPES as $value => $label)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl cursor-pointer transition"
                           style="background: rgba(255,255,255,0.04); border: 1px solid var(--prism-border); color: var(--prism-text);"
                           onmouseover="this.style.borderColor='var(--prism-border-strong)'; this.style.background='rgba(129,140,248,0.08)';"
                           onmouseout="this.style.borderColor='var(--prism-border)'; this.style.background='rgba(255,255,255,0.04)';">
                        <input type="radio"
                               name="theater_type"
                               value="{{ $value }}"
                               data-theater-type
                               class="w-4 h-4"
                               {{ old('theater_type', $show->theater_type ?? \App\Models\Show::THEATER_OTHER) === $value ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- أسعار التذاكر --}}
        <div data-anba-ruweis-fields
             class="grid grid-cols-2 gap-3 {{ old('theater_type', $show->theater_type) === \App\Models\Show::THEATER_ANBA_RUWEIS ? '' : 'hidden' }}">
            <div>
                <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">سعر تذكرة البلكون (EGP)</label>
                <input type="number" min="0" name="balcony_price"
                       value="{{ old('balcony_price', $show->balcony_price) }}"
                       class="prism-input text-sm">
            </div>
            <div>
                <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">سعر تذكرة الصالة (EGP)</label>
                <input type="number" min="0" name="hall_price"
                       value="{{ old('hall_price', $show->hall_price) }}"
                       class="prism-input text-sm">
            </div>
        </div>

        {{-- Poster --}}
        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">البوستر</label>

            @if($show->poster_path)
                @php
                    $posterUrl = str_starts_with($show->poster_path, 'http')
                        ? $show->poster_path
                        : $show->poster_path;
                @endphp

                <img id="posterPreview"
                     src="{{ $posterUrl }}"
                     class="w-full max-h-60 object-contain rounded-xl mb-2 p-2"
                     style="background: rgba(8,10,20,0.5); border: 1px solid var(--prism-border);">
            @endif

            <input type="file" name="poster" id="posterInput" class="text-xs text-[color:var(--prism-text-2)]">
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="space-y-4">

        <div class="prism-glass p-5 space-y-3">

            <h3 class="text-sm font-semibold text-[color:var(--prism-text)]">🎟️ تصميم التذكرة + QR</h3>

            <input type="file" name="ticket_template" id="ticketInput" class="text-xs text-[color:var(--prism-text-2)]">

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

                <div class="grid grid-cols-3 gap-2 text-xs">
                    <input type="number" name="ticket_qr_x" id="ticket_qr_x_input"
                           value="{{ old('ticket_qr_x', $show->ticket_qr_x ?? 0) }}"
                           class="prism-input text-xs px-2 py-1.5">

                    <input type="number" name="ticket_qr_y" id="ticket_qr_y_input"
                           value="{{ old('ticket_qr_y', $show->ticket_qr_y ?? 0) }}"
                           class="prism-input text-xs px-2 py-1.5">

                    <input type="number" name="ticket_qr_size" id="ticket_qr_size_input"
                           value="{{ old('ticket_qr_size', $show->ticket_qr_size ?? 220) }}"
                           class="prism-input text-xs px-2 py-1.5">
                </div>

            @endif

        </div>

    </div>

</div>

<button type="submit" class="prism-btn text-sm w-full sm:w-auto">
    حفظ التعديلات
    <span aria-hidden="true">←</span>
</button>

</form>


{{-- 🔥 نفس السكربت بدون أي تغيير --}}

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
