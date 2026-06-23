<section class="{{ ($withLayout ?? false) ? 'p-4 lg:p-6' : 'min-h-screen p-4 lg:p-6 flex items-center justify-center' }}">
    <div class="w-full {{ ($withLayout ?? false) ? 'max-w-[1600px] mx-auto' : 'max-w-6xl mx-auto' }}">

        <div class="error-space theme-card theme-shadow relative overflow-hidden rounded-[2rem] border theme-border {{ ($withLayout ?? false) ? 'min-h-[calc(100vh-8rem)]' : 'min-h-[620px]' }}">

            {{-- Luna --}}
            <div class="space-moon"></div>
            <div class="space-moon-crater space-moon-crater-1"></div>
            <div class="space-moon-crater space-moon-crater-2"></div>
            <div class="space-moon-crater space-moon-crater-3"></div>

            {{-- Estrellas --}}
            <div class="space-star space-star-1"></div>
            <div class="space-star space-star-2"></div>
            <div class="space-star space-star-3"></div>
            <div class="space-star space-star-4"></div>
            <div class="space-star space-star-5"></div>
            <div class="space-star space-star-6"></div>

            <div class="relative z-20 grid grid-cols-1 xl:grid-cols-2 gap-10 {{ ($withLayout ?? false) ? 'min-h-[calc(100vh-8rem)]' : 'min-h-[620px]' }} items-center px-6 md:px-12 py-12">

                {{-- Texto --}}
                <div class="max-w-xl">
                    <span class="theme-badge inline-flex px-4 py-1 rounded-full text-xs font-bold border mb-5">
                        Error {{ $code }}
                    </span>

                    <h1 class="error-code">
                        {{ $code }}
                    </h1>

                    <h2 class="theme-title text-3xl md:text-4xl font-extrabold mt-2">
                        {{ $title }}
                    </h2>

                   <p class="theme-text text-base mt-8 leading-relaxed">
    {{ $description }}
</p>

                    
                </div>

                {{-- Astronauta --}}
                <div class="hidden xl:flex items-center justify-center">
                    <div class="astronaut">
                        <div class="astronaut-cord"></div>

                        <div class="astronaut-backpack"></div>
                        <div class="astronaut-body"></div>
                        <div class="astronaut-chest"></div>

                        <div class="astronaut-arm-left-1"></div>
                        <div class="astronaut-arm-left-2"></div>
                        <div class="astronaut-arm-right-1"></div>
                        <div class="astronaut-arm-right-2"></div>

                        <div class="astronaut-leg-left"></div>
                        <div class="astronaut-leg-right"></div>
                        <div class="astronaut-foot-left"></div>
                        <div class="astronaut-foot-right"></div>

                    

                        {{-- Tabaco --}}
                        <div class="astronaut-cigar">
                            <span class="astronaut-cigar-band"></span>
                            <span class="astronaut-cigar-tip"></span>
                        </div>

                        {{-- Humo del tabaco --}}
                        <div class="astronaut-smoke astronaut-smoke-1"></div>
