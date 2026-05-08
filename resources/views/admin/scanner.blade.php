@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3 pb-10 prism-fade-up">

    {{-- HEADER --}}
    <div class="prism-glass prism-glow-border p-4 flex justify-between items-center gap-3">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                Gate Scanner
            </span>
            <h1 class="prism-headline text-base">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    🎫 Gate Scanner
                </span>
            </h1>
        </div>

        {{-- Back link: dashboard for authenticated admins, home for the
             door staff using a shared device (the scanner is now public). --}}
        @auth
            <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true">→</span>
                رجوع
            </a>
        @else
            <a href="{{ url('/') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true">→</span>
                رجوع
            </a>
        @endauth
    </div>

    {{-- RESULT --}}
    <div id="card"
         class="hidden prism-glass p-3 text-sm space-y-2">
    </div>

    {{-- SCANNER --}}
    <div class="relative prism-glass p-3 overflow-hidden"
         style="border-radius: 24px;">

        <div id="qr-reader"
             class="rounded-2xl overflow-hidden"
             style="border: 1px solid var(--prism-border);"></div>

        {{-- FRAME --}}
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="scan-frame"></div>
        </div>

        {{-- LINE --}}
        <div class="scan-line"></div>

        {{-- STATUS OVERLAY TOP --}}
        <div id="status"
             class="absolute top-5 left-1/2 -translate-x-1/2 z-50
                    px-4 py-2 rounded-full
                    text-sm
                    transition-all duration-300"
             style="background: rgba(8,10,20,0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); color: var(--prism-text); border: 1px solid var(--prism-border-strong); box-shadow: 0 8px 24px -8px rgba(129,140,248,0.4);">
            جاهز للفحص
        </div>
    </div>

    {{-- CONTROLS --}}
    <div class="flex gap-2">

        <button id="flashBtn" class="flex-1 prism-btn-ghost text-xs py-3">
            🔦 Flash
        </button>

        <button onclick="location.reload()" class="flex-1 prism-btn-ghost text-xs py-3">
            🔄 Restart
        </button>

    </div>

</section>

{{-- FLASH EFFECT --}}
<div id="flash" class="fixed inset-0 flex items-center justify-center hidden z-50"
     style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
    <div id="flashIcon" class="text-8xl font-black"></div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-frame{
    width:230px;
    height:230px;
    border:2px solid rgba(34,211,238,0.55);
    border-radius:24px;
    box-shadow: 0 0 30px rgba(34,211,238,0.25), inset 0 0 20px rgba(129,140,248,0.18);
}

