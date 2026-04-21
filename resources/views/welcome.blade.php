<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DriveEase — Rental Mobil Premium Indonesia</title>
    <meta name="description" content="Rental mobil premium dengan pengemudi profesional. Perjalanan nyaman, aman, dan tepat waktu di seluruh Indonesia.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ══════════════════════════════════════
           DESIGN TOKENS
        ══════════════════════════════════════ */
        :root {
            --ink:      rgb(17 24 39);
            --ink-80:   rgba(17,24,39,0.8);
            --ink-40:   rgba(17,24,39,0.4);
            --ink-12:   rgba(17,24,39,0.12);
            --ink-06:   rgba(17,24,39,0.06);
            --white:    #ffffff;
            --off:      #F7F7F5;
            --line:     rgba(17,24,39,0.1);
            --ease:     cubic-bezier(0.76, 0, 0.24, 1);
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* ══════════════════════════════════════
           RESET & BASE
        ══════════════════════════════════════ */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--white);
            color: var(--ink);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }

        /* ══════════════════════════════════════
           NAVBAR
        ══════════════════════════════════════ */
        #navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 0 5%;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            transition: background 0.4s var(--ease), box-shadow 0.4s;
        }
        #navbar.scrolled {
            background: rgba(255,255,255,0.96);
            box-shadow: 0 1px 0 var(--line);
            backdrop-filter: blur(12px);
        }
        #navbar.hero-over {
            background: transparent;
        }

        /* ── Logo blob (di luar navbar, layer di bawah navbar) ── */
        .nav-logo-blob {
            position: fixed;
            top: 0;
            left: 5%;
            width: 140px;
            height: 120px;
            background: rgba(255, 255, 255, 0.16);
            border-radius: 10% 10% 50% 50%;
            box-shadow: 0 6px 24px rgba(0,0,0,0.10);
            z-index: 998;                       /* di bawah navbar → tertutup navbar bagian atas */
            pointer-events: none;
            transition: background 0.4s var(--ease), box-shadow 0.4s;
        }
        .nav-logo-blob.scrolled {
            box-shadow: none;            
            background: rgb(255, 255, 255);  
            backdrop-filter: blur(12px);                  /* hilang saat navbar sudah scrolled, agar tidak mengganggu estetika */
                   /* menyatu sempurna dengan navbar saat scroll */
        }

        /* ── Logo image link (z-index paling atas) ── */
        .nav-logo-link {
            position: fixed;
            top: 1px;
            left: 5%;
            width: 140px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1001;
        }
        .nav-logo-link img {
            width: 102px;
            height: 102px;
            object-fit: contain;
            border-radius: 50%;
            margin-top: 0px;                   /* ikuti lekukan blob ke bawah */
        }

        /* ── Spacer kiri navbar ── */
        .nav-logo-spacer {
            width: 90px;
            flex-shrink: 0;
        }

        /* ── Nav links ── */
        nav ul { display: flex; list-style: none; gap: 2.4rem; }
        nav ul li a {
            font-size: 0.84rem;
            font-weight: 500;
            color: rgba(255,255,255,0.72);
            letter-spacing: 0.01em;
            transition: color 0.22s;
        }
        #navbar.scrolled nav ul li a { color: var(--ink-80); }
        nav ul li a:hover { color: var(--white); }
        #navbar.scrolled nav ul li a:hover { color: var(--ink); }

        /* ── Nav right buttons ── */
        .nav-right { display: flex; align-items: center; gap: 0.5rem; }
        .nav-btn {
            font-family: 'Syne', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0.55rem 1.3rem;
            border-radius: 4px;
            transition: all 0.24s;
            letter-spacing: 0.01em;
        }
        .nav-btn-ghost {
            color: rgba(255,255,255,0.72);
            border: 1px solid rgba(255,255,255,0.2);
        }
        #navbar.scrolled .nav-btn-ghost { color: var(--ink-80); border-color: var(--line); }
        .nav-btn-ghost:hover { background: rgba(255,255,255,0.1); color: var(--white); }
        #navbar.scrolled .nav-btn-ghost:hover { background: var(--ink-06); color: var(--ink); }
        .nav-btn-solid { background: var(--white); color: var(--ink); }
        #navbar.scrolled .nav-btn-solid { background: var(--ink); color: var(--white); }
        .nav-btn-solid:hover { opacity: 0.88; transform: translateY(-1px); }

        /* ── Hamburger ── */
        .nav-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 4px;
        }
        .nav-hamburger span {
            width: 24px; height: 2px;
            background: var(--white);
            border-radius: 2px;
            transition: all 0.3s var(--ease);
        }
        #navbar.scrolled .nav-hamburger span { background: var(--ink); }
        .nav-hamburger.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .nav-hamburger.open span:nth-child(2) { opacity: 0; }
        .nav-hamburger.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        /* ══════════════════════════════════════
           MOBILE MENU — FULL SCREEN
        ══════════════════════════════════════ */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--white);
            z-index: 997;
            padding: 100px 8% 3rem;
            flex-direction: column;
            justify-content: space-between;
            transform: translateX(100%);
            opacity: 0;
            transition: transform 0.4s var(--ease), opacity 0.4s var(--ease);
            pointer-events: none;
            overflow-y: auto;
        }
        .mobile-menu.open {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }
        .mobile-menu ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0;
            flex: 1;
        }
        .mobile-menu ul li a {
            display: block;
            padding: 1.1rem 0;
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--ink);
            border-bottom: 1px solid var(--line);
            letter-spacing: -0.02em;
            transition: color 0.2s, padding-left 0.2s;
        }
        .mobile-menu ul li a:hover {
            color: var(--accent, #2563eb);
            padding-left: 0.5rem;
        }
        .mobile-menu-actions {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-top: 2rem;
        }
        .mobile-menu-actions a {
            display: block;
            padding: 1rem;
            text-align: center;
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 6px;
        }
        .mob-btn-outline { border: 1.5px solid var(--line); color: var(--ink); }
        .mob-btn-fill { background: var(--ink); color: var(--white); }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            nav, .nav-right .nav-btn { display: none; }
            .nav-hamburger { display: flex; }
            .mobile-menu { display: flex; }
        }
        /* ══════════════════════════════════════
           HERO SLIDER
        ══════════════════════════════════════ */
        .hero {
            position: relative;
            height: 100vh;
            min-height: 620px;
            overflow: hidden;
            background: var(--ink);
        }

        /* Slides */
        .hero-slides {
            position: absolute;
            inset: 0;
        }
        .hero-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1.1s var(--ease);
        }
        .hero-slide.active { opacity: 1; }
        .hero-slide::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                110deg,
                rgba(17,24,39,0.88) 0%,
                rgba(17,24,39,0.55) 52%,
                rgba(17,24,39,0.22) 100%
            );
            z-index: 1;
        }
        .hero-slide img {
            width: 100%; height: 100%;
            object-fit: cover;
            transform: scale(1.06);
            transition: transform 6.5s var(--ease);
        }
        .hero-slide.active img { transform: scale(1); }

        /* Content */
        .hero-body {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 0 8% 8%;
        }

        .hero-slide-label {
            font-family: 'Syne', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            margin-bottom: 1.4rem;
            opacity: 0;
            transform: translateY(14px);
            animation: slideUp 0.8s var(--ease-out) 0.3s forwards;
        }
        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.8rem, 7.5vw, 6.5rem);
            font-weight: 800;
            color: var(--white);
            line-height: 0.94;
            letter-spacing: -0.03em;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(22px);
            animation: slideUp 0.9s var(--ease-out) 0.5s forwards;
        }
        .hero-title em {
            font-style: italic;
            font-weight: 300;
        }
        .hero-desc {
            font-size: 1rem;
            color: rgba(255,255,255,0.6);
            line-height: 1.75;
            max-width: 480px;
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(18px);
            animation: slideUp 0.9s var(--ease-out) 0.7s forwards;
        }
        .hero-actions {
            display: flex;
            gap: 0.85rem;
            flex-wrap: wrap;
            align-items: center;
            opacity: 0;
            transform: translateY(16px);
            animation: slideUp 0.9s var(--ease-out) 0.9s forwards;
        }
        .btn-hero-fill {
            font-family: 'Syne', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 0.9rem 2.1rem;
            background: var(--white);
            color: var(--ink);
            border-radius: 4px;
            letter-spacing: 0.01em;
            transition: all 0.28s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-hero-fill:hover { background: #e8e8e8; transform: translateY(-2px); }
        .btn-hero-border {
            font-family: 'Syne', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0.88rem 2.1rem;
            border: 1.5px solid rgba(255,255,255,0.28);
            color: rgba(255,255,255,0.82);
            border-radius: 4px;
            transition: all 0.28s;
        }
        .btn-hero-border:hover { border-color: rgba(255,255,255,0.7); color: var(--white); }

        /* Slider controls */
        .hero-controls {
            position: absolute;
            bottom: 6%;
            right: 8%;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1.2rem;
            opacity: 0;
            animation: fadeIn 0.8s ease 1.3s forwards;
        }
        .hero-dots {
            display: flex;
            gap: 0.4rem;
        }
        .hero-dot {
            width: 20px; height: 2px;
            background: rgba(255,255,255,0.28);
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .hero-dot.active {
            width: 40px;
            background: var(--white);
        }
        .hero-arrows {
            display: flex;
            gap: 0.4rem;
        }
        .hero-arrow {
            width: 44px; height: 44px;
            border: 1px solid rgba(255,255,255,0.22);
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
            transition: all 0.24s;
            background: transparent;
        }
        .hero-arrow:hover { background: rgba(255,255,255,0.12); color: var(--white); border-color: rgba(255,255,255,0.5); }

        /* Slide counter */
        .hero-counter {
            position: absolute;
            top: 50%;
            right: 8%;
            transform: translateY(-50%);
            z-index: 10;
            writing-mode: vertical-rl;
            font-family: 'Syne', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.36);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            animation: fadeIn 0.8s ease 1.5s forwards;
        }
        .hero-counter-line {
            width: 1px;
            height: 50px;
            background: rgba(255,255,255,0.18);
        }

        /* ══════════════════════════════════════
           TICKER / TRUST BAR
        ══════════════════════════════════════ */
        .ticker {
            background: var(--ink);
            padding: 1.1rem 0;
            overflow: hidden;
            position: relative;
        }
        .ticker-track {
            display: flex;
            gap: 0;
            white-space: nowrap;
            animation: ticker 28s linear infinite;
        }
        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'Syne', sans-serif;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            padding: 0 3rem;
        }
        .ticker-item i { color: rgba(255,255,255,0.22); font-size: 0.55rem; }
        @keyframes ticker {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* ══════════════════════════════════════
           SECTION COMMONS
        ══════════════════════════════════════ */
        .sec { padding: 7rem 8%; }
        .sec-tag {
            font-family: 'Syne', sans-serif;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--ink-40);
            display: block;
            margin-bottom: 1.1rem;
        }
        .sec-h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.9rem, 3.8vw, 3.2rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1.06;
            letter-spacing: -0.03em;
        }
        .sec-h2 em { font-style: italic; font-weight: 400; }
        .sec-sub {
            font-size: 0.97rem;
            color: var(--ink-40);
            line-height: 1.8;
            margin-top: 0.8rem;
            max-width: 480px;
        }

        /* ══════════════════════════════════════
           HOW IT WORKS — Numbered list layout
        ══════════════════════════════════════ */
        #how-it-works {
            background: var(--off);
            padding: 7rem 8%;
        }
        .works-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6rem;
            align-items: start;
            margin-top: 4rem;
        }
        .works-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .works-item {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 1.5rem;
            padding: 2rem 0;
            border-top: 1px solid var(--line);
            transition: all 0.3s;
            cursor: default;
        }
        .works-item:last-child { border-bottom: 1px solid var(--line); }
        .works-num {
            font-family: 'Syne', sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: var(--ink-40);
            padding-top: 0.2rem;
        }
        .works-content h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.5rem;
        }
        .works-content p { font-size: 0.88rem; color: var(--ink-40); line-height: 1.75; }
        .works-visual {
            position: sticky;
            top: 100px;
            border-radius: 6px;
            overflow: hidden;
            aspect-ratio: 4/5;
        }
        .works-visual img {
            width: 100%; height: 100%;
            object-fit: cover;
        }

        /* ══════════════════════════════════════
           FLEET — Editorial grid
        ══════════════════════════════════════ */
        #fleet { background: var(--white); padding: 7rem 8%; }
        .fleet-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .fleet-filters { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        .filter-btn {
            font-family: 'Syne', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.48rem 1.1rem;
            border: 1.5px solid var(--line);
            border-radius: 3px;
            background: transparent;
            color: var(--ink-40);
            cursor: pointer;
            transition: all 0.22s;
        }
        .filter-btn.active, .filter-btn:hover {
            background: var(--ink);
            color: var(--white);
            border-color: var(--ink);
        }

        /* Masonry-like grid */
        .fleet-grid {
            columns: 3;
            column-gap: 1rem;
        }
        .car-card {
            break-inside: avoid;
            margin-bottom: 1rem;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--white);
            transition: all 0.38s var(--ease);
        }
        .car-card:nth-child(3n+2) { margin-top: 2.5rem; }
        .car-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 60px rgba(17,24,39,0.1);
            border-color: transparent;
        }
        .car-img {
            position: relative;
            overflow: hidden;
        }
        .car-img img {
            width: 100%;
            aspect-ratio: 16/10;
            object-fit: cover;
            transition: transform 0.6s var(--ease);
        }
        .car-card:hover .car-img img { transform: scale(1.05); }
        .car-badge {
            position: absolute;
            top: 10px; left: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.28rem 0.65rem;
            border-radius: 2px;
        }
        .badge-available { background: rgba(17,24,39,0.85); color: rgba(255,255,255,0.9); }
        .badge-rented    { background: rgba(17,24,39,0.6); color: rgba(255,255,255,0.7); }
        .badge-service   { background: rgba(17,24,39,0.4); color: rgba(255,255,255,0.6); }
        .car-body { padding: 1.2rem 1.4rem 1.5rem; }
        .car-type {
            font-family: 'Syne', sans-serif;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--ink-40);
            margin-bottom: 0.35rem;
        }
        .car-name {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.8rem;
            letter-spacing: -0.01em;
        }
        .car-specs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.1rem;
        }
        .spec {
            font-size: 0.78rem;
            color: var(--ink-40);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .spec i { font-size: 0.7rem; }
        .car-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
        .car-price strong {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--ink);
        }
        .car-price span {
            font-size: 0.76rem;
            color: var(--ink-40);
            margin-left: 0.15rem;
        }
        .btn-book {
            font-family: 'Syne', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            padding: 0.52rem 1.15rem;
            background: var(--ink);
            color: var(--white);
            border-radius: 3px;
            transition: all 0.22s;
        }
        .btn-book:hover { opacity: 0.8; }
        .fleet-cta {
            display: flex;
            justify-content: center;
            margin-top: 3.5rem;
        }
        .btn-outline-ink {
            font-family: 'Syne', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 0.9rem 2.2rem;
            border: 1.5px solid var(--ink);
            color: var(--ink);
            border-radius: 4px;
            transition: all 0.26s;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
        }
        .btn-outline-ink:hover { background: var(--ink); color: var(--white); }


        /* ══════════════════════════════════════
         WHY US — Dark section with app mockup
        ══════════════════════════════════════ */
        #why-us {
            background: var(--ink);
            padding: 7rem 8%;
        }
        #why-us .sec-tag { color: rgba(255,255,255,0.3); }
        #why-us .sec-h2 { color: var(--white); }
        #why-us .sec-sub { color: rgba(255,255,255,0.38); }

        .why-layout {
            display: grid;
            grid-template-columns: 1fr 1.4fr;
            gap: 5rem;
            align-items: center;
        }

        /* — Mockup kiri — */
        .why-mockup {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .why-mockup-frame {
            position: relative;
            width: 100%;
            max-width: 300px;
            aspect-ratio: 9/19;
            border-radius: 36px;
            overflow: hidden;
            border: 1px solid rgba(169, 169, 169, 0.99);        /* lebih terang dari 0.1 */
            box-shadow:
                0 0 0 8px rgba(88, 87, 87, 0.41),             /* ring luar lebih terang */
                0 40px 80px rgba(177, 176, 176, 0.5);
            background: rgba(24, 12, 12, 0.06);               /* tambah ini biar frame keliatan walau gambar belum penuh */
        }
        .why-mockup-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* — Kanan: teks + grid — */
        .why-right {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .why-right .sec-sub {
            margin-bottom: 2.5rem;
        }

        .why-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 6px;
            overflow: hidden;
        }
        .why-card {
            padding: 2rem 1.8rem;
            background: var(--ink);
            transition: background 0.3s;
        }
        .why-card:hover { background: rgba(255,255,255,0.04); }
        .why-icon {
            width: 38px; height: 38px;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
        }
        .why-icon i { color: rgba(255,255,255,0.5); font-size: 0.95rem; }
        .why-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: rgba(255,255,255,0.85);
            margin-bottom: 0.45rem;
        }
        .why-card p { font-size: 0.82rem; color: rgba(255,255,255,0.3); line-height: 1.8; }
                /* ══════════════════════════════════════
           STATS — Horizontal bold numbers
        ══════════════════════════════════════ */
        #stats {
            background: var(--off);
            padding: 6rem 8%;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }
        .stat-item {
            padding: 2.5rem 2rem;
            border-right: 1px solid var(--line);
        }
        .stat-item:first-child { padding-left: 0; }
        .stat-item:last-child { border-right: none; }
        .stat-num {
            
            font-size: clamp(2.4rem, 4.5vw, 4rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1;
            letter-spacing: -0.04em;
        }
        .stat-label {
            font-size: 0.82rem;
            color: var(--ink-40);
            margin-top: 0.6rem;
            letter-spacing: 0.01em;
        }

        /* ══════════════════════════════════════
           HISTORY — Horizontal scroll timeline
        ══════════════════════════════════════ */
        #history { background: var(--white); padding: 7rem 0 7rem 8%; }
        #history .head-wrap { padding-right: 8%; margin-bottom: 4rem; }
        .tl-scroll {
            display: flex;
            gap: 0;
            overflow-x: auto;
            padding-right: 8%;
            scroll-snap-type: x mandatory;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .tl-scroll::-webkit-scrollbar { display: none; }
        .tl-entry {
            flex: 0 0 320px;
            scroll-snap-align: start;
            padding: 0 3rem 0 0;
            position: relative;
        }
        .tl-entry::before {
            content: '';
            position: absolute;
            top: 1.5rem;
            left: 0; right: 3rem;
            height: 1px;
            background: var(--line);
        }
        .tl-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            background: var(--ink);
            margin-bottom: 2rem;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 0 0 4px var(--white), 0 0 0 5px var(--line);
        }
        .tl-year-num {
            font-family: 'Syne', sans-serif;
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.04em;
            line-height: 1;
            margin-bottom: 0.6rem;
        }
        .tl-content h3 {
            font-family: 'Syne', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.55rem;
        }
        .tl-content p { font-size: 0.83rem; color: var(--ink-40); line-height: 1.78; }

        /* ══════════════════════════════════════
           TESTIMONIALS — Overlapping cards
        ══════════════════════════════════════ */
        #testimonials {
            background: var(--off);
            padding: 7rem 8%;
        }
        .testi-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 5rem;
            margin-top: 4rem;
            align-items: start;
        }
        .testi-left { position: sticky; top: 100px; }
        .testi-count {
            font-family: 'Syne', sans-serif;
            font-size: 5rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.04em;
            line-height: 0.9;
            margin-bottom: 0.5rem;
        }
        .testi-count-label { font-size: 0.85rem; color: var(--ink-40); }
        .testi-avg {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--line);
        }
        .testi-avg-stars {
            font-size: 1.1rem;
            color: var(--ink);
            letter-spacing: 0.05em;
            margin-bottom: 0.3rem;
        }
        .testi-avg-val {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--ink);
        }
        .testi-avg-sub { font-size: 0.8rem; color: var(--ink-40); margin-top: 0.15rem; }

        /* Ganti jadi: */
        .testi-grid {
            height: 520px;
            overflow: hidden;
            position: relative;
            mask-image: linear-gradient(
                to bottom,
                transparent 0%,
                black 12%,
                black 88%,
                transparent 100%
            );
            -webkit-mask-image: linear-gradient(
                to bottom,
                transparent 0%,
                black 12%,
                black 88%,
                transparent 100%
            );
        }

        .testi-track {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            animation: scrollTestimonials 20s linear infinite;
        }

        .testi-track:hover,
        .testi-grid:hover .testi-track {
            animation-play-state: paused;
        }

        @keyframes scrollTestimonials {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-50%); }
        }
        .testi-card {
            background: var(--white);
            border-radius: 6px;
            padding: 2rem;
            border: 1px solid var(--line);
            transition: all 0.3s var(--ease);
        }
        .testi-card:hover {
            transform: translateX(6px);
            box-shadow: 0 12px 40px rgba(17,24,39,0.06);
        }
        .testi-stars { font-size: 0.75rem; color: var(--ink); letter-spacing: 0.08em; margin-bottom: 0.9rem; }
        .testi-text {
            font-size: 0.92rem;
            color: var(--ink-80);
            line-height: 1.8;
            margin-bottom: 1.4rem;
            font-style: italic;
        }
        .testi-author { display: flex; align-items: center; gap: 0.75rem; }
        .testi-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: var(--ink);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .testi-name {
            font-family: 'Syne', sans-serif;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--ink);
        }
        .testi-role { font-size: 0.74rem; color: var(--ink-40); margin-top: 0.1rem; }

        /* ══════════════════════════════════════
           CTA — Full bleed split
        ══════════════════════════════════════ */
        .cta-section {
            background: var(--ink);
            padding: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 480px;
        }
        .cta-left {
            padding: 6rem 5rem 6rem 8%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .cta-left h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 3.2vw, 2.8rem);
            font-weight: 800;
            color: var(--white);
            line-height: 1.08;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
        }
        .cta-left p {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.4);
            line-height: 1.75;
            max-width: 380px;
            margin-bottom: 2.4rem;
        }
        .cta-btns { display: flex; flex-direction: column; gap: 0.6rem; max-width: 260px; }
        .btn-cta-fill {
            font-family: 'Syne', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 1rem 1.8rem;
            background: var(--white);
            color: var(--ink);
            border-radius: 4px;
            text-align: center;
            transition: all 0.24s;
        }
        .btn-cta-fill:hover { opacity: 0.88; }
        .btn-cta-ghost {
            font-family: 'Syne', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 1rem 1.8rem;
            border: 1px solid rgba(255,255,255,0.16);
            color: rgba(255,255,255,0.6);
            border-radius: 4px;
            text-align: center;
            transition: all 0.24s;
        }
        .btn-cta-ghost:hover { border-color: rgba(255,255,255,0.4); color: var(--white); }
        .cta-right {
            position: relative;
            overflow: hidden;
        }
        .cta-right img {
            width: 100%; height: 100%;
            object-fit: cover;
            filter: grayscale(60%) brightness(0.55);
        }
        .cta-right-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, var(--ink), transparent);
        }

        /* ══════════════════════════════════════
           FOOTER
        ══════════════════════════════════════ */
        footer {
            background: var(--white);
            border-top: 1px solid var(--line);
            padding: 4rem 8% 2.5rem;
        }
        .footer-top {
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr);
            gap: 4rem;
            padding-bottom: 3rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--line);
        }
        .footer-logo {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.03em;
            display: block;
            margin-bottom: 1rem;
        }
        .footer-logo sup { font-size: 0.42em; letter-spacing: 0.12em; font-weight: 600; opacity: 0.45; }
        .footer-brand p { font-size: 0.83rem; color: var(--ink-40); line-height: 1.82; max-width: 260px; }
        .footer-socials { display: flex; gap: 0.5rem; margin-top: 1.4rem; }
        .social-btn {
            width: 32px; height: 32px;
            border: 1px solid var(--line);
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ink-40);
            font-size: 0.78rem;
            transition: all 0.22s;
        }
        .social-btn:hover { border-color: var(--ink); color: var(--ink); }
        .footer-col h4 {
            font-family: 'Syne', sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--ink);
            margin-bottom: 1.2rem;
        }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.65rem; }
        .footer-col ul li a { font-size: 0.83rem; color: var(--ink-40); transition: color 0.2s; }
        .footer-col ul li a:hover { color: var(--ink); }
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.77rem;
            color: var(--ink-40);
        }

        /* ══════════════════════════════════════
           MOBILE SLIDER (full-width)
        ══════════════════════════════════════ */
        .mobile-slider-section { display: none; }

        /* ══════════════════════════════════════
           ANIMATIONS
        ══════════════════════════════════════ */
        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }

        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.75s var(--ease-out), transform 0.75s var(--ease-out);
        }
        .reveal.visible { opacity: 1; transform: none; }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.32s; }

        /* ══════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════ */
        @media (max-width: 1024px) {
            .fleet-grid { columns: 2; }
            .why-layout {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
            .why-mockup {
                /*display: none;  sembunyikan mockup di mobile, grid tetap tampil */
            }
            .works-layout { grid-template-columns: 1fr; gap: 3rem; }
            .works-visual { display: none; }
            .testi-layout { grid-template-columns: 1fr; }
            .testi-left { position: static; }
            .cta-section { grid-template-columns: 1fr; }
            .cta-right { height: 260px; }
            .footer-top { grid-template-columns: 1fr 1fr; gap: 2.5rem; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .stat-item { border-bottom: 1px solid var(--line); }
        }

        @media (max-width: 768px) {
            nav { display: none; }
            .nav-right .nav-btn { display: none; }
            .nav-hamburger { display: flex; }
            .mobile-menu { display: block; }

            .hero-counter { display: none; }
            .hero-body { padding: 0 6% 10%; }
            .hero-controls { right: 6%; bottom: 5%; }

            /* Mobile Hero Slider section override */
            .hero { display: none; }
            .ticker { display: none; }
            .mobile-slider-section { display: block; }

            .fleet-grid { columns: 1; }
            .car-card:nth-child(3n+2) { margin-top: 0; }

            .sec { padding: 5rem 6%; }
            #how-it-works { padding: 5rem 6%; }
            #fleet { padding: 5rem 6%; }
            #why-us { padding: 5rem 6%; }
            #stats { padding: 4rem 6%; }
            #history { padding: 5rem 0 5rem 6%; }
            #testimonials { padding: 5rem 6%; }
            .cta-left { padding: 4rem 6%; }

            .works-layout { margin-top: 2.5rem; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .stat-item:first-child { padding-left: 2rem; }
            .footer-top { grid-template-columns: 1fr; gap: 2rem; }
            .footer-bottom { flex-direction: column; align-items: flex-start; }
            .why-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .stats-row { 
                grid-template-columns: 1fr 1fr;
                display: block;
             }
            .hero-title { font-size: 2.6rem; }
            
        }

        /* ══════════════════════════════════════
           MOBILE HERO SLIDER (full-width)
        ══════════════════════════════════════ */
        .mobile-slider-section {
            position: relative;
            background: var(--ink);
        }
        .mob-slider {
            position: relative;
            height: 100svh;
            min-height: 600px;
            overflow: hidden;
        }
        .mob-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s var(--ease);
        }
        .mob-slide.active { opacity: 1; }
        .mob-slide::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                170deg,
                rgba(17,24,39,0.2) 0%,
                rgba(17,24,39,0.75) 60%,
                rgba(17,24,39,0.95) 100%
            );
            z-index: 1;
        }
        .mob-slide img {
            width: 100%; height: 100%;
            object-fit: cover;
            transform: scale(1.06);
            transition: transform 7s var(--ease);
        }
        .mob-slide.active img { transform: scale(1); }
        .mob-slide-body {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            z-index: 10;
            padding: 0 7% 9%;
        }
        .mob-slide-tag {
            font-family: 'Syne', sans-serif;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            margin-bottom: 1rem;
        }
        .mob-slide-title {
            font-family: 'Syne', sans-serif;
            font-size: 2.6rem;
            font-weight: 800;
            color: var(--white);
            line-height: 0.95;
            letter-spacing: -0.03em;
            margin-bottom: 1.2rem;
        }
        .mob-slide-title em { font-style: italic; font-weight: 300; }
        .mob-slide-desc {
            font-size: 0.88rem;
            color: rgba(255,255,255,0.55);
            line-height: 1.75;
            margin-bottom: 2rem;
        }
        .mob-slide-btns {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .mob-btn-primary {
            font-family: 'Syne', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 1.05rem;
            background: var(--white);
            color: var(--ink);
            border-radius: 4px;
            text-align: center;
            transition: opacity 0.22s;
        }
        .mob-btn-secondary {
            font-family: 'Syne', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 1.05rem;
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.72);
            border-radius: 4px;
            text-align: center;
        }
        /* Dots at top of mobile slider */
        .mob-dots {
            position: absolute;
            top: 88px;
            left: 7%;
            z-index: 10;
            display: flex;
            gap: 0.35rem;
        }
        .mob-dot {
            width: 16px; height: 2px;
            background: rgba(255,255,255,0.25);
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .mob-dot.active { width: 32px; background: var(--white); }
        /* Swipe indicator */
        .mob-swipe-hint {
            position: absolute;
            bottom: 7.5%;
            right: 7%;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-family: 'Syne', sans-serif;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
        }
        .mob-swipe-hint i { font-size: 0.9rem; }

        /* Mobile ticker */
        .mob-ticker {
            background: var(--ink);
            padding: 0.85rem 0;
            overflow: hidden;
        }
    </style>
</head>
<body>

    {{-- ════════ NAVBAR ════════ --}}
    <div class="nav-logo-blob"></div>
    <a href="/" class="nav-logo-link" aria-label="Bening Rental">
        <img src="{{ asset('image/logo.webp') }}" alt="Bening Rental Logo">
    </a>

    <header id="navbar" class="hero-over">
        <div class="nav-logo-spacer"></div>
        <nav>
            <ul>
                <li><a href="#how-it-works">Cara Kerja</a></li>
                <li><a href="#fleet">Armada</a></li>
                <li><a href="#why-us">Keunggulan</a></li>
                <li><a href="#history">About</a></li>
            </ul>
        </nav>
        <div class="nav-right">
            @guest
                <a href="{{ route('login') }}" class="nav-btn nav-btn-ghost">Masuk</a>
                <a href="{{ route('register') }}" class="nav-btn nav-btn-solid">Daftar Gratis</a>
            @else
                <a href="{{ route('dashboard') }}" class="nav-btn nav-btn-solid">Dashboard</a>
            @endguest
            <div class="nav-hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu">
        <ul>
            <li><a href="#how-it-works">Cara Kerja</a></li>
            <li><a href="#fleet">Armada</a></li>
            <li><a href="#why-us">Keunggulan</a></li>
            <li><a href="#history">About</a></li>
        </ul>
        <div class="mobile-menu-actions">
            @guest
                <a href="{{ route('login') }}" class="mob-btn-outline">Masuk</a>
                <a href="{{ route('register') }}" class="mob-btn-fill">Daftar Gratis</a>
            @else
                <a href="{{ route('dashboard') }}" class="mob-btn-fill">Dashboard</a>
            @endguest
        </div>
    </div>

    {{-- ════════ HERO SLIDER (Desktop) ════════ --}}
    <section class="hero" id="heroDesktop">
        <div class="hero-slides" id="heroSlides">
            @foreach($heroSlides as $i => $url)
            <div class="hero-slide {{ $i === 0 ? 'active' : '' }}">
                <img src="{{ $url }}" alt="Slide {{ $i + 1 }}">
            </div>
            @endforeach
        </div>
        <div class="hero-body">
            <div class="hero-slide-label">Rental Mobil Premium — Indonesia</div>
            <p class="hero-desc">Armada premium, pengemudi terverifikasi, dan layanan tanpa batas — untuk bisnis, leisure, dan semua kebutuhan Anda.</p>
            <div class="hero-actions">
                @guest
                    <a href="{{ route('register') }}" class="btn-hero-fill">Mulai Pesan <i class="fas fa-arrow-right"></i></a>
                    <a href="#fleet" class="btn-hero-border">Lihat Armada</a>
                @else
                    <a href="{{ route('vehicles.index') }}" class="btn-hero-fill">Pesan Sekarang <i class="fas fa-arrow-right"></i></a>
                    <a href="{{ route('dashboard') }}" class="btn-hero-border">Dashboard</a>
                @endguest
            </div>
        </div>
        <div class="hero-controls">
            <div class="hero-dots" id="heroDots">
                @foreach($heroSlides as $i => $url)
                <div class="hero-dot {{ $i === 0 ? 'active' : '' }}" data-idx="{{ $i }}"></div>
                @endforeach
            </div>
            <div class="hero-arrows">
                <button class="hero-arrow" id="heroPrev" aria-label="Previous"><i class="fas fa-arrow-left"></i></button>
                <button class="hero-arrow" id="heroNext" aria-label="Next"><i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
        <div class="hero-counter">
            <span id="slideCounter">01 / {{ str_pad(count($heroSlides), 2, '0', STR_PAD_LEFT) }}</span>
            <div class="hero-counter-line"></div>
        </div>
    </section>

    {{-- ════════ TICKER ════════ --}}
    <div class="ticker">
        <div class="ticker-track" aria-hidden="true">
            @php $items = ['Pengemudi Terverifikasi','Layanan 24/7','Coverage Nasional','Pembayaran Aman','Support Responsif','Asuransi Perjalanan','Armada Prima','GPS Real-time']; @endphp
            @foreach(array_merge($items,$items) as $item)
                <span class="ticker-item"><i class="fas fa-circle"></i>{{ $item }}</span>
            @endforeach
        </div>
    </div>

    {{-- ════════ MOBILE SLIDER ════════ --}}
    <div class="mobile-slider-section" id="mobileHero">
        <div class="mob-slider" id="mobSlides">
            <div class="mob-dots" id="mobDots">
                @foreach($heroSlides as $i => $url)
                    <div class="mob-dot {{ $i === 0 ? 'active' : '' }}" data-idx="{{ $i }}"></div>
                @endforeach
            </div>
            @foreach($heroSlides as $i => $url)
                <div class="mob-slide {{ $i === 0 ? 'active' : '' }}">
                    <img src="{{ $url }}" alt="Slide {{ $i + 1 }}">
                </div>
            @endforeach
            <div class="mob-slide-body">
                <div class="mob-slide-tag">Rental Premium — Indonesia</div>
                <h1 class="mob-slide-title">Perjalanan<br><em>Tanpa</em><br>Kompromi.</h1>
                <p class="mob-slide-desc">Armada premium & pengemudi profesional untuk setiap perjalanan Anda.</p>
                <div class="mob-slide-btns">
                    @guest
                        <a href="{{ route('register') }}" class="mob-btn-primary">Mulai Pesan</a>
                        <a href="#fleet" class="mob-btn-secondary">Lihat Armada</a>
                    @else
                        <a href="{{ route('vehicles.index') }}" class="mob-btn-primary">Pesan Sekarang</a>
                        <a href="{{ route('dashboard') }}" class="mob-btn-secondary">Dashboard</a>
                    @endguest
                </div>
            </div>
            <div class="mob-swipe-hint"><i class="fas fa-arrow-right"></i> Geser</div>
        </div>
        <div class="mob-ticker">
            <div class="ticker-track" aria-hidden="true">
                @php $items = ['Pengemudi Terverifikasi','Layanan 24/7','Coverage Nasional','Pembayaran Aman','Support Responsif','Asuransi Perjalanan','Armada Prima','GPS Real-time']; @endphp
                @foreach(array_merge($items, $items) as $item)
                    <span class="ticker-item"><i class="fas fa-circle"></i>{{ $item }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ════════ HOW IT WORKS ════════ --}}
    <section id="how-it-works">
        <div class="works-layout">
            <div>
                <h2 class="sec-h2 reveal reveal-delay-1">Simple</h2>
                <p class="sec-sub reveal reveal-delay-2">Tidak perlu antri atau ribet. Daftar, pilih, bayar, dan pengemudi kami tiba tepat waktu.</p>
                <div class="works-list" style="margin-top:2.5rem;">
                    <div class="works-item reveal">
                        <div class="works-num">01</div>
                        <div class="works-content"><h3>Buat Akun</h3><p>Daftar gratis dalam hitungan detik. Lengkapi profil untuk pengalaman yang lebih personal.</p></div>
                    </div>
                    <div class="works-item reveal reveal-delay-1">
                        <div class="works-num">02</div>
                        <div class="works-content"><h3>Pilih Kendaraan</h3><p>Jelajahi armada lengkap kami. Saring berdasarkan tipe, kapasitas, atau harga.</p></div>
                    </div>
                    <div class="works-item reveal reveal-delay-2">
                        <div class="works-num">03</div>
                        <div class="works-content"><h3>Bayar & Konfirmasi</h3><p>Pembayaran aman via Midtrans. Admin konfirmasi, pengemudi siap menjemput.</p></div>
                    </div>
                    <div class="works-item reveal reveal-delay-3">
                        <div class="works-num">04</div>
                        <div class="works-content"><h3>Nikmati & Beri Ulasan</h3><p>Selesaikan perjalanan dan bagikan pengalaman Anda untuk membantu pengemudi kami.</p></div>
                    </div>
                </div>
            </div>
            <div class="works-visual reveal">
                <img src="{{ $landingImages['how_it_works_image'] }}" alt="Pengemudi Profesional">
            </div>
        </div>
    </section>

    {{-- ════════ FLEET ════════ --}}
    <section id="fleet">
        <div class="fleet-head">
            <div><h2 class="sec-h2 reveal reveal-delay-1">Best Service</h2></div>
            <div class="fleet-filters reveal">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="sedan">Sedan</button>
                <button class="filter-btn" data-filter="suv">SUV</button>
                <button class="filter-btn" data-filter="mpv">MPV</button>
                <button class="filter-btn" data-filter="van">Van</button>
            </div>
        </div>
        <div class="fleet-grid" id="fleet-grid">
            @forelse($vehicles as $vehicle)
            <div class="car-card reveal" data-type="{{ strtolower($vehicle->type ?? 'sedan') }}">
                <div class="car-img">
                    @if(!empty($vehicle->images) && count($vehicle->images) > 0)
                        <img src="{{ $vehicle->images[0] }}" alt="{{ $vehicle->name }}" loading="lazy">
                    @else
                        <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=600" alt="{{ $vehicle->name }}" loading="lazy">
                    @endif
                    <span class="car-badge badge-{{ $vehicle->status ?? 'available' }}">
                        {{ $vehicle->status === 'available' ? 'Tersedia' : ($vehicle->status === 'rented' ? 'Disewa' : 'Servis') }}
                    </span>
                </div>
                <div class="car-body">
                    <div class="car-type">{{ $vehicle->type ?? 'Sedan' }}</div>
                    <div class="car-name">{{ $vehicle->brand }} {{ $vehicle->model }} {{ $vehicle->year }}</div>
                    <div class="car-specs">
                        <div class="spec"><i class="fas fa-users"></i> {{ $vehicle->capacity ?? 4 }} Kursi</div>
                        <div class="spec"><i class="fas fa-star"></i> {{ number_format($vehicle->rating_avg ?? 4.8, 1) }}</div>
                        @if(!empty($vehicle->features))
                        <div class="spec"><i class="fas fa-snowflake"></i> AC</div>
                        @endif
                    </div>
                    <div class="car-footer">
                        <div class="car-price">
                            <strong>Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}</strong>
                            <span>/ hari</span>
                        </div>
                        @auth
                            @if(($vehicle->status ?? 'available') === 'available')
                                <a href="{{ route('vehicles.show', $vehicle->_id) }}" class="btn-book">Pesan</a>
                            @else
                                <span style="font-size:0.75rem;color:var(--ink-40);">Tidak Tersedia</span>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-book">Pesan</a>
                        @endauth
                    </div>
                </div>
            </div>
            @empty
            @php
            $showcases = [
                ['brand'=>'Toyota','model'=>'Alphard','type'=>'MPV','price'=>1800000,'cap'=>7,'rating'=>4.9,'status'=>'available','img'=>'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=600'],
                ['brand'=>'BMW','model'=>'520i','type'=>'Sedan','price'=>2500000,'cap'=>5,'rating'=>4.8,'status'=>'available','img'=>'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&q=80&w=600'],
                ['brand'=>'Toyota','model'=>'Fortuner','type'=>'SUV','price'=>1500000,'cap'=>7,'rating'=>4.7,'status'=>'available','img'=>'https://images.unsplash.com/photo-1606016159991-dfe4f2746ad5?auto=format&fit=crop&q=80&w=600'],
                ['brand'=>'Honda','model'=>'Odyssey','type'=>'MPV','price'=>1200000,'cap'=>8,'rating'=>4.8,'status'=>'rented','img'=>'https://images.unsplash.com/photo-1594502184342-2e12f877aa73?auto=format&fit=crop&q=80&w=600'],
                ['brand'=>'Mercedes','model'=>'E350','type'=>'Sedan','price'=>3200000,'cap'=>5,'rating'=>5.0,'status'=>'available','img'=>'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&q=80&w=600'],
                ['brand'=>'Mitsubishi','model'=>'Pajero Sport','type'=>'SUV','price'=>1400000,'cap'=>7,'rating'=>4.6,'status'=>'available','img'=>'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&q=80&w=600'],
            ];
            @endphp
            @foreach($showcases as $car)
            <div class="car-card reveal" data-type="{{ strtolower($car['type']) }}">
                <div class="car-img">
                    <img src="{{ $car['img'] }}" alt="{{ $car['brand'] }} {{ $car['model'] }}" loading="lazy">
                    <span class="car-badge badge-{{ $car['status'] }}">{{ $car['status'] === 'available' ? 'Tersedia' : 'Disewa' }}</span>
                </div>
                <div class="car-body">
                    <div class="car-type">{{ $car['type'] }}</div>
                    <div class="car-name">{{ $car['brand'] }} {{ $car['model'] }}</div>
                    <div class="car-specs">
                        <div class="spec"><i class="fas fa-users"></i> {{ $car['cap'] }} Kursi</div>
                        <div class="spec"><i class="fas fa-star"></i> {{ $car['rating'] }}</div>
                        <div class="spec"><i class="fas fa-snowflake"></i> AC</div>
                    </div>
                    <div class="car-footer">
                        <div class="car-price"><strong>Rp {{ number_format($car['price'], 0, ',', '.') }}</strong><span>/ hari</span></div>
                        <a href="{{ route('login') }}" class="btn-book">Pesan</a>
                    </div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
        <div class="fleet-cta reveal">
            @auth
                <a href="{{ route('vehicles.index') }}" class="btn-outline-ink">Lihat Semua Kendaraan <i class="fas fa-arrow-right"></i></a>
            @else
                <a href="{{ route('register') }}" class="btn-outline-ink">Daftar & Lihat Semua Armada <i class="fas fa-arrow-right"></i></a>
            @endauth
        </div>
    </section>

    {{-- ════════ WHY US ════════ --}}
    <section id="why-us">
        <div class="why-layout">
            <div class="why-mockup reveal">
                <div class="why-mockup-frame">
                    <img src="{{ $landingImages['why_us_mockup'] }}" alt="Mockup Aplikasi">
                </div>
            </div>
            <div class="why-right">
                <span class="sec-tag reveal">Aplikasi Kami</span>
                <h2 class="sec-h2 reveal reveal-delay-1" style="color:var(--white);">make it simple with our app</h2>
                <p class="sec-sub reveal reveal-delay-2" style="color:rgba(255,255,255,0.35);">Semua kebutuhan perjalanan Anda ada di satu genggaman</p>
                <div class="why-grid reveal reveal-delay-3">
                    <div class="why-card"><div class="why-icon"><i class="fas fa-mobile-screen"></i></div><h3>Pemesanan Instan</h3><p>Pesan kendaraan dalam hitungan detik. Tanpa antre, tanpa telepon, langsung konfirmasi.</p></div>
                    <div class="why-card"><div class="why-icon"><i class="fas fa-map-location-dot"></i></div><h3>Lacak Secara Real-time</h3><p>Pantau posisi kendaraan langsung dari aplikasi. Tahu kapan pengemudi tiba.</p></div>
                    <div class="why-card"><div class="why-icon"><i class="fas fa-calendar-check"></i></div><h3>Jadwal Fleksibel</h3><p>Atur tanggal dan jam penjemputan sesuai kebutuhan Anda, kapan saja.</p></div>
                    <div class="why-card"><div class="why-icon"><i class="fas fa-wallet"></i></div><h3>Bayar Lebih Mudah</h3><p>Berbagai metode pembayaran tersedia — transfer, e-wallet, hingga kartu kredit.</p></div>
                    <div class="why-card"><div class="why-icon"><i class="fas fa-bell"></i></div><h3>Notifikasi Otomatis</h3><p>Update status perjalanan secara real-time langsung ke ponsel Anda.</p></div>
                    <div class="why-card"><div class="why-icon"><i class="fas fa-headset"></i></div><h3>Support Dalam Aplikasi</h3><p>Hubungi tim kami langsung dari chat aplikasi — respons cepat 24/7.</p></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════ STATS ════════ --}}
    <section id="stats">
        <div class="stats-row">
            <div class="stat-item reveal">
                <div class="stat-num" data-target="{{ max($stats['total_vehicles'], 20) }}" data-suffix="+">0</div>
                <div class="stat-label">Kendaraan Aktif</div>
            </div>
            <div class="stat-item reveal reveal-delay-1">
                <div class="stat-num" data-target="{{ max($stats['total_bookings'], 500) }}" data-suffix="+">0</div>
                <div class="stat-label">Perjalanan Selesai</div>
            </div>
            <div class="stat-item reveal reveal-delay-2">
                <div class="stat-num" data-target="{{ max($stats['happy_customers'], 200) }}" data-suffix="+">0</div>
                <div class="stat-label">Pelanggan Puas</div>
            </div>
            <div class="stat-item reveal reveal-delay-3">
                {{-- Rata-rata rating real dari DB, fallback 4.9 --}}
                <div class="stat-num" data-target="{{ number_format($ratingAvg, 1, '.', '') }}" data-decimal="1">0</div>
                <div class="stat-label">Rating Rata-rata</div>
            </div>
        </div>
    </section>

    {{-- ════════ HISTORY ════════ --}}
    <section id="history">
        <div class="head-wrap">
            <h2 class="sec-h2 reveal reveal-delay-1">Our Record</h2>
        </div>
        <div class="tl-scroll">
            @php $timeline = [
                ['year'=>'2020','title'=>'Pondasi DriveEase','desc'=>'Berawal dari 3 kendaraan dan tekad kuat, DriveEase lahir dengan misi menghadirkan rental mobil yang jujur dan profesional.'],
                ['year'=>'2021','title'=>'Ekspansi Armada','desc'=>'Armada berkembang menjadi 15 kendaraan. Mulai menerima pesanan korporat dari berbagai perusahaan di Jabodetabek.'],
                ['year'=>'2022','title'=>'Platform Digital','desc'=>'Meluncurkan sistem pemesanan online terintegrasi dengan Midtrans untuk pembayaran yang aman dan terpercaya.'],
                ['year'=>'2023','title'=>'Pelacakan Real-time','desc'=>'Menghadirkan fitur GPS tracking untuk setiap perjalanan. Transparansi penuh untuk ketenangan pikiran pelanggan.'],
                ['year'=>'2024','title'=>'Penghargaan Nasional','desc'=>'Meraih penghargaan "Best Car Rental Service". Kepercayaan Anda adalah motivasi terbesar kami.'],
                ['year'=>'2025+','title'=>'Menuju Masa Depan','desc'=>'Ekspansi ke 10 kota besar Indonesia dengan armada kendaraan listrik premium untuk generasi mendatang.'],
            ]; @endphp
            @foreach($timeline as $tl)
            <div class="tl-entry reveal">
                <div class="tl-dot"></div>
                <div class="tl-year-num">{{ $tl['year'] }}</div>
                <div class="tl-content"><h3>{{ $tl['title'] }}</h3><p>{{ $tl['desc'] }}</p></div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ════════ TESTIMONIALS ════════ --}}
    <section id="testimonials">
        <div class="testi-layout">
            <div class="testi-left">
                <h2 class="sec-h2 reveal reveal-delay-1">Kata<br>mereka.</h2>
                <div class="testi-avg reveal reveal-delay-2">
                    <div class="testi-avg-stars">
                        @php
                            $fullStars = floor($ratingAvg);
                            $halfStar  = ($ratingAvg - $fullStars) >= 0.5;
                        @endphp
                        @for($s = 1; $s <= 5; $s++)
                            @if($s <= $fullStars)★@elseif($halfStar && $s === $fullStars + 1)★@else☆@endif
                        @endfor
                    </div>
                    <div class="testi-avg-val">{{ number_format($ratingAvg, 1) }}</div>
                    <div class="testi-avg-sub">
                        dari {{ $ratingCount > 0 ? number_format($ratingCount) : '500' }}+
                        ulasan terverifikasi
                    </div>
                </div>
                <div class="testi-count reveal reveal-delay-3" style="margin-top:2.5rem;">
                    <div class="testi-count" style="font-family:'Syne';font-size:4rem;font-weight:800;color:var(--ink);line-height:1;">98%</div>
                    <div class="testi-count-label">pelanggan puas dan kembali lagi</div>
                </div>
            </div>

            <div class="testi-grid">
                <div class="testi-track" id="testiTrack">
                    @if($testimonials->isNotEmpty())
                        @foreach($testimonials as $t)
                        <div class="testi-card">
                            <div class="testi-stars">
                                {{ str_repeat('★', $t['stars']) }}{{ str_repeat('☆', 5 - $t['stars']) }}
                            </div>
                            <p class="testi-text">"{{ $t['text'] }}"</p>
                            <div class="testi-author">
                                <div class="testi-avatar">{{ $t['init'] }}</div>
                                <div>
                                    <div class="testi-name">{{ $t['name'] }}</div>
                                    <div class="testi-role">{{ $t['role'] }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        {{-- Fallback dummy jika belum ada rating --}}
                        @php $dummies = [
                            ['init'=>'BW','name'=>'Budi Wijaya','role'=>'Direktur, PT Maju Bersama','stars'=>5,'text'=>'Pengemudi sangat ramah dan tepat waktu. Kendaraan bersih dan nyaman. Sudah 5 kali pakai untuk perjalanan bisnis.'],
                            ['init'=>'SR','name'=>'Sari Rahayu','role'=>'Travel Blogger','stars'=>5,'text'=>'Booking sangat mudah. Pembayaran aman. Pengemudi membantu angkat koper tanpa diminta — sangat profesional!'],
                            ['init'=>'AP','name'=>'Ahmad Pratama','role'=>'Event Organizer','stars'=>4,'text'=>'Harga kompetitif untuk kualitas yang diberikan. Sewa Alphard untuk acara pernikahan — semua tamu sangat terkesan.'],
                            ['init'=>'DK','name'=>'Diana Kusuma','role'=>'Ibu Rumah Tangga','stars'=>5,'text'=>'Fitur tracking real-time bikin tenang. Bisa pantau perjalanan suami dari rumah. Fiturnya sangat membantu.'],
                            ['init'=>'RS','name'=>'Rizal Santoso','role'=>'HR Manager','stars'=>5,'text'=>'Sebagai klien korporat, kami butuh layanan yang konsisten. Selalu hadir tepat waktu untuk 20+ perjalanan tim kami.'],
                        ]; @endphp
                        @foreach($dummies as $t)
                        <div class="testi-card">
                            <div class="testi-stars">{{ str_repeat('★',$t['stars']) }}{{ str_repeat('☆', 5-$t['stars']) }}</div>
                            <p class="testi-text">"{{ $t['text'] }}"</p>
                            <div class="testi-author">
                                <div class="testi-avatar">{{ $t['init'] }}</div>
                                <div>
                                    <div class="testi-name">{{ $t['name'] }}</div>
                                    <div class="testi-role">{{ $t['role'] }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ════════ CTA ════════ --}}
    <div class="cta-section">
        <div class="cta-left">
            <h2 class="reveal">Siap Memulai<br>Perjalanan Anda?</h2>
            <p class="reveal reveal-delay-1">Bergabunglah dengan {{ number_format(max($stats['happy_customers'], 200)) }}+ pelanggan yang telah mempercayakan perjalanan mereka kepada kami.</p>
            <div class="cta-btns reveal reveal-delay-2">
                @guest
                    <a href="{{ route('register') }}" class="btn-cta-fill">Daftar Sekarang — Gratis</a>
                    <a href="{{ route('login') }}" class="btn-cta-ghost">Sudah Punya Akun?</a>
                @else
                    <a href="{{ route('vehicles.index') }}" class="btn-cta-fill">Pesan Kendaraan</a>
                    <a href="{{ route('dashboard') }}" class="btn-cta-ghost">Ke Dashboard</a>
                @endguest
            </div>
        </div>
        <div class="cta-right reveal">
            <div class="cta-right-overlay"></div>
            <img src="{{ $landingImages['cta_image'] }}" alt="">
        </div>
    </div>

    {{-- ════════ FOOTER ════════ --}}
    <footer>
        <div class="footer-top">
            <div class="footer-brand">
                <a href="/" class="footer-logo">DriveEase<sup>™</sup></a>
                <p>Rental mobil premium dengan pengemudi profesional untuk perjalanan bisnis dan leisure di seluruh Indonesia.</p>
                <div class="footer-socials">
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-col"><h4>Layanan</h4><ul><li><a href="#">Rental Harian</a></li><li><a href="#">Rental Mingguan</a></li><li><a href="#">Paket Korporat</a></li><li><a href="#">Airport Transfer</a></li><li><a href="#">Wisata & Leisure</a></li></ul></div>
            <div class="footer-col"><h4>Perusahaan</h4><ul><li><a href="#history">Tentang Kami</a></li><li><a href="#">Karir</a></li><li><a href="#">Blog</a></li><li><a href="#">Press Kit</a></li></ul></div>
            <div class="footer-col"><h4>Bantuan</h4><ul><li><a href="#">FAQ</a></li><li><a href="#">Syarat & Ketentuan</a></li><li><a href="#">Kebijakan Privasi</a></li><li><a href="#">Hubungi Kami</a></li><li><a href="{{ route('login') }}">Login</a></li></ul></div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} INEZ RENT CAR. Semua hak dilindungi.</span>
        </div>
    </footer>

    {{-- ════════ SCRIPTS ════════ --}}
    <script>
    const navbar = document.getElementById('navbar');
    const blob   = document.querySelector('.nav-logo-blob');

    function updateNav() {
        const scrolled = window.scrollY > 60;
        navbar.classList.toggle('scrolled', scrolled);
        navbar.classList.toggle('hero-over', !scrolled);
        blob.classList.toggle('scrolled', scrolled);
    }
    window.addEventListener('scroll', updateNav, { passive: true });
    updateNav();

    const ham = document.getElementById('hamburger');
    const mobMenu = document.getElementById('mobileMenu');
    ham.addEventListener('click', () => { ham.classList.toggle('open'); mobMenu.classList.toggle('open'); });
    mobMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { ham.classList.remove('open'); mobMenu.classList.remove('open'); }));

    const heroSlideEls = document.querySelectorAll('#heroDesktop .hero-slide');
    const heroDots     = document.querySelectorAll('#heroDots .hero-dot');
    const heroCounter  = document.getElementById('slideCounter');
    let heroIdx = 0, heroTimer;
    function gotoHeroSlide(n) {
        heroSlideEls[heroIdx].classList.remove('active'); heroDots[heroIdx].classList.remove('active');
        heroIdx = (n + heroSlideEls.length) % heroSlideEls.length;
        heroSlideEls[heroIdx].classList.add('active'); heroDots[heroIdx].classList.add('active');
        heroCounter.textContent = String(heroIdx+1).padStart(2,'0') + ' / ' + String(heroSlideEls.length).padStart(2,'0');
    }
    function startHeroAuto() { clearInterval(heroTimer); heroTimer = setInterval(() => gotoHeroSlide(heroIdx+1), 5500); }
    document.getElementById('heroNext').addEventListener('click', () => { gotoHeroSlide(heroIdx+1); startHeroAuto(); });
    document.getElementById('heroPrev').addEventListener('click', () => { gotoHeroSlide(heroIdx-1); startHeroAuto(); });
    heroDots.forEach(d => d.addEventListener('click', () => { gotoHeroSlide(+d.dataset.idx); startHeroAuto(); }));
    startHeroAuto();

    const mobSlideEls = document.querySelectorAll('#mobSlides .mob-slide');
    const mobDotEls   = document.querySelectorAll('#mobDots .mob-dot');
    let mobIdx = 0, mobTimer;
    function gotoMobSlide(n) {
        mobSlideEls[mobIdx].classList.remove('active'); mobDotEls[mobIdx].classList.remove('active');
        mobIdx = (n + mobSlideEls.length) % mobSlideEls.length;
        mobSlideEls[mobIdx].classList.add('active'); mobDotEls[mobIdx].classList.add('active');
    }
    function startMobAuto() { clearInterval(mobTimer); mobTimer = setInterval(() => gotoMobSlide(mobIdx+1), 5000); }
    mobDotEls.forEach(d => d.addEventListener('click', () => { gotoMobSlide(+d.dataset.idx); startMobAuto(); }));
    let touchStartX = 0;
    const mobSlider = document.getElementById('mobSlides');
    mobSlider.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    mobSlider.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) { gotoMobSlide(mobIdx + (diff > 0 ? 1 : -1)); startMobAuto(); }
    }, { passive: true });
    startMobAuto();

    const revealEls = document.querySelectorAll('.reveal');
    const revObs = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(el => revObs.observe(el));

    const counters = document.querySelectorAll('[data-target]');
    const cntObs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target  = parseFloat(el.dataset.target);
            const suffix  = el.dataset.suffix || '';
            const decimal = parseInt(el.dataset.decimal || '0');
            const step = 16, duration = 1800;
            const inc = target / (duration / step);
            let cur = 0;
            const tick = () => {
                cur += inc;
                if (cur >= target) { el.textContent = (decimal > 0 ? target.toFixed(decimal) : Math.floor(target).toLocaleString('id-ID')) + suffix; return; }
                el.textContent = (decimal > 0 ? cur.toFixed(decimal) : Math.floor(cur).toLocaleString('id-ID')) + suffix;
                setTimeout(tick, step);
            };
            tick();
            cntObs.unobserve(el);
        });
    }, { threshold: 0.5 });
    counters.forEach(c => cntObs.observe(c));

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const f = btn.dataset.filter;
            document.querySelectorAll('.car-card').forEach(card => { card.style.display = (f === 'all' || card.dataset.type === f) ? '' : 'none'; });
        });
    });

    const testiTrack = document.getElementById('testiTrack');
    if (testiTrack) { testiTrack.innerHTML += testiTrack.innerHTML; }
    </script>
</body>
</html>