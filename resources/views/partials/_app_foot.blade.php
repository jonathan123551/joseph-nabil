    {{-- ============== Premium modal (singleton) ============== --}}
    <div class="pt-modal-root" id="pt-modal-root" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="pt-modal-backdrop" data-pt-modal-close></div>
        <div class="pt-modal-card" id="pt-modal-card">
            <div class="pt-modal-icon" id="pt-modal-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h0"/></svg>
            </div>
            <div class="pt-modal-title" id="pt-modal-title">تأكيد</div>
            <div class="pt-modal-body"  id="pt-modal-body"></div>
            <div class="pt-modal-actions" id="pt-modal-actions"></div>
        </div>
    </div>

    {{-- ============== Toast ============== --}}
    <div class="pt-toast" id="pt-toast" role="status" aria-live="polite"></div>

    {{-- ============== JS: ripple, modal, lang toggle, scroll reveal, action bar ============== --}}
    <script>
    (function () {
        // ---------- ripple ----------
        document.addEventListener('pointerdown', function (e) {
            const t = e.target.closest('.prism-ripple');
            if (!t) return;
            const r = t.getBoundingClientRect();
            t.style.setProperty('--rx', ((e.clientX - r.left) / r.width * 100) + '%');
            t.style.setProperty('--ry', ((e.clientY - r.top)  / r.height * 100) + '%');
        }, { passive: true });

        // ---------- floating topbar scroll ----------
        const topbar = document.getElementById('pt-topbar');
        const topbarWrap = document.getElementById('pt-topbar-wrap');
        const updateTopbar = () => {
            if (!topbar) return;
            const scrolled = window.scrollY > 6;
            topbar.classList.toggle('is-scrolled', scrolled);
            if (topbarWrap) topbarWrap.classList.toggle('is-scrolled', scrolled);
        };
        window.addEventListener('scroll', updateTopbar, { passive: true });
        updateTopbar();

        // ---------- scroll reveal ----------
        const io = ('IntersectionObserver' in window) ? new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    en.target.classList.add('is-in');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.08 }) : null;
        const observeReveals = () => {
            document.querySelectorAll('.pt-reveal:not(.is-in)').forEach(el => {
                if (io) io.observe(el);
                else el.classList.add('is-in');
            });
        };
        observeReveals();

        // ---------- modal API ----------
        const root = document.getElementById('pt-modal-root');
        const card = document.getElementById('pt-modal-card');
        const titleEl = document.getElementById('pt-modal-title');
        const bodyEl = document.getElementById('pt-modal-body');
        const iconEl = document.getElementById('pt-modal-icon');
        const actionsEl = document.getElementById('pt-modal-actions');
        const ICONS = {
            info:    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h0"/></svg>',
            success: '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 L9 17 L4 12"/></svg>',
            error:   '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6 L18 18 M18 6 L6 18"/></svg>',
            warn:    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L22 21 L2 21 Z"/><path d="M12 10v4M12 18h0"/></svg>',
            loading: '<div class="pt-modal-spinner"></div>'
        };
        function setTone(tone) {
            iconEl.classList.remove('tone-success','tone-error','tone-warn');
            if (tone === 'success') iconEl.classList.add('tone-success');
            else if (tone === 'error') iconEl.classList.add('tone-error');
            else if (tone === 'warn')  iconEl.classList.add('tone-warn');
            iconEl.innerHTML = ICONS[tone] || ICONS.info;
        }
        function open(opts) {
            opts = opts || {};
            const tone = opts.tone || 'info';
            setTone(tone);
            titleEl.textContent = opts.title || '';
            if (typeof opts.body === 'string') bodyEl.innerHTML = opts.body;
            else { bodyEl.innerHTML = ''; if (opts.body instanceof Node) bodyEl.appendChild(opts.body); }
            actionsEl.innerHTML = '';
            (opts.actions || []).forEach(a => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = a.label || '';
                let cls = a.variant === 'ghost' ? 'prism-btn-ghost' :
                          a.variant === 'rose'  ? 'prism-btn-rose'  :
                          a.variant === 'emerald' ? 'prism-btn-emerald' :
                          a.variant === 'gold' ? 'prism-btn-gold' :
                          'prism-btn';
                b.className = cls + ' text-xs';
                b.addEventListener('click', () => {
                    let r = true;
                    if (typeof a.onClick === 'function') r = a.onClick();
                    if (r !== false) close();
                });
                actionsEl.appendChild(b);
            });
            root.classList.add('is-open');
            root.setAttribute('aria-hidden','false');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            root.classList.remove('is-open');
            root.setAttribute('aria-hidden','true');
            document.body.style.overflow = '';
        }
        root.addEventListener('click', (e) => {
            if (e.target.matches('[data-pt-modal-close]')) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && root.classList.contains('is-open')) close();
        });

        // ---------- toast ----------
        const toast = document.getElementById('pt-toast');
        let toastTimer = null;
        function showToast(message, ms) {
            toast.textContent = message;
            toast.classList.add('is-on');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('is-on'), ms || 2400);
        }

        // ---------- language toggle ----------
        // Comprehensive bilingual dictionary. Arabic preserves the existing
        // copy verbatim, English is human-tuned for native quality. Keys are
        // flat to keep templates terse.
        @include('partials._i18n_dictionary')
        // Match all lang toggle button groups (desktop + mobile drawer)
        const langButtons = document.querySelectorAll('.pt-lang-toggle button[data-pt-lang]');
        function moveThumbForGroup(group, lang) {
            const thumb = group.querySelector('.pt-lang-thumb');
            if (!thumb) return;
            const target = group.querySelector('button[data-pt-lang="' + lang + '"]');
            if (!target) return;
            const wrap = group.getBoundingClientRect();
            const r = target.getBoundingClientRect();
            thumb.style.width = r.width + 'px';
            const offset = r.left - wrap.left;
            thumb.style.transform = 'translateX(' + offset + 'px)';
        }
        // Look up a translation key against the current dictionary, with simple
        // {placeholder} interpolation. Falls back to the AR dictionary, then to
        // the key itself, so missing keys never blow up the page.
        function ptT(key, vars) {
            const lang = document.documentElement.getAttribute('data-pt-lang') || 'ar';
            const dict = I18N[lang] || I18N.ar;
            let s = dict[key];
            if (s === undefined) s = (I18N.ar || {})[key];
            if (s === undefined) return key;
            if (vars && typeof s === 'string') {
                s = s.replace(/\{(\w+)\}/g, (m, k) => (vars[k] !== undefined ? vars[k] : m));
            }
            return s;
        }
        // Expose helpers globally so per-page JS (seat picker, booking form,
        // scanner, ...) can build dynamic strings in the active language.
        window.PT_I18N = I18N;
        window.PT_T    = ptT;
        function applyLang(lang) {
            const dict = I18N[lang] || I18N.ar;
            // Only fire pt:langchange and rewrite document attributes if
            // the language actually changed. Callers used to invoke
            // applyLang(currentLang) on resize/load purely to re-position
            // the language-toggle thumb, but that re-dispatched
            // `pt:langchange`, which on Android Chrome (where every
            // keyboard open/close fires a `resize`) destroyed booking
            // form inputs mid-typing via their innerHTML='' rebuild and
            // collapsed the on-screen keyboard. Guard the heavy work
            // here so passive same-lang calls become cheap no-ops; the
            // dedicated repositionLangThumbs() handler below covers the
            // legitimate "viewport changed" use case.
            const prevLang = document.documentElement.getAttribute('data-pt-lang') || '';
            const langChanged = prevLang !== lang;
            document.documentElement.setAttribute('data-pt-lang', lang);
            document.documentElement.lang = lang;
            document.documentElement.dir  = (lang === 'en') ? 'ltr' : 'rtl';
            if (!langChanged) {
                // Already in this language — skip the heavy DOM rewrite
                // and the pt:langchange dispatch. Keep the thumb position
                // fresh because the resize that triggered us may have
                // changed the toggle's measured width.
                document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, lang));
                window.PT_LANG = lang;
                return;
            }
            // Read optional `data-i18n-vars='{"n": 5}'` JSON for placeholder
            // substitution in {n}-style templates. Returns an empty object on
            // missing/invalid JSON so the raw template still renders cleanly.
            const readVars = (el) => {
                const raw = el.getAttribute('data-i18n-vars');
                if (!raw) return null;
                try { return JSON.parse(raw); } catch (_) { return null; }
            };
            const interp = (s, vars) => {
                if (!vars || typeof s !== 'string') return s;
                return s.replace(/\{(\w+)\}/g, (m, k) => (vars[k] !== undefined ? vars[k] : m));
            };
            // Text content
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const k = el.getAttribute('data-i18n');
                if (dict[k] !== undefined) el.textContent = interp(dict[k], readVars(el));
            });
            // HTML content (for strings that include inline tags / line breaks)
            document.querySelectorAll('[data-i18n-html]').forEach(el => {
                const k = el.getAttribute('data-i18n-html'); 
                if (dict[k] !== undefined) el.innerHTML = interp(dict[k], readVars(el));
            });
            // Attribute translation. Encode as `data-i18n-attr="placeholder:key,title:key2"`.
            document.querySelectorAll('[data-i18n-attr]').forEach(el => {
                const spec = el.getAttribute('data-i18n-attr') || '';
                spec.split(',').forEach(pair => {
                    const [attr, key] = pair.split(':').map(s => s && s.trim());
                    if (!attr || !key) return;
                    if (dict[key] !== undefined) el.setAttribute(attr, dict[key]);
                });
            });
            // Update language toggle button states + thumb
            langButtons.forEach(b => {
                const on = b.getAttribute('data-pt-lang') === lang;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, lang));
            // Page title — pages can declare a meta tag like
            //   <meta name="pt-title-i18n" content="key" data-suffix="...">
            // and document.title is rebuilt here in the active language.
            // The dynamic `data-suffix` (e.g. a show title) is appended
            // with " · " when present. Missing keys leave the existing
            // @section('title') string untouched.
            const titleMeta = document.querySelector('meta[name="pt-title-i18n"]');
            if (titleMeta) {
                const tk = titleMeta.getAttribute('content');
                const suffix = titleMeta.getAttribute('data-suffix') || '';
                if (tk && dict[tk] !== undefined) {
                    const base = interp(dict[tk], readVars(titleMeta));
                    document.title = suffix ? base + ' · ' + suffix : base;
                }
            }
            try { localStorage.setItem('pt-lang', lang); } catch(_){}
            window.PT_LANG = lang;
            document.dispatchEvent(new CustomEvent('pt:langchange', { detail: { lang } }));
        }
        window.PT_APPLY_LANG = applyLang;
        langButtons.forEach(b => b.addEventListener('click', () => applyLang(b.getAttribute('data-pt-lang'))));
        let initLang = 'ar';
        try { initLang = localStorage.getItem('pt-lang') || 'ar'; } catch(_){}
        applyLang(initLang);
        // Re-position the language-toggle thumb after fonts load + on
        // resize. This previously called applyLang(currentLang), but
        // Android Chrome's on-screen keyboard fires `resize` every
        // time it opens / closes, and applyLang used to dispatch
        // pt:langchange + walk every data-i18n element on each call.
        // Booking form listeners on pt:langchange rebuild their
        // attendee inputs via innerHTML='', which destroyed the
        // input the user was actively typing into and collapsed the
        // keyboard. The cheap thumb-reposition below is the only
        // thing we ever needed on resize. Wrapped in rAF so a burst
        // of resize events (Gboard appearance animation) collapses
        // to a single layout read per frame.
        
        function repositionLangThumbs() {
            const cur = document.documentElement.getAttribute('data-pt-lang') || 'ar';
            document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, cur));
        }
        window.addEventListener('load', repositionLangThumbs);
        let __ptResizeRaf = 0;
        window.addEventListener('resize', () => {
            if (__ptResizeRaf) cancelAnimationFrame(__ptResizeRaf);
            __ptResizeRaf = requestAnimationFrame(repositionLangThumbs);
        });

        // ---------- theme toggle ----------
        function applyTheme(theme, persist) {
            theme = theme === 'light' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-pt-theme', theme);
            const meta = document.getElementById('pt-theme-color');
            if (meta) meta.setAttribute('content', theme === 'light' ? '#f4f1ea' : '#05060d');
            document.querySelectorAll('.pt-theme-segment button[data-pt-theme-set]').forEach(b => {
                const on = b.getAttribute('data-pt-theme-set') === theme;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (persist) { try { localStorage.setItem('pt-theme', theme); } catch(_){} }
            document.dispatchEvent(new CustomEvent('pt:themechange', { detail: { theme } }));
        }
        const themeBtn = document.getElementById('pt-theme-toggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const cur = document.documentElement.getAttribute('data-pt-theme') || 'dark';
                applyTheme(cur === 'dark' ? 'light' : 'dark', true);
            });
        }
        document.querySelectorAll('.pt-theme-segment button[data-pt-theme-set]').forEach(b => {
            b.addEventListener('click', () => applyTheme(b.getAttribute('data-pt-theme-set'), true));
        });
        // Sync segment with currently-active theme on load (the early bootstrap script already set the attribute)
        applyTheme(document.documentElement.getAttribute('data-pt-theme') || 'dark', false);
        // Platform default is dark — the OS prefers-color-scheme hint
        // is intentionally NOT wired up to applyTheme(). First-time
        // visitors always land on dark; if they want light they pick
        // it via the toggle and the choice persists in localStorage.

        // ---------- mobile drawer ----------
        const drawer = document.getElementById('pt-drawer');
        const drawerBackdrop = document.getElementById('pt-drawer-backdrop');
        const burger = document.getElementById('pt-burger');
        const drawerClose = document.getElementById('pt-drawer-close');
        function openDrawer() {
            if (!drawer) return;
            document.body.classList.add('pt-drawer-open');
            drawer.setAttribute('aria-hidden', 'false');
            if (burger) burger.setAttribute('aria-expanded', 'true');
            // lock background scroll while preserving position
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            if (!drawer) return;
            document.body.classList.remove('pt-drawer-open');
            drawer.setAttribute('aria-hidden', 'true');
            if (burger) burger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
        if (burger) burger.addEventListener('click', () => {
            if (document.body.classList.contains('pt-drawer-open')) closeDrawer(); else openDrawer();
        });
        if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);
        if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.body.classList.contains('pt-drawer-open')) closeDrawer();
        });
        // Close drawer when a drawer link is tapped
        document.querySelectorAll('.pt-drawer-link').forEach(a => {
            a.addEventListener('click', () => closeDrawer());
        });
        // Close drawer when window is resized to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 880 && document.body.classList.contains('pt-drawer-open')) closeDrawer();
        });

        // ---------- expose API ----------
        window.PT = window.PT || {};
        window.PT.modal = { open, close };
        window.PT.toast = showToast;
        window.PT.observeReveals = observeReveals;
        window.PT.t = (k) => (I18N[document.documentElement.getAttribute('data-pt-lang') || 'ar'] || {})[k] || k;
        window.PT.lang = () => document.documentElement.getAttribute('data-pt-lang') || 'ar';
        window.PT.theme = () => document.documentElement.getAttribute('data-pt-theme') || 'dark';
        window.PT.setTheme = applyTheme;

        // ---------- Wave 1: copy + share helpers ----------
        // Tap-to-copy: any element with [data-pt-copy="value"] copies that
        // value to clipboard on click and shows a toast. Falls back to
        // execCommand for older browsers / non-secure contexts.
        function ptCopyValue(value) {
            if (value == null) return Promise.reject();
            const text = String(value);
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            return new Promise((resolve, reject) => {
                try {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.top = '-1000px';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    const ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    ok ? resolve() : reject();
                } catch (e) { reject(e); }
            });
        }
        window.PT.copy = ptCopyValue;

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-pt-copy]');
            if (!trigger) return;
            const value = trigger.getAttribute('data-pt-copy');
            if (!value) return;
            e.preventDefault();
            ptCopyValue(value).then(() => {
                showToast(window.PT.t('copy_done'));
                trigger.classList.add('is-copied');
                setTimeout(() => trigger.classList.remove('is-copied'), 1200);
            }).catch(() => {
                showToast(window.PT.t('copy_failed'));
            });
        });

        // Share helper — wa.me deep link, falls back to navigator.share
        // when available. Returns the wa.me URL so callers can also use
        // it as a plain href.
        window.PT.shareWA = function (text, url) {
            const body = (text || '') + (url ? (text ? ' ' : '') + url : '');
            return 'https://wa.me/?text=' + encodeURIComponent(body);
        };

        // ---------- cinematic homepage v2 (homepage-only motion) ----------
        // Wires up: hero spotlight cursor glow, scroll-parallax orbs,
        // staggered hero entrance reveal, pointer-tracked 3D tilt on
        // storytelling cards, and magnetic CTAs. All gated on hover +
        // fine pointer + reduced-motion preference. Listeners attach
        // only to homepage-scoped nodes; on other pages this block
        // does nothing because the targets don't exist.
        (function setupCinemaV2() {
            const hero = document.querySelector('.pt-hero');
            if (!hero) return; // homepage-only

            const reduceMQ = matchMedia('(prefers-reduced-motion: reduce)');
            const hoverMQ  = matchMedia('(hover: hover) and (pointer: fine)');

            // Hero entrance: mark .pt-cinema-stagger as .is-in next frame.
            // Always run this — even with reduced-motion the CSS already
            // collapses the transition, so this is a no-op visually.
            requestAnimationFrame(() => {
                document.querySelectorAll('.pt-cinema-stagger').forEach(el => {
                    el.classList.add('is-in');
                });
            });

            if (reduceMQ.matches) return;

            // Hero spotlight cursor glow
            const spot = hero.querySelector('.pt-cinema-spot');
            if (spot && hoverMQ.matches) {
                hero.addEventListener('pointermove', (e) => {
                    const r = hero.getBoundingClientRect();
                    const x = ((e.clientX - r.left) / r.width)  * 100;
                    const y = ((e.clientY - r.top)  / r.height) * 100;
                    spot.style.setProperty('--pt-spot-x', x + '%');
                    spot.style.setProperty('--pt-spot-y', y + '%');
                }, { passive: true });
                hero.addEventListener('pointerenter', () => spot.classList.add('is-on'));
                hero.addEventListener('pointerleave', () => spot.classList.remove('is-on'));
            }

            // Scroll parallax for hero ambient orbs (rAF-throttled)
            const orbs = hero.querySelectorAll('.pt-cinema-orb');
            if (orbs.length) {
                let ticking = false;
                const FACTORS = [0.55, 0.9, 0.7];
                const onScroll = () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => {
                        const y = Math.min(120, Math.max(-30, window.scrollY * 0.18));
                        orbs.forEach((orb, i) => {
                            const f = FACTORS[i] != null ? FACTORS[i] : 0.7;
                            orb.style.setProperty('--pt-parallax', (y * f).toFixed(1) + 'px');
                        });
                        ticking = false;
                    });
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            }

            // 3D pointer-tracked tilt on storytelling cards
            if (hoverMQ.matches) {
                document.querySelectorAll('.pt-cinema-step').forEach(step => {
                    let raf = 0;
                    const apply = (px, py) => {
                        const rx = (0.5 - py) * 9;   // max ±4.5deg
                        const ry = (px - 0.5) * 12;  // max ±6deg
                        step.style.setProperty('--pt-rx', rx.toFixed(2) + 'deg');
                        step.style.setProperty('--pt-ry', ry.toFixed(2) + 'deg');
                        step.style.setProperty('--pt-ty', '-4px');
                    };
                    step.addEventListener('pointerenter', () => step.classList.add('is-tilting'));
                    step.addEventListener('pointermove', (e) => {
                        if (raf) return;
                        raf = requestAnimationFrame(() => {
                            const r = step.getBoundingClientRect();
                            apply((e.clientX - r.left) / r.width,
                                  (e.clientY - r.top)  / r.height);
                            raf = 0;
                        });
                    }, { passive: true });
                    step.addEventListener('pointerleave', () => {
                        step.classList.remove('is-tilting');
                        step.style.setProperty('--pt-rx', '0deg');
                        step.style.setProperty('--pt-ry', '0deg');
                        step.style.setProperty('--pt-ty', '0px');
                    });
                });
            }

            // Magnetic CTAs (homepage scope)
            if (hoverMQ.matches) {
                document.querySelectorAll('.pt-cinema-magnet').forEach(el => {
                    const MAX = 9;
                    let raf = 0;
                    el.addEventListener('pointerenter', () => el.classList.add('is-magnet'));
                    el.addEventListener('pointermove', (e) => {
                        if (raf) return;
                        raf = requestAnimationFrame(() => {
                            const r = el.getBoundingClientRect();
                            const x = ((e.clientX - r.left) / r.width  - 0.5) * 2;
                            const y = ((e.clientY - r.top)  / r.height - 0.5) * 2;
                            el.style.setProperty('--pt-mx', (x * MAX).toFixed(1) + 'px');
                            el.style.setProperty('--pt-my', (y * MAX * 0.55).toFixed(1) + 'px');
                            raf = 0;
                        });
                    }, { passive: true });
                    el.addEventListener('pointerleave', () => {
                        el.classList.remove('is-magnet');
                        el.style.setProperty('--pt-mx', '0px');
                        el.style.setProperty('--pt-my', '0px');
                    });
                });
            }
        })();

        // ---------- Cinematic homepage v3 (full-screen scene story) ----------
        // Activates each .pt-cine-scene as it enters the viewport (.is-active),
        // and tracks when the intro scene is in view so the floating nav can
        // fade out for a true full-screen opener. Homepage-scoped: silently
        // no-ops on every other page (no [data-pt-cine] root present).
        (function setupCinemaV3() {
            const root = document.querySelector('[data-pt-cine]');
            if (!root) return;

            const scenes = root.querySelectorAll('.pt-cine-scene');
            if (!scenes.length) return;

            // Intro is full-screen on first paint — flag the body so the
            // floating nav stays hidden until the user scrolls past it.
            const introScene = root.querySelector('.is-scene-intro');
            if (introScene) {
                document.body.classList.add('has-cine-intro-active');
            }

            // Activate first scene immediately so its stagger plays on load.
            requestAnimationFrame(() => {
                if (scenes[0]) scenes[0].classList.add('is-active');
            });

            // IntersectionObserver: mark each scene .is-active when it
            // crosses 35% visibility. We never remove the class once it's
            // set so re-scrolling up doesn't re-trigger the entrance.
            if ('IntersectionObserver' in window) {
                const sceneIO = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio >= 0.34) {
                            entry.target.classList.add('is-active');
                        }
                    });
                }, { threshold: [0.34, 0.6] });
                scenes.forEach((scene) => sceneIO.observe(scene));

                // Watch the intro scene: nav fades back in once it leaves.
                if (introScene) {
                    const introIO = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.intersectionRatio >= 0.45) {
                                document.body.classList.add('has-cine-intro-active');
                            } else {
                                document.body.classList.remove('has-cine-intro-active');
                            }
                        });
                    }, { threshold: [0, 0.2, 0.45, 0.8] });
                    introIO.observe(introScene);
                }
            } else {
                // No IO support — just activate everything so content shows.
                scenes.forEach((s) => s.classList.add('is-active'));
                document.body.classList.remove('has-cine-intro-active');
            }
        })();

        // ---------- Trailer click-to-load embed ----------
        // First paint shows the cinematic poster-frame. Tap (or Enter/Space)
        // mounts an iframe pointing at the Facebook video plugin so the
        // trailer plays INLINE — never opens externally on the primary
        // path. The fallback link below the frame stays as the lifeline
        // if the embed silently fails. We add a 6s "loading watchdog":
        // if the iframe never fires `load`, we surface the fallback as
        // a more prominent visual hint (the loading caption itself
        // becomes the call-to-action).
        (function setupTrailerClickToLoad() {
            const cards = document.querySelectorAll('[data-pt-trailer-card]');
            if (!cards.length) return;

            cards.forEach((card) => {
                const frame    = card.querySelector('[data-pt-trailer-frame]');
                const embedUrl = card.getAttribute('data-pt-trailer-embed');
                if (!frame || !embedUrl) return;

                const play = () => {
                    if (card.dataset.loaded === '1') return;
                    card.dataset.loaded = '1';
                    card.classList.add('is-loading');

                    const iframe = document.createElement('iframe');
                    iframe.src   = embedUrl;
                    iframe.title = 'برومو مسرحية العباد';
                    iframe.setAttribute('allow', 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share');
                    iframe.setAttribute('allowfullscreen', 'true');
                    iframe.setAttribute('frameborder', '0');
                    iframe.setAttribute('scrolling', 'no');
                    iframe.setAttribute('loading', 'eager');

                    iframe.addEventListener('load', () => {
                        card.classList.remove('is-loading');
                        card.classList.add('is-playing');
                    }, { once: true });

                    // Watchdog: if the FB plugin never fires `load` (ad
                    // blocker, region block, private post, etc.) we let
                    // the loading veil persist but visually surface the
                    // fallback link beneath so the user is never
                    // stranded. 6s is conservative — FB's plugin
                    // normally fires `load` within 1-2s on broadband.
                    setTimeout(() => {
                        if (!card.classList.contains('is-playing')) {
                            card.classList.remove('is-loading');
                            card.classList.add('is-stalled');
                        }
                    }, 6000);

                    frame.appendChild(iframe);
                };

                frame.addEventListener('click', play);
                frame.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        play();
                    }
                });
            });
        })();

        // ---------- Cast rail v4 desktop interactions ----------
        // Touch (mobile/iPad) is fully handled by native momentum +
        // proximity snap from the CSS — this IIFE only wires the
        // DESKTOP-specific affordances:
        //   1. Arrow buttons → smooth scrollBy one card-width.
        //      Auto-disable when at the rail's start or end.
        //   2. Mouse drag-to-scroll (Netflix-rail style).
        //      Filtered to e.pointerType === 'mouse' so touch is
        //      untouched. Disables snap during the active drag via
        //      `.is-grabbing`; snap reactivates on pointerup so the
        //      rail still settles softly to the nearest card.
        //   3. Vertical wheel → horizontal scroll. Polite version:
        //      passes through to the page when the rail is at its
        //      start or end, so vertical page-scroll still works
        //      when the user has parked the rail at an edge.
        //   4. Active-card emphasis: IntersectionObserver tags the
        //      most-visible card with `.is-centered` for a subtle
        //      scale + glow boost (CSS does the styling).
        (function setupCastRailInteractions() {
            const wrap = document.querySelector('[data-pt-cast-rail-wrap]');
            if (!wrap) return;
            const rail = wrap.querySelector('[data-pt-cast-rail]');
            if (!rail) return;

            const prevBtn = wrap.querySelector('[data-pt-cast-arrow="prev"]');
            const nextBtn = wrap.querySelector('[data-pt-cast-arrow="next"]');

            // One scroll step = card width + gap. Read live so it
            // adapts to breakpoint changes without a reflow listener.
            const cardStep = () => {
                const card = rail.querySelector('.pt-alebad-cast-card');
                if (!card) return 280;
                const styles = window.getComputedStyle(rail);
                const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
                return card.getBoundingClientRect().width + gap;
            };

            const maxScroll = () => Math.max(0, rail.scrollWidth - rail.clientWidth);
            // `Math.abs` handles Firefox-RTL's negative scrollLeft so the
            // start/end detection works in both directions.
            const pos = () => Math.abs(rail.scrollLeft);

            const updateArrows = () => {
                const max = maxScroll();
                const p = pos();
                // If the rail doesn't actually overflow (e.g. very wide
                // viewport, few cards), hide both arrows — having them
                // visible but inert reads as broken UI.
                if (max < 4) {
                    if (prevBtn) prevBtn.classList.add('is-disabled');
                    if (nextBtn) nextBtn.classList.add('is-disabled');
                    return;
                }
                if (prevBtn) prevBtn.classList.toggle('is-disabled', p < 4);
                if (nextBtn) nextBtn.classList.toggle('is-disabled', p > max - 4);
            };

            // Arrow click → scroll to the NEXT card boundary, not just
            // `scrollBy(cardStep)`. With `scroll-snap-type: proximity`
            // active on the rail, a blind scrollBy can land between
            // snap points; the proximity snap then nudges the rail
            // back to the previous card after the smooth-scroll
            // settles, producing a visible jitter. Aligning explicitly
            // to a card boundary means the snap engine has nothing to
            // fight, so each arrow click resolves cleanly.
            const scrollDir = (dir) => {
                const step = cardStep();
                if (step <= 0) return;
                const max = maxScroll();
                const current = rail.scrollLeft;
                // Add a 4px deadband so a click on a card already
                // aligned advances to the next one, not "0 step".
                const target = dir > 0
                    ? Math.ceil((current + 4) / step) * step
                    : Math.floor((current - 4) / step) * step;
                rail.scrollTo({
                    left: Math.max(0, Math.min(max, target)),
                    behavior: 'smooth',
                });
            };
            if (prevBtn) prevBtn.addEventListener('click', () => scrollDir(-1));
            if (nextBtn) nextBtn.addEventListener('click', () => scrollDir(1));

            // Hide the "اسحب لاكتشاف باقي النجوم" hint once the user
            // has actually scrolled the rail. Pulsing forever would
            // read as nagging; one acknowledgment is enough.
            const hint = wrap.querySelector('.pt-alebad-cast-rail-hint');
            if (hint) {
                const hideHintIfMoved = () => {
                    if (pos() > 24) {
                        hint.classList.add('is-acknowledged');
                        rail.removeEventListener('scroll', hideHintIfMoved);
                    }
                };
                rail.addEventListener('scroll', hideHintIfMoved, { passive: true });
            }

            // Update on scroll (rAF-throttled) and on resize.
            let raf = null;
            const scheduleUpdate = () => {
                if (raf) return;
                raf = requestAnimationFrame(() => { raf = null; updateArrows(); });
            };
            rail.addEventListener('scroll', scheduleUpdate, { passive: true });
            window.addEventListener('resize', scheduleUpdate, { passive: true });
            // Initial paint — wait one frame so layout has settled.
            requestAnimationFrame(updateArrows);

            // Desktop-only behaviors (drag + wheel). Touch keeps the
            // native iOS Safari pipeline.
            const hasFinePointer = window.matchMedia &&
                window.matchMedia('(pointer: fine)').matches;
            if (hasFinePointer) {
                // -- Drag-to-scroll with inertia (mouse only) --
                let isDown = false;
                let startX = 0;
                let startScroll = 0;
                let hasMoved = false;
                let activePointerId = null;
                // Velocity tracker — exponential moving average over
                // the last few pointermove events, in cursor-pixels
                // per ms. On pointerup any residual velocity coasts
                // the scroll for ~400ms with cubic decay, giving the
                // rail a "throw" feel instead of stopping dead at
                // the last cursor position.
                let lastMoveX = 0;
                let lastMoveTime = 0;
                let velocity = 0;
                let inertiaRaf = null;

                const cancelInertia = () => {
                    if (inertiaRaf !== null) {
                        cancelAnimationFrame(inertiaRaf);
                        inertiaRaf = null;
                    }
                };

                rail.addEventListener('pointerdown', (e) => {
                    // Only handle mouse — touch keeps native momentum.
                    if (e.pointerType !== 'mouse') return;
                    // Ignore right/middle clicks.
                    if (e.button !== 0) return;
                    // Kill any in-flight inertia from a previous drag —
                    // a fresh grab should always feel responsive.
                    cancelInertia();
                    isDown = true;
                    hasMoved = false;
                    startX = e.clientX;
                    startScroll = rail.scrollLeft;
                    activePointerId = e.pointerId;
                    lastMoveX = e.clientX;
                    lastMoveTime = performance.now();
                    velocity = 0;
                    rail.classList.add('is-grabbing');
                    try { rail.setPointerCapture(e.pointerId); } catch (_) {}
                });

                rail.addEventListener('pointermove', (e) => {
                    if (!isDown) return;
                    const now = performance.now();
                    const dt = now - lastMoveTime;
                    const dx = e.clientX - startX;
                    if (Math.abs(dx) > 4) hasMoved = true;
                    if (dt > 0) {
                        const instantVel = (e.clientX - lastMoveX) / dt;
                        // EMA smoothing — 0.65 weight on history makes
                        // the velocity stable against single-frame jitter
                        // but still tracks acceleration.
                        velocity = velocity * 0.65 + instantVel * 0.35;
                    }
                    lastMoveX = e.clientX;
                    lastMoveTime = now;
                    // Pulling the cursor right (positive dx) means
                    // dragging the rail's content right = decreasing
                    // scrollLeft. Subtract dx to follow the cursor.
                    rail.scrollLeft = startScroll - dx;
                });

                const endDrag = (e) => {
                    if (!isDown) return;
                    isDown = false;
                    rail.classList.remove('is-grabbing');
                    if (activePointerId !== null) {
                        try { rail.releasePointerCapture(activePointerId); } catch (_) {}
                        activePointerId = null;
                    }

                    // Coast: if release velocity is non-trivial, apply
                    // cubic-decay inertia for a few frames. Threshold of
                    // 0.25 px/ms (= 250 px/s) suppresses inertia on
                    // intentional slow drags — only flick-style throws
                    // coast. ~12ms per frame at 60fps, multiplied
                    // through gives a natural feel similar to native
                    // iOS Photos.
                    if (Math.abs(velocity) > 0.25) {
                        let v = velocity * 16; // px per frame at 60fps
                        const decay = 0.93;
                        const tick = () => {
                            v *= decay;
                            rail.scrollLeft -= v;
                            if (Math.abs(v) > 0.4) {
                                inertiaRaf = requestAnimationFrame(tick);
                            } else {
                                inertiaRaf = null;
                            }
                        };
                        inertiaRaf = requestAnimationFrame(tick);
                    }
                    velocity = 0;
                    // If the user actually dragged (not just clicked),
                    // suppress the synthetic click that follows so
                    // any future click handlers on cards don't fire.
                    if (hasMoved) {
                        const suppress = (ev) => {
                            ev.stopPropagation();
                            ev.preventDefault();
                        };
                        rail.addEventListener('click', suppress, {
                            capture: true, once: true
                        });
                        // Failsafe: detach within a microtask if no
                        // click ever fires.
                        setTimeout(() => {
                            rail.removeEventListener('click', suppress, { capture: true });
                        }, 40);
                    }
                };
                rail.addEventListener('pointerup', endDrag);
                rail.addEventListener('pointercancel', endDrag);

                // -- Wheel-to-horizontal (mouse wheel users) --
                // Trackpads that already generate deltaX pass through
                // untouched. Mouse wheels (deltaY only) get converted.
                // When the rail has reached an edge in the requested
                // direction, we let the wheel bubble up so the page
                // can scroll vertically — otherwise the rail would
                // "trap" page scroll on long pages.
                rail.addEventListener('wheel', (e) => {
                    if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
                    if (e.deltaY === 0) return;
                    const max = maxScroll();
                    const cur = rail.scrollLeft;
                    const atEnd   = e.deltaY > 0 && cur >= max - 0.5;
                    const atStart = e.deltaY < 0 && cur <= 0.5;
                    if (atEnd || atStart) return;
                    e.preventDefault();
                    rail.scrollLeft = cur + e.deltaY;
                }, { passive: false });
            }

            // -- Active-card emphasis --
            // Tag whichever card is ≥85% visible inside the rail's
            // own viewport with `.is-centered`. CSS handles the
            // scale/glow boost (hover: hover only — touch would
            // flicker since you're constantly mid-snap).
            if ('IntersectionObserver' in window) {
                const cards = rail.querySelectorAll('.pt-alebad-cast-card');
                if (cards.length) {
                    const centerIO = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            entry.target.classList.toggle('is-centered',
                                entry.intersectionRatio >= 0.85);
                        });
                    }, {
                        root: rail,
                        threshold: [0.6, 0.85, 0.95],
                    });
                    cards.forEach((c) => centerIO.observe(c));
                }
            }
        })();

        // ---------- Scene 4 — Story: disclosure surfaces (bio + credits) ----------
        // Wires every gold ghost-pill toggle in the Story scene to its
        // panel via aria-controls. Animation is pure-CSS via the
        // grid-template-rows 0fr↔1fr trick — JS just flips:
        //   - aria-expanded on the button (drives label/chevron via CSS)
        //   - .is-open on the panel (drives the height + fade transition)
        // Keyboard handling is free because the trigger is a real <button>.
        // Supports two attribute styles so the new biography disclosure and
        // the legacy Making-Of credits disclosure can coexist without
        // re-flagging the existing markup:
        //   - new generic [data-pt-disclosure-toggle] / [data-pt-disclosure-panel]
        //   - legacy [data-pt-credits-toggle] / [data-pt-credits-panel]
        // No-op when there's no panel on the page (every other route).
        (function setupStoryDisclosures() {
            const toggles = document.querySelectorAll(
                '[data-pt-disclosure-toggle], [data-pt-credits-toggle]'
            );
            if (toggles.length === 0) return;

            toggles.forEach((toggle) => {
                const controlsId = toggle.getAttribute('aria-controls');
                // Two ways to locate the matching panel:
                //   1. preferred: button has aria-controls="<panel-id>"
                //   2. legacy:    nearest [data-pt-credits-panel] in the doc
                let panel = controlsId ? document.getElementById(controlsId) : null;
                if (!panel) {
                    panel = document.querySelector('[data-pt-credits-panel]');
                }
                if (!panel) return;

                const setOpen = (open) => {
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    panel.classList.toggle('is-open', open);
                };

                // Initial state: honor any pre-set aria-expanded from the
                // markup so SSR can ship an already-open variant.
                setOpen(toggle.getAttribute('aria-expanded') === 'true');

                toggle.addEventListener('click', () => {
                    const next = toggle.getAttribute('aria-expanded') !== 'true';
                    setOpen(next);
                });
            });
        })();
    })();

    // ---------- intercept forms with data-pt-confirm ----------
    (function () {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            const cfg = form.getAttribute('data-pt-confirm');
            if (!cfg || form.dataset.ptConfirmed === '1') return;
            e.preventDefault();
            let opts;
            try { opts = JSON.parse(cfg); } catch (_) { opts = { title: cfg }; }
            const tone = opts.tone || 'warn';
            // i18nKeys lets callers translate title/body/labels at runtime
            // without committing English copy into the markup. Resolution
            // order: i18nKeys[field] (translated) → opts[field] (literal AR) → safe default.
            const k = (opts.i18nKeys || {});
            const tr = (key, fallback) => key ? window.PT.t(key) || fallback : fallback;
            const title = tr(k.title, opts.title)
                || (window.PT.lang() === 'en' ? 'Are you sure?' : 'هل أنت متأكد؟');
            const body  = tr(k.body, opts.body) || '';
            const okLabel = tr(k.okLabel, opts.okLabel)
                || (window.PT.lang() === 'en' ? 'Continue' : 'متابعة');
            const cancelLabel = tr(k.cancelLabel, opts.cancelLabel)
                || (window.PT.lang() === 'en' ? 'Cancel' : 'إلغاء');
            const okVariant = opts.okVariant || 'emerald';
            window.PT.modal.open({
                tone, title, body,
                actions: [
                    { label: cancelLabel, variant: 'ghost' },
                    { label: okLabel, variant: okVariant, onClick: () => {
                        // show loading and then submit
                        window.PT.modal.open({
                            tone: 'info',
                            title: window.PT.t('modal_processing'),
                            body: window.PT.t('modal_processing_body'),
                            actions: []
                        });
                        // tell modal icon to show spinner
                        const ic = document.getElementById('pt-modal-icon');
                        ic.innerHTML = '<div class="pt-modal-spinner"></div>';
                        form.dataset.ptConfirmed = '1';
                        setTimeout(() => form.submit(), 60);
                        return false;
                    } }
                ]
            });
        });
    })();
    </script>

    @stack('scripts')