.scan-line{
    position:absolute;
    left:10%;
    right:10%;
    height:2px;
    background:linear-gradient(90deg, transparent, #22d3ee 30%, #818cf8 50%, #c084fc 70%, transparent);
    box-shadow: 0 0 12px rgba(34,211,238,0.7);
    animation:scan 1.4s cubic-bezier(.2,.7,.2,1) infinite;
}
@keyframes scan{
    0%   { top:10%; opacity: 0.6; }
    50%  { top:85%; opacity: 1;   }
    100% { top:10%; opacity: 0.6; }
}

.flash-ok{    color:#34d399; text-shadow: 0 0 50px #34d399, 0 0 100px rgba(52,211,153,0.5); }
.flash-used{ color:#fbbf24; text-shadow: 0 0 50px #fbbf24, 0 0 100px rgba(251,191,36,0.5); }
.flash-error{ color:#fb7185; text-shadow: 0 0 50px #fb7185, 0 0 100px rgba(251,113,133,0.5); }

.glow-green{  box-shadow: 0 0 25px rgba(52,211,153,.6); }
.glow-yellow{ box-shadow: 0 0 25px rgba(251,191,36,.6); }
.glow-red{    box-shadow: 0 0 25px rgba(251,113,133,.6); }
</style>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy = false;
let lastCode = null;
let lastScanTime = 0;

const COOLDOWN = 3000;

// 🔊 SOUND (fixed)
let audioCtx;
function beep(type){
    try{
        if(!audioCtx){
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }

        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.frequency.value =
            type === 'ok' ? 950 :
            type === 'used' ? 500 : 250;

        gain.gain.value = 0.25;

        osc.start();
        setTimeout(()=>osc.stop(),150);

    }catch(e){}
}

// 📳 vibration
function vibrate(type){
    if(type==='ok') navigator.vibrate?.(120);
    else if(type==='used') navigator.vibrate?.([100,50,100]);
    else navigator.vibrate?.(200);
}

// 💡 flash overlay
function flash(type){
    const f = document.getElementById('flash');
    const i = document.getElementById('flashIcon');

    f.classList.remove('hidden');

    if(type==='ok'){ i.textContent='✓'; i.className='flash-ok'; }
    if(type==='used'){ i.textContent='!'; i.className='flash-used'; }
    if(type==='error'){ i.textContent='✕'; i.className='flash-error'; }

    setTimeout(()=>f.classList.add('hidden'),700);
}

// 🎯 UI — PRISM-themed status pill
function setStatus(text,type){
    const s = document.getElementById('status');

    s.innerText = text;

    s.className = "absolute top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm border transition-all duration-300";

    if(type==='ok'){
        s.style.background = 'rgba(16,185,129,0.85)';
        s.style.borderColor = 'rgba(110,231,183,0.7)';
        s.style.color = '#022c22';
        s.style.boxShadow = '0 0 24px rgba(52,211,153,0.55)';
    }
    else if(type==='used'){
        s.style.background = 'rgba(251,191,36,0.9)';
        s.style.borderColor = 'rgba(254,240,138,0.7)';
        s.style.color = '#1b1208';
        s.style.boxShadow = '0 0 24px rgba(251,191,36,0.55)';
    }
    else{
        s.style.background = 'rgba(244,63,94,0.85)';
        s.style.borderColor = 'rgba(253,164,175,0.7)';
        s.style.color = '#fff1f2';
        s.style.boxShadow = '0 0 24px rgba(251,113,133,0.55)';
    }

    // pop animation
    s.style.transform = "translate(-50%, -5px) scale(1.1)";
    setTimeout(()=>{
        s.style.transform = "translate(-50%, 0) scale(1)";
    },150);
}

// 📊 render — PRISM-themed result card
function render(d){
    const c = document.getElementById('card');
    c.classList.remove('hidden');

    c.innerHTML = `
        <div class="space-y-2">

            <!-- 👤 NAME -->
            <div style="color: var(--prism-text);" class="font-semibold text-sm tracking-wide">
                ${d.name}
            </div>

            <div style="border-top: 1px solid var(--prism-border);" class="my-1"></div>

            <!-- 🎭 SHOW -->
            <div style="color: var(--prism-text-2);" class="text-[12px]">
                🎭 ${d.show_title}
            </div>

            <!-- 🕒 DATE & TIME -->
            <div class="text-[12px] font-medium" style="color: var(--prism-cyan);">
                🕒 ${d.date} • ${d.time}
            </div>

            ${
                d.scanned_at
                ? `<div class="text-[12px] font-semibold" style="color: var(--prism-emerald);">
                        ✅ دخل: ${d.scanned_at}
                   </div>`
                : ''
            }

        </div>
    `;
}

// 🚀 request
function check(code){

    fetch('/admin/scanner/check',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body:JSON.stringify({code})
    })
    .then(r=>r.json())
    .then(d=>{

        if(d.status==='ok'){
            setStatus('✅ دخول مسموح','ok');
            vibrate('ok');
            beep('ok');
            flash('ok');
            render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة','used');
            vibrate('used');
            beep('used');
            flash('used');
            render(d);
        }
        else{
            setStatus('❌ غير صالح','error');
            vibrate('error');
            beep('error');
            flash('error');
        }

    })
    .finally(()=>{
        setTimeout(()=>busy=false,800);
    });
}

// 📸 START (FAST + STABLE)
qr.start(
    { facingMode: "environment" },
    {
        fps: 12,
        qrbox: 260
    },
    text=>{
        const now = Date.now();

        if(text === lastCode && now - lastScanTime < COOLDOWN){
            return;
        }

        if(busy) return;

        busy = true;
        lastCode = text;
        lastScanTime = now;

        check(text);
    }
);

// 🔦 Flash control
let flashOn = false;
document.getElementById('flashBtn').onclick = async () => {
    try{
        flashOn = !flashOn;
        await qr.applyVideoConstraints({
            advanced: [{ torch: flashOn }]
        });
    }catch(e){
        alert('الفلاش غير مدعوم');
    }
};
</script>
@endsection
