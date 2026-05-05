{{-- resources/views/admin/shows/create.blade.php --}}
@extends('layouts.app')

@section('title', 'إضافة عرض جديد')

@section('content')
    <section class="max-w-xl space-y-5 mx-auto prism-fade-up">

        {{-- HEADER --}}
        <div class="prism-glass prism-glow-border p-5 flex items-center justify-between gap-3">
            <div class="space-y-1">
                <span class="prism-pill prism-pill-neon">
                    <span class="prism-dot prism-dot-emerald"></span>
                    New Show
                </span>
                <h1 class="prism-headline text-xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        إضافة عرض جديد
                    </span>
                </h1>
            </div>

            <a href="{{ route('admin.shows.index') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true">→</span>
                رجوع لقائمة العروض
            </a>
        </div>

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

        <form action="{{ route('admin.shows.store') }}" method="POST" enctype="multipart/form-data" class="prism-glass p-5 space-y-4 prism-fade-up">
            @csrf

            {{-- اسم العرض --}}
            <div>
                <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">اسم العرض</label>
                <input type="text" name="title" value="{{ old('title') }}" class="prism-input text-sm">
            </div>

            {{-- وصف العرض --}}
            <div>
                <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">وصف العرض</label>
                <textarea name="description" rows="4" class="prism-input text-sm">{{ old('description') }}</textarea>
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
                                   {{ old('theater_type', \App\Models\Show::THEATER_OTHER) === $value ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- أسعار التذاكر (يظهر فقط لمسرح الأنبا رويس) --}}
            <div data-anba-ruweis-fields
                 class="grid grid-cols-2 gap-3 {{ old('theater_type') === \App\Models\Show::THEATER_ANBA_RUWEIS ? '' : 'hidden' }}">
                <div>
                    <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">سعر تذكرة البلكون (EGP)</label>
                    <input type="number" min="0" name="balcony_price" value="{{ old('balcony_price') }}" class="prism-input text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">سعر تذكرة الصالة (EGP)</label>
                    <input type="number" min="0" name="hall_price" value="{{ old('hall_price') }}" class="prism-input text-sm">
                </div>
            </div>

            {{-- بوستر العرض --}}
            <div>
                <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">بوستر العرض (اختياري)</label>
                <input type="file" name="poster" accept="image/*"
                       class="w-full text-xs text-[color:var(--prism-text-2)] file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-medium file:cursor-pointer"
                       style="--tw-ring-color: var(--prism-border-strong);">
            </div>

            {{-- تصميم التذكرة + إعداد موضع الـ QR --}}
            <div class="mt-4 space-y-2 pt-3" style="border-top: 1px solid var(--prism-border);">
                <h3 class="text-sm font-semibold text-[color:var(--prism-text)]">تصميم التذكرة وموضع الـ QR</h3>

                <p class="text-xs text-[color:var(--prism-text-3)]">
                    ارفع تصميم التذكرة (PNG / JPG)، وبعدها حدد مكان مربع الـ QR بالسحب على الصورة أو بالأرقام.
                    لو ما رفعتش تصميم، النظام هيطلع QR لوحده بدون خلفية.
                </p>

                <div class="grid md:grid-cols-2 gap-4 items-start">

                    {{-- ملف تصميم التذكرة + المعاينة --}}
                    <div class="space-y-2">
                        <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">ملف تصميم التذكرة</label>

                        <input type="file"
                               name="ticket_template"
                               id="ticket_template_input"
                               accept="image/*"
                               class="w-full text-xs text-[color:var(--prism-text-2)]">

                        <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1">
                            بعد ما تختار الملف، هتقدر تحرك مربع الـ QR وتغيّر حجمه على التصميم.
                        </p>

                        {{-- محرر موضع الـ QR (المعاينة) --}}
                        <div id="ticket-editor-wrapper"
                             class="mt-2 rounded-xl overflow-hidden hidden"
                             style="background: rgba(8,10,20,0.55); border: 1px solid var(--prism-border);">
                            <div id="ticket-editor"
                                 class="relative mx-auto max-w-md">
                                <img id="ticketTemplatePreview"
                                     src=""
                                     alt="تصميم التذكرة"
                                     class="w-full h-auto block select-none pointer-events-none">

                                {{-- مربع الـ QR المتحرك --}}
                                <div id="qrBox"
                                     class="absolute border-2 border-emerald-400 bg-emerald-400/10 cursor-move"
                                     style="width: 120px; height: 120px; left: 10px; top: 10px;">
                                    <div id="qrResizeHandle"
                                         class="absolute w-3 h-3 bg-emerald-400 bottom-0 right-0 cursor-nwse-resize"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- إعدادات مكان الـ QR (أرقام) --}}
                    <div class="space-y-2 text-xs">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block mb-1 text-[color:var(--prism-text-3)]">X (من الشمال)</label>
                                <input type="number" min="0" name="ticket_qr_x"
                                       id="ticket_qr_x_input"
                                       value="{{ old('ticket_qr_x', 0) }}"
                                       class="prism-input text-xs px-2 py-1.5">
                            </div>
                            <div>
                                <label class="block mb-1 text-[color:var(--prism-text-3)]">Y (من فوق)</label>
                                <input type="number" min="0" name="ticket_qr_y"
                                       id="ticket_qr_y_input"
                                       value="{{ old('ticket_qr_y', 0) }}"
                                       class="prism-input text-xs px-2 py-1.5">
                            </div>
                            <div>
                                <label class="block mb-1 text-[color:var(--prism-text-3)]">حجم الـ QR</label>
                                <input type="number" min="50" name="ticket_qr_size"
                                       id="ticket_qr_size_input"
                                       value="{{ old('ticket_qr_size', 220) }}"
                                       class="prism-input text-xs px-2 py-1.5">
                            </div>
                        </div>

                        <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1 leading-relaxed">
                            حرّك مربع الـ QR على الصورة بالفأرة أو اللمس، واسحب المربع الصغير في الركن لتكبير/تصغير الحجم.
                            الأرقام دي بتتحوّل أوتوماتيك حسب مكانك على التصميم الأصلي (بالبكسل).
                        </p>
                    </div>
                </div>
            </div>

            {{-- حالة العرض --}}
            <label class="flex items-center gap-2 text-xs cursor-pointer text-[color:var(--prism-text-2)]">
                <input type="checkbox"
                       name="is_active"
                       id="is_active"
                       value="1"
                       class="w-4 h-4"
                       {{ old('is_active', 1) ? 'checked' : '' }}>
                عرض هذا العرض على الموقع
            </label>

            {{-- زر الحفظ --}}
            <button type="submit" class="prism-btn text-sm mt-2">
                اضافه العرض
                <span aria-hidden="true">←</span>
            </button>
        </form>
    </section>

    {{-- سكربت محرر الـ QR --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const templateInput = document.getElementById('ticket_template_input');
            const wrapper  = document.getElementById('ticket-editor-wrapper');
            const img      = document.getElementById('ticketTemplatePreview');
            const qrBox    = document.getElementById('qrBox');
            const handle   = document.getElementById('qrResizeHandle');

            const inputX   = document.getElementById('ticket_qr_x_input');
            const inputY   = document.getElementById('ticket_qr_y_input');
            const inputS   = document.getElementById('ticket_qr_size_input');

            if (!templateInput || !wrapper || !img || !qrBox || !handle || !inputX || !inputY || !inputS) {
                return;
            }

            let scale = 1;
            let isDragging = false;
            let isResizing = false;
            let startX = 0, startY = 0;
            let startLeft = 0, startTop = 0;
            let startWidth = 0;

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

            // لما يختار ملف تصميم
            templateInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    wrapper.classList.add('hidden');
                    img.src = '';
                    return;
                }

                const url = URL.createObjectURL(file);
                img.src = url;
                wrapper.classList.remove('hidden');

                img.onload = function () {
                    recalcScaleAndPositionFromInputs();
                };
            });

            [inputX, inputY, inputS].forEach(function (el) {
                el.addEventListener('input', recalcScaleAndPositionFromInputs);
            });

            // Drag
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

            // Resize
            handle.addEventListener('mousedown', function (e) {
                isResizing = true;
                const rect = qrBox.getBoundingClientRect();
                startX = e.clientX;
                startY = e.clientY;
                startWidth  = rect.width;
                e.stopPropagation();
                e.preventDefault();
            });

            window.addEventListener('mousemove', function (e) {
                if (!isDragging && !isResizing) return;
                const dx = e.clientX - startX;

                if (isDragging) {
                    const imgRect = img.getBoundingClientRect();
                    let newLeft = startLeft + dx - imgRect.left;
                    let newTop  = startTop  + (e.clientY - startY) - imgRect.top;

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

            // دعم اللمس (موبايل)
            qrBox.addEventListener('touchstart', function (e) {
                const touch = e.touches[0];
                if (!touch) return;

                if (e.target === handle) {
                    isResizing = true;
                    const rect = qrBox.getBoundingClientRect();
                    startX = touch.clientX;
                    startWidth  = rect.width;
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
