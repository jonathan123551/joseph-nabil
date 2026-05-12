{{-- resources/views/admin/shows/create.blade.php --}}
@extends('layouts.app')

@section('title', 'إضافة عرض جديد')

@section('content')
    <section class="max-w-2xl mx-auto space-y-4 prism-fade-up">

        {{-- Header --}}
        <div class="prism-glass prism-glow-border p-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="space-y-1">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        <span data-i18n="adm_show_new_pill">New Show</span>
                    </span>
                    <h1 class="prism-headline text-xl">
                        <span data-i18n="adm_show_new_title"
                              style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                            إضافة عرض جديد
                        </span>
                    </h1>
                </div>

                <a href="{{ route('admin.shows.index') }}" class="prism-btn-ghost text-xs">
                    <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                    <span data-i18n="adm_back_shows_list">رجوع لقائمة العروض</span>
                </a>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="pt-alert pt-alert-danger text-xs prism-fade-up">
                <ul class="list-disc pr-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.shows.store') }}" method="POST" enctype="multipart/form-data"
              class="space-y-4 prism-fade-up" autocomplete="off">
            @csrf

            {{-- Section: basic info --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">🎭</span>
                    <span class="pt-form-section-head-title" data-i18n="adm_show_basic">بيانات العرض</span>
                </div>

                <div class="pt-form-field">
                    <label class="pt-form-field-label">
                        <span data-i18n="adm_show_title_label">اسم العرض</span>
                        <span class="pt-form-req" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" class="prism-input text-sm">
                </div>

                <div class="pt-form-field">
                    <label class="pt-form-field-label" data-i18n="adm_show_description">وصف العرض</label>
                    <textarea name="description" rows="4" class="prism-input text-sm">{{ old('description') }}</textarea>
                    <p class="pt-form-helper" data-i18n="adm_show_description_helper">يظهر تحت اسم العرض في صفحة التفاصيل وعلى الكروت.</p>
                </div>
            </div>

            {{-- Section: theater type + section pricing --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">🏛️</span>
                    <span class="pt-form-section-head-title" data-i18n="adm_show_theater">نوع المسرح والأسعار</span>
                </div>

                <div class="pt-radio-group">
                    @foreach(\App\Models\Show::THEATER_TYPES as $value => $label)
                        <label class="pt-radio-card">
                            <input type="radio"
                                   name="theater_type"
                                   value="{{ $value }}"
                                   data-theater-type
                                   {{ old('theater_type', \App\Models\Show::THEATER_OTHER) === $value ? 'checked' : '' }}>
                            <span class="text-sm font-medium">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Anba Ruweis: per-section ticket prices + auto-capacity preview --}}
                <div data-anba-ruweis-fields
                     class="space-y-3 {{ old('theater_type') === \App\Models\Show::THEATER_ANBA_RUWEIS ? '' : 'hidden' }}">
                    <p class="pt-form-helper" data-i18n="adm_show_anba_helper">
                        الأنبا رويس بيستخدم تسعير لكل فئة (بلكون / صالة). هتظهر أسعار التذاكر تحت.
                    </p>
                    <div class="pt-form-grid">
                        <div class="pt-form-field">
                            <label class="pt-form-field-label" data-i18n="adm_show_balcony_price">سعر تذكرة البلكون (EGP)</label>
                            <input type="number" min="0" name="balcony_price"
                                   value="{{ old('balcony_price') }}"
                                   class="prism-input text-sm" inputmode="numeric">
                        </div>
                        <div class="pt-form-field">
                            <label class="pt-form-field-label" data-i18n="adm_show_hall_price">سعر تذكرة الصالة (EGP)</label>
                            <input type="number" min="0" name="hall_price"
                                   value="{{ old('hall_price') }}"
                                   class="prism-input text-sm" inputmode="numeric">
                        </div>
                    </div>

                    {{-- Live capacity preview — sourced from the actual seat
                         layout. Updates with the toggle JS below so admins
                         see the auto-derived capacity the moment they pick
                         the Anba Ruweis radio, without saving first. --}}
                    @php
                        $anbaTheater = \App\Models\Theater::anbaRuweis();
                        $anbaCounts  = ['hall' => 0, 'balcony' => 0, 'total' => 0];
                        if ($anbaTheater) {
                            $rows = $anbaTheater->seats()
                                ->selectRaw('section, COUNT(*) as c')
                                ->groupBy('section')
                                ->pluck('c', 'section')
                                ->all();
                            $anbaCounts['hall']    = (int) ($rows[\App\Models\Theater::SECTION_HALL] ?? 0);
                            $anbaCounts['balcony'] = (int) ($rows[\App\Models\Theater::SECTION_BALCONY] ?? 0);
                            $anbaCounts['total']   = $anbaCounts['hall'] + $anbaCounts['balcony'];
                        }
                    @endphp
                    <div class="pt-alert pt-alert-success text-xs">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="prism-dot prism-dot-emerald"></span>
                            <span class="font-semibold text-[color:var(--prism-text)]" data-i18n="adm_show_capacity_preview_title">
                                سعة المقاعد للمسرح
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="pt-mini-card pt-mini-card-gold">
                                <div class="pt-mini-card-label" data-i18n="adm_section_hall">صالة</div>
                                <div class="pt-mini-card-value">{{ $anbaCounts['hall'] }}</div>
                            </div>
                            <div class="pt-mini-card pt-mini-card-violet">
                                <div class="pt-mini-card-label" data-i18n="adm_section_balcony">بلكون</div>
                                <div class="pt-mini-card-value">{{ $anbaCounts['balcony'] }}</div>
                            </div>
                            <div class="pt-mini-card pt-mini-card-emerald">
                                <div class="pt-mini-card-label" data-i18n="adm_time_capacity_total">الإجمالي</div>
                                <div class="pt-mini-card-value">{{ $anbaCounts['total'] }}</div>
                            </div>
                        </div>
                        <p class="pt-form-helper mt-2" data-i18n="adm_time_capacity_helper">
                            يتم حساب إجمالي التذاكر تلقائيًا من خريطة المقاعد للمسرح.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Section: poster --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">🖼️</span>
                    <span class="pt-form-section-head-title" data-i18n="adm_show_poster">بوستر العرض</span>
                    <span class="pt-form-section-head-sub" data-i18n="common_optional">اختياري</span>
                </div>

                <label class="pt-file-zone">
                    <span class="pt-file-zone-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    </span>
                    <span class="pt-file-zone-title" data-i18n="adm_show_poster_pick">اضغط لاختيار صورة البوستر</span>
                    <span class="pt-file-zone-sub" data-i18n="adm_show_poster_hint">PNG / JPG · ينصح بنسبة عمودية (2:3)</span>
                    <input type="file" name="poster" id="posterInput" accept="image/*">
                </label>

                {{-- Inline preview — appears as soon as the operator picks
                     a file so they can sanity-check crop / orientation /
                     readability before saving. Hidden until a file is
                     chosen. --}}
                <div class="pt-image-preview" data-poster-preview hidden>
                    <div class="pt-image-preview-frame">
                        <img class="pt-image-preview-img"
                             alt=""
                             data-poster-preview-img
                             src="">
                        <div class="pt-image-preview-fallback" data-poster-preview-fallback hidden>
                            <span class="pt-image-preview-fallback-icon" aria-hidden="true">🖼️</span>
                            <span data-i18n="adm_show_poster_preview_load_err">تعذّر عرض الصورة</span>
                        </div>
                    </div>
                    <div class="pt-image-preview-meta">
                        <span class="pt-image-preview-meta-label" data-i18n="adm_show_poster_preview_label">معاينة</span>
                        <span class="pt-image-preview-meta-detail" data-poster-preview-meta></span>
                    </div>
                </div>
            </div>

            {{-- Section: ticket template + QR designer --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">🎟️</span>
                    <span class="pt-form-section-head-title" data-i18n="adm_show_ticket_design">تصميم التذكرة وموضع الـ QR</span>
                </div>

                <p class="pt-form-helper" data-i18n="adm_show_ticket_design_helper">
                    ارفع تصميم التذكرة (PNG / JPG)، وحدد مكان مربع الـ QR بالسحب على الصورة أو بالأرقام.
                    لو ما رفعتش تصميم، النظام هيطلع QR لوحده بدون خلفية.
                </p>

                <div class="pt-form-field">
                    <label class="pt-form-field-label" data-i18n="adm_show_ticket_template_file">ملف تصميم التذكرة</label>
                    <label class="pt-file-zone">
                        <span class="pt-file-zone-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                        </span>
                        <span class="pt-file-zone-title" data-i18n="adm_show_ticket_template_pick">اضغط لرفع تصميم التذكرة</span>
                        <span class="pt-file-zone-sub" data-i18n="adm_show_ticket_template_hint">بعد الرفع تقدر تحرك مربع الـ QR وتغيّر حجمه على التصميم</span>
                        <input type="file" name="ticket_template" id="ticket_template_input" accept="image/*">
                    </label>
                </div>

                {{-- Live QR position editor (shown after a file is picked) --}}
                <div id="ticket-editor-wrapper"
                     class="rounded-xl overflow-hidden hidden"
                     style="background: var(--prism-surface-soft); border: 1px solid var(--prism-border);">
                    <div id="ticket-editor" class="relative mx-auto max-w-md">
                        <img id="ticketTemplatePreview"
                             src=""
                             alt="تصميم التذكرة"
                             class="w-full h-auto block select-none pointer-events-none">

                        {{-- Movable QR box --}}
                        <div id="qrBox"
                             class="absolute border-2 border-emerald-400 bg-emerald-400/10 cursor-move"
                             style="width: 120px; height: 120px; left: 10px; top: 10px;">
                            <div id="qrResizeHandle"
                                 class="absolute w-3 h-3 bg-emerald-400 bottom-0 right-0 cursor-nwse-resize"></div>
                        </div>
                    </div>
                </div>

                {{-- Numeric QR coords (kept in sync with the visual editor) --}}
                <div class="pt-form-grid-3">
                    <div class="pt-form-field">
                        <label class="pt-form-field-label" data-i18n="adm_show_qr_x">X (من الشمال)</label>
                        <input type="number" min="0" name="ticket_qr_x"
                               id="ticket_qr_x_input"
                               value="{{ old('ticket_qr_x', 0) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label" data-i18n="adm_show_qr_y">Y (من فوق)</label>
                        <input type="number" min="0" name="ticket_qr_y"
                               id="ticket_qr_y_input"
                               value="{{ old('ticket_qr_y', 0) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label" data-i18n="adm_show_qr_size">حجم الـ QR</label>
                        <input type="number" min="50" name="ticket_qr_size"
                               id="ticket_qr_size_input"
                               value="{{ old('ticket_qr_size', 220) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                </div>

                <p class="pt-form-helper" data-i18n="adm_show_qr_helper">
                    حرّك مربع الـ QR على الصورة بالفأرة أو اللمس، واسحب المربع الصغير في الركن لتكبير/تصغير الحجم.
                    الأرقام بتتحوّل أوتوماتيك حسب موضعك على التصميم الأصلي (بالبكسل).
                </p>
            </div>

            {{-- Section: visibility --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">👁️</span>
                    <span class="pt-form-section-head-title" data-i18n="adm_show_visibility">الظهور</span>
                </div>

                <label class="pt-switch-row cursor-pointer">
                    <span class="text-xs text-[color:var(--prism-text-2)]" data-i18n="adm_show_visibility_label">عرض هذا العرض على الموقع</span>
                    <input type="checkbox" name="is_active" id="is_active" value="1" class="w-5 h-5"
                           {{ old('is_active', 1) ? 'checked' : '' }}
                           style="accent-color: #34d399;">
                </label>
            </div>

            {{-- Sticky action bar --}}
            <div class="pt-form-actions-sticky">
                <a href="{{ route('admin.shows.index') }}" class="prism-btn-ghost text-sm flex items-center justify-center">
                    <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                    <span data-i18n="common_cancel">إلغاء</span>
                </a>
                <button type="submit" class="prism-btn text-sm pt-form-actions-primary flex items-center justify-center">
                    <span data-i18n="adm_show_create_btn">اضافه العرض</span>
                    <span aria-hidden="true" class="pt-arrow-rtl">←</span>
                </button>
            </div>
        </form>
    </section>

    {{-- QR editor script (UNCHANGED behaviour, same DOM ids) --}}
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

            // When the admin picks a template file
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

            // Touch support
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

    {{-- Inline poster preview — instant visual confirmation after the
         operator picks a file. Revokes prior object URLs to avoid leaks,
         and falls back to a hatched "could not display" card if the
         browser fails to load the image. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input    = document.getElementById('posterInput');
            const preview  = document.querySelector('[data-poster-preview]');
            if (!input || !preview) return;

            const img      = preview.querySelector('[data-poster-preview-img]');
            const fallback = preview.querySelector('[data-poster-preview-fallback]');
            const meta     = preview.querySelector('[data-poster-preview-meta]');

            function fmtBytes(n) {
                if (n < 1024) return n + ' B';
                if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
                return (n / 1048576).toFixed(1) + ' MB';
            }

            img.addEventListener('error', function () { fallback.hidden = false; });
            img.addEventListener('load',  function () { fallback.hidden = true;  });

            input.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    preview.hidden = true;
                    if (img.dataset.blob === '1' && img.src) {
                        try { URL.revokeObjectURL(img.src); } catch (_) {}
                    }
                    img.removeAttribute('src');
                    return;
                }

                if (img.dataset.blob === '1' && img.src) {
                    try { URL.revokeObjectURL(img.src); } catch (_) {}
                }

                const url = URL.createObjectURL(file);
                img.dataset.blob = '1';
                img.src = url;
                fallback.hidden = true;
                meta.textContent = file.name + ' · ' + fmtBytes(file.size);
                preview.hidden = false;
            });
        });
    </script>

    {{-- Toggle balcony/hall fields based on theater type --}}
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