<div class="astronaut-smoke astronaut-smoke-2"></div>
<div class="astronaut-smoke astronaut-smoke-3"></div>
<div class="astronaut-smoke astronaut-smoke-4"></div>
<div class="astronaut-smoke astronaut-smoke-5"></div>
<div class="astronaut-smoke astronaut-smoke-6"></div>
<div class="astronaut-smoke astronaut-smoke-7"></div>

                        <div class="astronaut-head">
                            <div class="astronaut-visor"></div>
                            <div class="astronaut-visor-flare-1"></div>
                            <div class="astronaut-visor-flare-2"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<style>
    .error-space {
        background:
            radial-gradient(circle at 15% 20%, rgba(148, 163, 184, .22), transparent 28%),
            radial-gradient(circle at 90% 80%, rgba(56, 189, 248, .12), transparent 32%),
            linear-gradient(135deg, #ffffff 0%, #f8fafc 42%, #e2e8f0 100%);
    }

    html.dark-navy .error-space {
        background:
            radial-gradient(circle at 18% 18%, rgba(56, 189, 248, .11), transparent 30%),
            radial-gradient(circle at 90% 80%, rgba(56, 189, 248, .08), transparent 35%),
            linear-gradient(135deg, #0b1220 0%, #0f172a 45%, #111c33 100%);
    }

    .error-code {
        font-size: clamp(5rem, 14vw, 12rem);
        line-height: .85;
        font-weight: 900;
        letter-spacing: -0.08em;
        color: #0b1220;
        text-shadow: 0 18px 45px rgba(15, 23, 42, .18);
    }

    html.dark-navy .error-code {
        color: #e5e7eb;
        text-shadow: 0 18px 50px rgba(56, 189, 248, .16);
    }

    .space-moon {
        position: absolute;
        top: -250px;
        left: -260px;
        width: 640px;
        height: 640px;
        border-radius: 9999px;
        background: radial-gradient(circle at 35% 30%, #ffffff 0%, #e5e7eb 45%, #94a3b8 100%);
        opacity: .85;
        box-shadow: 0 0 55px rgba(15, 23, 42, .20);
        z-index: 1;
    }

    html.dark-navy .space-moon {
        background: radial-gradient(circle at 35% 30%, #e5e7eb 0%, #94a3b8 48%, #475569 100%);
        opacity: .22;
        box-shadow: 0 0 70px rgba(56, 189, 248, .18);
    }

    .space-moon-crater {
        position: absolute;
        border-radius: 9999px;
        background: linear-gradient(90deg, rgba(71, 85, 105, .35), rgba(226, 232, 240, .55));
        z-index: 2;
        opacity: .45;
    }

    .space-moon-crater-1 {
        top: 90px;
        left: 230px;
        width: 42px;
        height: 120px;
        transform: rotate(18deg);
    }

    .space-moon-crater-2 {
        top: 260px;
        left: 110px;
        width: 38px;
        height: 75px;
        transform: rotate(55deg);
    }

    .space-moon-crater-3 {
        top: 60px;
        left: 40px;
        width: 55px;
        height: 95px;
        transform: rotate(250deg);
    }

    .space-star {
        position: absolute;
        width: 5px;
        height: 5px;
        border-radius: 9999px;
        background: #94a3b8;
        opacity: .4;
        animation: space-shimmer 1.6s infinite alternate;
        z-index: 1;
    }

    html.dark-navy .space-star {
        background: #38bdf8;
    }

    @keyframes space-shimmer {
        from {
            opacity: .1;
            transform: scale(.8);
        }

        to {
            opacity: .85;
            transform: scale(1.25);
        }
    }

    .space-star-1 { top: 18%; left: 52%; animation-delay: .3s; }
    .space-star-2 { top: 34%; left: 82%; animation-delay: .9s; }
    .space-star-3 { top: 70%; left: 88%; animation-delay: 1.3s; }
    .space-star-4 { top: 84%; left: 42%; animation-delay: .6s; }
    .space-star-5 { top: 15%; left: 72%; animation-delay: 1.7s; }
    .space-star-6 { top: 55%; left: 64%; animation-delay: 2s; }

    .astronaut {
        position: relative;
        width: 220px;
        height: 330px;
        transform: rotate(16deg) scale(1.1);
        animation: astronaut-float 4.5s ease-in-out infinite;
        z-index: 10;
    }

    @keyframes astronaut-float {
        0%, 100% {
            transform: translateY(0) rotate(16deg) scale(1.1);
        }

        50% {
            transform: translateY(-22px) rotate(10deg) scale(1.1);
        }
    }

    .astronaut-cord {
        position: absolute;
        width: 260px;
        height: 150px;
        left: -190px;
        top: 90px;
        border-top: 7px solid rgba(148, 163, 184, .75);
        border-radius: 50%;
        transform: rotate(-18deg);
    }

    html.dark-navy .astronaut-cord {
        border-color: rgba(226, 232, 240, .45);
    }

    .astronaut-head {
        position: absolute;
        top: 40px;
        left: 82px;
        width: 70px;
        height: 70px;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: inset -8px -8px 0 rgba(148, 163, 184, .35);
        z-index: 5;
    }

    .astronaut-visor {
        position: absolute;
        left: 10px;
        top: 14px;
        width: 50px;
        height: 42px;
        background: linear-gradient(135deg, #0b1220, #1e293b);
        border-radius: 18px 18px 22px 22px;
        border: 3px solid #e5e7eb;
        overflow: hidden;
    }

    html.dark-navy .astronaut-visor {
        background: linear-gradient(135deg, #082f49, #0f172a);
        box-shadow: inset 0 0 18px rgba(56, 189, 248, .25);
    }

    .astronaut-visor-flare-1 {
        position: absolute;
        top: 26px;
        left: 46px;
        width: 10px;
        height: 10px;
        border-radius: 9999px;
        background: #38bdf8;
        opacity: .65;
        z-index: 6;
    }

    .astronaut-visor-flare-2 {
        position: absolute;
        top: 40px;
        left: 43px;
        width: 5px;
        height: 5px;
        border-radius: 9999px;
        background: #7dd3fc;
        opacity: .55;
        z-index: 6;
    }

    .astronaut-backpack {
        position: absolute;
        top: 86px;
        left: 64px;
        width: 105px;
        height: 105px;
        background: #cbd5e1;
        border-radius: 16px;
        box-shadow: inset -8px -8px 0 rgba(100, 116, 139, .25);
    }

    .astronaut-body {
        position: absolute;
        top: 112px;
        left: 75px;
        width: 82px;
        height: 92px;
        background: #f8fafc;
        border-radius: 16px;
        box-shadow: inset -8px -8px 0 rgba(148, 163, 184, .35);
        z-index: 3;
    }

    .astronaut-chest {
        position: absolute;
        top: 142px;
        left: 92px;
        width: 48px;
        height: 28px;
        border-radius: 8px;
        background: #cbd5e1;
        z-index: 4;
    }

    .astronaut-arm-left-1,
    .astronaut-arm-right-1,
    .astronaut-arm-left-2,
    .astronaut-arm-right-2,
    .astronaut-leg-left,
    .astronaut-leg-right {
        position: absolute;
        background: #f8fafc;
        box-shadow: inset -5px -5px 0 rgba(148, 163, 184, .28);
        z-index: 2;
    }

    .astronaut-arm-left-1 {
        top: 126px;
        left: 28px;
        width: 66px;
        height: 22px;
        border-radius: 10px;
        transform: rotate(-28deg);
    }

    .astronaut-arm-left-2 {
        top: 102px;
        left: 24px;
        width: 22px;
        height: 52px;
        border-radius: 12px;
        transform: rotate(-10deg);
    }

    .astronaut-arm-right-1 {
        top: 116px;
        left: 145px;
        width: 70px;
        height: 22px;
        border-radius: 10px;
        transform: rotate(-10deg);
    }

    .astronaut-arm-right-2 {
        top: 78px;
        left: 191px;
        width: 22px;
        height: 54px;
        border-radius: 12px;
        transform: rotate(-10deg);
    }

    .astronaut-wrist-left,
    .astronaut-wrist-right {
        position: absolute;
        width: 24px;
        height: 5px;
        border-radius: 9999px;
        background: #38bdf8;
        z-index: 5;
    }

    .astronaut-wrist-left {
        top: 121px;
        left: 25px;
        transform: rotate(-14deg);
    }

    .astronaut-wrist-right {
        top: 98px;
        left: 190px;
        transform: rotate(-10deg);
    }

    .astronaut-leg-left {
        top: 196px;
        left: 73px;
        width: 26px;
        height: 82px;
        transform: rotate(9deg);
    }

    .astronaut-leg-right {
        top: 196px;
        left: 139px;
        width: 26px;
        height: 82px;
        transform: rotate(-9deg);
    }

    .astronaut-foot-left,
    .astronaut-foot-right {
        position: absolute;
        top: 260px;
        width: 34px;
        height: 23px;
        background: #ffffff;
        border-radius: 12px 12px 5px 5px;
        border-bottom: 5px solid #38bdf8;
        z-index: 4;
    }

    .astronaut-foot-left {
        left: 64px;
        transform: rotate(9deg);
    }

    .astronaut-foot-right {
        left: 139px;
        transform: rotate(-9deg);
    }

    /* ================================
       Tabaco en la mano
    ================================ */

    .astronaut-cigar {
        position: absolute;
        top: 75px;
        left: 190px;
        width: 56px;
        height: 13px;
        background: linear-gradient(90deg, #653817 0%, #8f5327 45%, #b9783f 100%);
        border-radius: 999px;
        transform: rotate(-10deg);
        box-shadow: 0 0 0 2px rgba(15, 23, 42, .13);
        z-index: 30;
    }

    .astronaut-cigar-band {
        position: absolute;
        left: 16px;
        top: 0;
        width: 6px;
        height: 13px;
        background: #f5ea58;
        border-radius: 2px;
    }

    .astronaut-cigar-tip {
        position: absolute;
        right: -4px;
        top: 2px;
        width: 10px;
        height: 9px;
        background: #ff6b4a;
        border-radius: 0 999px 999px 0;
        box-shadow:
            0 0 10px rgba(255, 107, 74, .8),
            0 0 18px rgba(255, 107, 74, .45);
    }

   /* ================================
   Humo del tabaco abundante
================================ */

.astronaut-smoke {
    position: absolute;
    border: 4px solid transparent;
    border-top-color: rgba(203, 213, 225, .75);
    border-left-color: rgba(203, 213, 225, .75);
    border-radius: 50%;
    z-index: 29;
    pointer-events: none;
    filter: blur(.35px);
}

html.dark-navy .astronaut-smoke {
    border-top-color: rgba(125, 211, 252, .52);
    border-left-color: rgba(125, 211, 252, .52);
}

.astronaut-smoke-1 {
    top: 58px;
    left: 236px;
    width: 20px;
    height: 20px;
    animation: smoke-float-1 2.4s ease-in-out infinite;
}

.astronaut-smoke-2 {
    top: 42px;
    left: 252px;
    width: 31px;
    height: 31px;
    animation: smoke-float-2 2.9s ease-in-out infinite .25s;
}

.astronaut-smoke-3 {
    top: 24px;
    left: 226px;
    width: 27px;
    height: 27px;
    animation: smoke-float-3 3.2s ease-in-out infinite .55s;
}

.astronaut-smoke-4 {
    top: 5px;
    left: 260px;
    width: 36px;
    height: 36px;
    animation: smoke-float-4 3.6s ease-in-out infinite .85s;
}

.astronaut-smoke-5 {
    top: 35px;
    left: 278px;
    width: 24px;
    height: 24px;
    opacity: .65;
    animation: smoke-float-5 2.8s ease-in-out infinite .45s;
}

.astronaut-smoke-6 {
    top: 12px;
    left: 238px;
    width: 38px;
    height: 38px;
    opacity: .55;
    animation: smoke-float-6 4s ease-in-out infinite 1.1s;
}

.astronaut-smoke-7 {
    top: -8px;
    left: 280px;
    width: 30px;
    height: 30px;
    opacity: .5;
    animation: smoke-float-7 4.3s ease-in-out infinite 1.45s;
}

@keyframes smoke-float-1 {
    0% {
        opacity: 0;
        transform: rotate(35deg) translate(0, 0) scale(.65);
    }

    22% {
        opacity: .8;
    }

    100% {
        opacity: 0;
        transform: rotate(35deg) translate(12px, -38px) scale(1.2);
    }
}

@keyframes smoke-float-2 {
    0% {
        opacity: 0;
        transform: rotate(42deg) translate(0, 0) scale(.75);
    }

    28% {
        opacity: .7;
    }

    100% {
        opacity: 0;
        transform: rotate(42deg) translate(22px, -48px) scale(1.35);
    }
}

@keyframes smoke-float-3 {
    0% {
        opacity: 0;
        transform: rotate(28deg) translate(0, 0) scale(.7);
    }

    28% {
        opacity: .6;
    }

    100% {
        opacity: 0;
        transform: rotate(28deg) translate(-16px, -46px) scale(1.3);
    }
}

@keyframes smoke-float-4 {
    0% {
        opacity: 0;
        transform: rotate(40deg) translate(0, 0) scale(.7);
    }

    30% {
        opacity: .58;
    }

    100% {
        opacity: 0;
        transform: rotate(40deg) translate(24px, -58px) scale(1.45);
    }
}

@keyframes smoke-float-5 {
    0% {
        opacity: 0;
        transform: rotate(22deg) translate(0, 0) scale(.65);
    }

    30% {
        opacity: .55;
    }

    100% {
        opacity: 0;
        transform: rotate(22deg) translate(30px, -42px) scale(1.25);
    }
}

@keyframes smoke-float-6 {
    0% {
        opacity: 0;
        transform: rotate(55deg) translate(0, 0) scale(.8);
    }

    32% {
        opacity: .52;
    }

    100% {
        opacity: 0;
        transform: rotate(55deg) translate(-10px, -64px) scale(1.55);
    }
}

@keyframes smoke-float-7 {
    0% {
        opacity: 0;
        transform: rotate(35deg) translate(0, 0) scale(.7);
    }

    34% {
        opacity: .48;
    }

    100% {
        opacity: 0;
        transform: rotate(35deg) translate(30px, -70px) scale(1.45);
    }
}
    @media (max-width: 768px) {
        .space-moon {
            width: 420px;
            height: 420px;
            top: -210px;
            left: -220px;
        }

        .error-code {
            font-size: clamp(4.5rem, 24vw, 8rem);
        }
    }
</style>