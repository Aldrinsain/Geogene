<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logo.php';
$fullName = $_SESSION['search_full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>GeoGene - Verify Your Identity</title>
<style>
  *{box-sizing:border-box;}
  html,body{overflow:hidden;height:100%;max-width:100%;}
  body{
    margin:0;height:100vh;height:100dvh;
    display:flex;align-items:center;justify-content:center;
    background:#070c1c;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  }
  .phone{
    width:393px;height:800px;max-height:100vh;max-height:100dvh;
    background:#0B132B;border-radius:28px;
    box-shadow:0 20px 60px rgba(0,0,0,0.5);
    position:relative;overflow:hidden;
    max-width:100%;
    display:flex;flex-direction:column;
  }
  @media (max-width:480px){
    body{height:100vh;height:100dvh;}
    .phone{
      width:100%;height:100vh;height:100dvh;
      border-radius:0;box-shadow:none;
    }
  }
  @media (max-height:700px){
    .footer-tree{display:none;}
    .content{padding-top:12px;}
    .header-row{margin-bottom:10px;}
    .glass-card{padding:14px 16px;}
    .shield-icon-wrap{margin-bottom:6px;}
    p.page-sub{margin-bottom:10px;}
    .login-line{margin-top:8px;}
  }

  /* ============ LIQUID GLASS ANIMATED BACKGROUND ============ */
  .liquid-bg{position:absolute;inset:0;overflow:hidden;z-index:0;}
  .liquid-blob{
    position:absolute;border-radius:50%;
    background:radial-gradient(circle at 35% 35%, rgba(43, 214, 28, 0.93), rgba(30, 114, 31, 0.69) 70%);
    filter:blur(6px);mix-blend-mode:screen;
    animation:liquidMove 10s ease-in-out infinite;
  }
  .liquid-blob.l1{width:280px;height:280px;top:-90px;left:-100px;animation-delay:0s;}
  .liquid-blob.l2{width:220px;height:220px;top:280px;right:-90px;animation-delay:2.5s;}
  .liquid-blob.l3{width:200px;height:200px;bottom:-70px;left:20px;animation-delay:5s;}
  @keyframes liquidMove{
    0%,100%{transform:translate(0,0) scale(1);}
    33%{transform:translate(24px,-18px) scale(1.12);}
    66%{transform:translate(-18px,14px) scale(0.94);}
  }

  /* ============ LAYOUT ============ */
  .content{
    position:relative;z-index:2;
    padding:16px 20px 0;
    padding-top:max(22px, env(safe-area-inset-top));
  }

  .header-row{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
  .header-text{flex:1;min-width:0;}
  .header-text .brand{font-size:17px;font-weight:800;color:#39FF88;line-height:1.15;}
  .header-text .tagline{font-size:9px;font-weight:600;color:#8a93b8;letter-spacing:.01em;line-height:1.3;}
  .back-arrow {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);

    color: #f2f4fa;
    text-decoration: none;

    backdrop-filter: blur(10px);

    transition:
        background .2s ease,
        transform .2s ease,
        box-shadow .2s ease;
}

/* Hover effect */
.back-arrow:hover {
    background: rgba(57,255,136,0.12);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(57,255,136,0.15);
}

/* Click effect */
.back-arrow:active {
    transform: scale(.92);
  }
  .back-arrow:hover{background:rgba(255,255,255,0.1);}
  .header-logo{
    width:42px;height:42px;border-radius:14px;flex-shrink:0;
    background:rgba(57,255,136,0.1);border:1px solid rgba(57,255,136,0.25);
    display:flex;align-items:center;justify-content:center;
    backdrop-filter:blur(10px);
  }

  /* frosted glass main card */
  .glass-card{
    position:relative;
    background:rgba(255,255,255,0.045);
    border:1px solid rgba(255,255,255,0.09);
    backdrop-filter:blur(22px) saturate(140%);
    -webkit-backdrop-filter:blur(22px) saturate(140%);
    border-radius:26px;
    padding:18px 18px;
    box-shadow:0 20px 50px rgba(0,0,0,0.35);
  }
  .shield-icon-wrap {
    width: 70px;
    height: 70px;
    margin: 4px auto 16px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: transparent;
    box-shadow: none;
    border-radius: 0;

    animation: shieldPop .6s cubic-bezier(.34,1.56,.64,1) both;
}

.shield-icon-wrap svg {
    width: 58px;
    height: 58px;
    display: block;
}

@keyframes shieldPop {
    0% {
        opacity: 0;
        transform: scale(.65);
    }

    70% {
        transform: scale(1.08);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}

  
  h1.page-title{font-size:18px;font-weight:800;color:#f2f4fa;margin:0 0 6px;line-height:1.25;display: flex;align-items: center;justify-content: center;}
  h1.page-title .accent{color:#39FF88;}
  p.page-sub{font-size:11.5px;color:#a8b0d0;line-height:1.45;margin:0 0 14px;}
 
  .search-field-wrap{
    display:flex;align-items:center;gap:10px;
    background:#0B132B;
    margin-top:50px;
    border:1px solid rgba(255,255,255,0.04);
    border-radius:16px;padding:6px 8px 6px 16px;margin-bottom:10px;
    box-shadow:
      inset 5px 5px 10px rgba(0,0,0,0.45),
      inset -5px -5px 10px rgba(255,255,255,0.035);
    transition:box-shadow .2s ease;
  }
  .search-field-wrap:focus-within{
    box-shadow:
      inset 4px 4px 8px rgba(0,0,0,0.5),
      inset -4px -4px 8px rgba(255,255,255,0.04),
      0 0 0 1px rgba(57,255,136,0.35);
  }
  .search-field-wrap svg{flex-shrink:0;}
  .search-field-wrap input{
    flex:1;border:none;background:none;outline:none;
    font-size:14px;color:#f2f4fa;padding:12px 0;font-family:inherit;
  }
  .search-field-wrap input::placeholder{color:#6d759a;}
  .search-icon-btn{
    width:38px;height:38px;border-radius:11px;border:none;cursor:pointer;flex-shrink:0;
    background:rgba(57,255,136,0.15);
    display:flex;align-items:center;justify-content:center;
  }
 
  .search-btn{
    width:100%;
    height:48px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:9px;
 
    background:linear-gradient(160deg,#5cffa3,#39FF88);
    color:#0B132B;
    border:none;
    border-radius:14px;
 
    font-size:14px;
    font-weight:800;
    cursor:pointer;
 
    box-shadow:
      0 1px 0 rgba(255,255,255,.5) inset,
      0 8px 20px rgba(57,255,136,.25);
 
    transition:
      transform .2s ease,
      box-shadow .2s ease;
    margin-top:50px;
  }
 
  .search-btn:hover{
    transform:translateY(-2px);
    box-shadow:
      0 1px 0 rgba(255,255,255,.5) inset,
      0 12px 25px rgba(57,255,136,.35);
  }
 
  .search-btn:active{
    transform:scale(.97);

  }

  
  .info-banner{
    display:flex;align-items:flex-start;gap:12px; 
    background:rgba(57,255,136,0.06);border:1px solid rgba(57,255,136,0.16);
    border-radius:14px;padding:11px 13px;margin-top:85px;
  }
  .info-banner .i-circle{
    width:20px;height:20px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    margin-top:2px;
  }
  .info-banner p{margin:0;font-size:12px;color:#a8b0d0;line-height:1.55;}

  .login-line{text-align:center;font-size:13px;color:#a8b0d0;margin:10px 0 0;}
  .login-line b{color:#39FF88;cursor:pointer;font-weight:700;}

  .footer-tree{
    position:relative;z-index:1;margin-top:8px;height:34px;overflow:hidden;
    display:flex;align-items:flex-end;justify-content:center;opacity:.35;
  }

  /* ---------- other screens ---------- */
  .screen{display:none;position:relative;z-index:2;padding:26px 22px;}
  #screen-search{padding:0;}
  #screen-search.active{display:flex;flex-direction:column;height:100%;}
  .screen.active{display:block;animation:fadeSlideUp .4s ease both;}
  @keyframes fadeSlideUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
  .s-title{font-size:22px;font-weight:800;color:#f2f4fa;text-align:center;margin:18px 0 8px;}
  .s-sub{font-size:13px;color:#a8b0d0;text-align:center;line-height:1.6;margin:0 0 20px;}

  .loader-icon-wrap{
    width:160px;height:160px;margin:14px auto 26px;border-radius:26px;
    background:transparent;border:none;
    display:flex;align-items:center;justify-content:center;
    overflow:visible;
  }
  .progress-track{width:100%;height:6px;background:rgba(255,255,255,0.08);border-radius:10px;overflow:hidden;margin:0 0 8px;}
  .progress-fill{height:100%;width:0%;background:linear-gradient(90deg,#5cffa3,#39FF88);border-radius:10px;animation:fillProgress 2.6s ease forwards;}
  @keyframes fillProgress{to{width:100%;}}
  .cancel-link{display:block;text-align:center;font-size:12.5px;color:#6d759a;text-decoration:none;margin-top:22px;}

  .icon-circle{
    width:80px;
    height:80px;
    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    margin:16px auto 22px;

    /* NEOMORPHISM BASE */
    background:#0B132B;

    box-shadow:
        8px 8px 18px rgba(0,0,0,0.55),
        -8px -8px 18px rgba(255,255,255,0.035),
        inset 1px 1px 2px rgba(255,255,255,0.04);

    position:relative;

    /* GRAPHIC MOTION */
    opacity:0;
    transform:scale(.55) translateY(15px);

    animation:
        iconPopIn .75s cubic-bezier(.34,1.56,.64,1) forwards,
        iconFloat 3.5s ease-in-out 1s infinite;
}


/* =========================
   GREEN SUCCESS
   ========================= */

.icon-circle.green{
    box-shadow:
        9px 9px 20px rgba(0,0,0,0.55),
        -8px -8px 18px rgba(255,255,255,0.035),
        inset 0 0 0 1px rgba(57,255,136,0.12),
        0 0 20px rgba(57,255,136,0.08);
}


/* =========================
   RED ERROR
   ========================= */

.icon-circle.red{
    box-shadow:
        9px 9px 20px rgba(0,0,0,0.55),
        -8px -8px 18px rgba(255,255,255,0.035),
        inset 0 0 0 1px rgba(255,92,108,0.12),
        0 0 20px rgba(255,92,108,0.07);
}


/* =========================
   ICON
   ========================= */

.icon-circle svg{
    width:36px;
    height:36px;

    position:relative;
    z-index:2;

    filter:
        drop-shadow(0 2px 4px rgba(0,0,0,0.35));

    animation:
        iconDraw .6s ease .25s both;
}


/* =========================
   POP-IN
   ========================= */

@keyframes iconPopIn{

    0%{
        opacity:0;
        transform:scale(.45) translateY(18px);
    }

    65%{
        opacity:1;
        transform:scale(1.08) translateY(-3px);
    }

    85%{
        transform:scale(.97) translateY(1px);
    }

    100%{
        opacity:1;
        transform:scale(1) translateY(0);
    }
}


/* =========================
   FLOATING MOTION
   ========================= */

@keyframes iconFloat{

    0%,100%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(-5px);
    }
}


/* =========================
   ICON DRAW EFFECT
   ========================= */

@keyframes iconDraw{

    0%{
        opacity:0;
        transform:scale(.65) rotate(-8deg);
    }

    60%{
        opacity:1;
        transform:scale(1.08) rotate(2deg);
    }

    100%{
        opacity:1;
        transform:scale(1) rotate(0deg);
    }
}
.btn-primary{
    width:75%;
    height:45px;

    /* CENTER */
    margin:80px auto 0;
    margin-top: 60px;

    background:linear-gradient(160deg,#5cffa3,#39FF88);
    color:#0B132B;
    border:none;
    border-radius:16px;

    padding:0;
    font-size:14.5px;
    font-weight:800;
    cursor:pointer;

    display:flex;
    align-items:center;
    justify-content:center;

    text-decoration:none;
    text-align:center;

    box-shadow:
        0 10px 22px rgba(57,255,136,0.30);

    opacity:0;
    transform:translateY(18px) scale(.96);

    animation:
        proceedButtonIn
        .7s
        cubic-bezier(.22,1,.36,1)
        .5s
        forwards;

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        filter .25s ease;
}

@keyframes proceedButtonIn{
    0%{
        opacity:0;
        transform:translateY(18px) scale(.96);
    }

    60%{
        opacity:1;
        transform:translateY(-2px) scale(1.01);
    }

    100%{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.btn-primary:hover{
    transform:translateY(-2px);
    box-shadow:
        0 14px 28px rgba(57,255,136,0.38);
    filter:brightness(1.04);
}

.btn-primary:active{
    transform:scale(.95);
    box-shadow:
        0 5px 12px rgba(57,255,136,0.20);
}
  .btn-cancel-search{
    width:70%;
    height:40px;

    background:rgba(255,255,255,0.03);
    color:#39FF88;
    border:1.5px solid rgba(57,255,136,0.4);
    border-radius:16px;

    padding:0;
    font-size:14px;
    font-weight:700;
    cursor:pointer;

    margin:200px auto 0;

    display:flex;
    align-items:center;
    justify-content:center;

    opacity:0;
    transform:translateY(18px) scale(.96);

    animation:
        cancelSearchIn
        .7s
        cubic-bezier(.22,1,.36,1)
        .35s
        forwards;

    transition:
        transform .25s ease,
        background .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;
}

@keyframes cancelSearchIn{
    0%{
        opacity:0;
        transform:translateY(18px) scale(.96);
    }

    60%{
        opacity:1;
        transform:translateY(-2px) scale(1.01);
    }

    100%{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.btn-cancel-search:hover{
    background:rgba(57,255,136,0.08);
    border-color:rgba(57,255,136,0.75);
    box-shadow:0 8px 20px rgba(57,255,136,0.12);
    transform:translateY(-2px);
}

.btn-cancel-search:active{
    transform:scale(.95);
}
.btn-cancel-registration{
    width:75%;
    height:38px;

    background:transparent;
    color:#a8b0d0;

    border:1px solid rgba(255,255,255,0.12);
    border-radius:14px;

    padding:0;
    font-size:13px;
    font-weight:600;
    cursor:pointer;

    margin:16px auto 0;
    margin-top: 165px;

    display:flex;
    align-items:center;
    justify-content:center;

    opacity:0;
    transform:translateY(12px);

    animation:
        cancelRegistrationIn
        .6s
        cubic-bezier(.22,1,.36,1)
        .45s
        forwards;

    transition:
        transform .2s ease,
        color .2s ease,
        background .2s ease,
        border-color .2s ease;
}

@keyframes cancelRegistrationIn{
    from{
        opacity:0;
        transform:translateY(12px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

.btn-cancel-registration:hover{
    color:#39FF88;
    background:rgba(57,255,136,0.05);
    border-color:rgba(57,255,136,0.4);
    transform:translateY(-2px);
}

.btn-cancel-registration:active{
    transform:scale(.95);
}
</style>
</head>
<body>

<div class="phone">
  <!-- Liquid Glass moving background, sits behind everything -->
  <div class="liquid-bg">
    <div class="liquid-blob l1"></div>
    <div class="liquid-blob l2"></div>
    <div class="liquid-blob l3"></div>
  </div>

  <!-- SCREEN 1: SEARCH -->
  <section class="screen active" id="screen-search">
    <div class="content">
      <div class="header-row">
        <a href="../auth/intro.php" class="back-arrow">
    <svg
        width="17"
        height="17"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.2"
        stroke-linecap="round"
        stroke-linejoin="round">

        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
        </a>
      <div class="header-text">
          <div class="brand">GeoGene</div>
          <div class="tagline">Connecting Families, Preserving Heritage.</div>
        </div>
      </div>

      <div class="glass-card">
        <div class="shield-icon-wrap">
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="58"
        height="58"
        viewBox="0 0 24 24"
        fill="none">

        <g clip-path="url(#clip0_4418_4642)">
            <path
                d="M18.3301 5.67L6.59009 17.41C6.15009 17.85 5.41008 17.79 5.05008 17.27C3.81008 15.46 3.08008 13.32 3.08008 11.12V6.73C3.08008 5.91 3.70008 4.98 4.46008 4.67L10.0301 2.39C11.2901 1.87 12.6901 1.87 13.9501 2.39L17.9901 4.03999C18.6601 4.30999 18.8301 5.17 18.3301 5.67Z"
                fill="#39A96B"/>

            <path
                d="M19.27 7.03963C19.92 6.48963 20.91 6.95962 20.91 7.80962V11.1196C20.91 16.0096 17.36 20.5896 12.51 21.9296C12.18 22.0196 11.82 22.0196 11.48 21.9296C10.06 21.5296 8.74 20.8596 7.61 19.9796C7.13 19.6096 7.08001 18.9096 7.50001 18.4796C9.68001 16.2496 16.06 9.74963 19.27 7.03963Z"
                fill="#0fdd16"/>
        </g>

        <defs>
            <clipPath id="clip0_4418_4642">
                <rect width="24" height="24" fill="white"/>
            </clipPath>
        </defs>
    </svg>
</div>

        <h1 class="page-title">Verify Your Identity</span></h1>
        <p class="page-sub">Search your full name as registered with the Municipal Civil Registry Office.</p>

        <div class="search-field-wrap">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fafcfb" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
          <input type="text" id="fullNameInput" placeholder="Enter your full name"
                 value="<?= htmlspecialchars($fullName) ?>" autocomplete="off"
                 onkeydown="if(event.key==='Enter') doSearch();">
             </div>     
        <div class="info-banner">
          <span class="i-circle">
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="#FFFFFF"
        aria-hidden="true">

        <path d="M12 22C6.47715 22 2 17.5228 2 12
        2 6.47715 6.47715 2 12 2
        17.5228 2 22 6.47715 22 12
        22 17.5228 17.5228 22 12 22ZM12 20
        C16.4183 20 20 16.4183 20 12
        20 7.58172 16.4183 4 12 4
        7.58172 4 4 7.58172 4 12
        4 16.4183 7.58172 20 12 20ZM13 10.5V15H14V17H10V15H11V12.5H10V10.5H13ZM13.5 8
        C13.5 8.82843 12.8284 9.5 12 9.5
        11.1716 9.5 10.5 8.82843 10.5 8
        10.5 7.17157 11.1716 6.5 12 6.5
        12.8284 6.5 13.5 7.17157 13.5 8Z"/>
    </svg>
</span>
          <p>This information will only be used to verify your identity with MCRO records.</p>
        </div>
        <button type="button" class="search-btn" onclick="doSearch()">
          Search
        </button>
      </div>
    </div>
    <div style="height:20px;"></div>
  </section>

  <!-- SCREEN 2: SEARCHING -->
  <section class="screen" id="screen-searching">
    <h1 class="s-title">Searching MCRO Database</h1>
    <p class="s-sub">Please wait while we search for your records...</p>
    <div class="loader-icon-wrap">
      <svg width="150" height="150" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="paperGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#f4f7ff"/>
            <stop offset="100%" stop-color="#dbe1f2"/>
          </linearGradient>
          <linearGradient id="foldGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#ffffff"/>
            <stop offset="100%" stop-color="#c6cee6"/>
          </linearGradient>
          <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#3a3a42"/>
            <stop offset="50%" stop-color="#111114"/>
            <stop offset="100%" stop-color="#000000"/>
          </linearGradient>
          <radialGradient id="lensGrad" cx="38%" cy="28%" r="78%">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.95"/>
            <stop offset="32%" stop-color="#eaf3fc" stop-opacity="0.85"/>
            <stop offset="62%" stop-color="#bfe0fa" stop-opacity="0.55"/>
            <stop offset="100%" stop-color="#8fc3ef" stop-opacity="0.35"/>
          </radialGradient>
          <linearGradient id="neckGrad" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#eef2f7"/>
            <stop offset="30%" stop-color="#9aa3ad"/>
            <stop offset="55%" stop-color="#4c525b"/>
            <stop offset="80%" stop-color="#c7ccd2"/>
            <stop offset="100%" stop-color="#7a828c"/>
          </linearGradient>
          <linearGradient id="handleGrad" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#123055"/>
            <stop offset="20%" stop-color="#2f6fc4"/>
            <stop offset="45%" stop-color="#6fb0f0"/>
            <stop offset="60%" stop-color="#3f7fd0"/>
            <stop offset="85%" stop-color="#173a6e"/>
            <stop offset="100%" stop-color="#0a1f40"/>
          </linearGradient>
          <linearGradient id="ringBevel" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#6a6a74"/>
            <stop offset="45%" stop-color="#1a1a1e"/>
            <stop offset="100%" stop-color="#000000"/>
          </linearGradient>
          <clipPath id="lensClip">
            <circle cx="235" cy="215" r="60"/>
          </clipPath>
          <filter id="frostBlur" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur stdDeviation="2.2"/>
          </filter>
          <filter id="dropSoft" x="-60%" y="-60%" width="220%" height="220%">
            <feDropShadow dx="0" dy="8" stdDeviation="10" flood-color="#9aa3c9" flood-opacity="0.4"/>
          </filter>
          <filter id="dropTight" x="-60%" y="-60%" width="220%" height="220%">
            <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#8890b8" flood-opacity="0.35"/>
          </filter>
        </defs>

        <style>
          .card{ transform-box: fill-box; transform-origin: center; }
          .card-back{ animation: swayBack 4.6s ease-in-out infinite; }
          .card-mid{ animation: swayMid 4.6s ease-in-out infinite; animation-delay:.15s; }
          .card-front{ animation: swayFront 4.6s ease-in-out infinite; animation-delay:.3s; }
          @keyframes swayBack{ 0%,100%{ transform:rotate(-14deg) translateY(0);} 50%{ transform:rotate(-14deg) translateY(-3px);} }
          @keyframes swayMid{ 0%,100%{ transform:rotate(-6deg) translateY(0);} 50%{ transform:rotate(-6deg) translateY(-4px);} }
          @keyframes swayFront{ 0%,100%{ transform:rotate(-1deg) translateY(0);} 50%{ transform:rotate(-1deg) translateY(-5px);} }

          .highlight-bar{ animation: barGlow 2.4s ease-in-out infinite; }
          @keyframes barGlow{ 0%,100%{ opacity:1; } 50%{ opacity:.55; } }

          .gray-line{ animation: lineFade 2.4s ease-in-out infinite; }
          .gray-line:nth-child(1){ animation-delay:0s; }
          .gray-line:nth-child(2){ animation-delay:.15s; }
          .gray-line:nth-child(3){ animation-delay:.3s; }
          .gray-line:nth-child(4){ animation-delay:.45s; }
          @keyframes lineFade{ 0%,100%{ fill:#c9cfe0; } 50%{ fill:#aeb7d4; } }

          .mag-roam{
            transform-box: fill-box;
            transform-origin: center;
            animation: magRoam 5s ease-in-out infinite;
          }
          @keyframes magRoam{
            0%   { transform: translate(0,0) rotate(0deg); }
            20%  { transform: translate(-22px,-14px) rotate(-5deg); }
            40%  { transform: translate(12px,16px) rotate(4deg); }
            60%  { transform: translate(-14px,20px) rotate(-4deg); }
            80%  { transform: translate(18px,-10px) rotate(5deg); }
            100% { transform: translate(0,0) rotate(0deg); }
          }

          .lens-shine{
            animation: shineSweep 3s ease-in-out infinite;
          }
          @keyframes shineSweep{
            0%,100%{ opacity:.35; transform: translate(0,0); }
            50%{ opacity:.9; transform: translate(6px,4px); }
          }
        </style>

        <!-- BACK CARD -->
        <g class="card card-back">
          <rect x="60" y="40" width="150" height="210" rx="20" fill="url(#paperGrad)" filter="url(#dropTight)"/>
          <circle cx="105" cy="88" r="24" fill="#ffffff"/>
          <circle cx="105" cy="82" r="8" fill="#2f6bff"/>
          <path d="M89 100a16 12 0 0 1 32 0z" fill="#2f6bff"/>
          <rect x="83" y="122" width="45" height="12" rx="6" fill="#3a86ff"/>
          <rect class="gray-line" x="83" y="146" width="58" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="83" y="164" width="50" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="83" y="182" width="55" height="8" rx="4" fill="#c9cfe0"/>
        </g>

        <!-- MIDDLE CARD -->
        <g class="card card-mid">
          <rect x="95" y="70" width="150" height="210" rx="20" fill="url(#paperGrad)" filter="url(#dropTight)"/>
          <circle cx="140" cy="118" r="24" fill="#ffffff"/>
          <circle cx="140" cy="112" r="8" fill="#2f7bff"/>
          <path d="M124 130a16 12 0 0 1 32 0z" fill="#2f7bff"/>
          <rect x="118" y="152" width="45" height="12" rx="6" fill="#4d9cff"/>
          <rect class="gray-line" x="118" y="176" width="58" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="118" y="194" width="50" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="118" y="212" width="55" height="8" rx="4" fill="#c9cfe0"/>
        </g>

        <!-- FRONT CARD -->
        <g class="card card-front" filter="url(#dropSoft)">
          <rect x="130" y="100" width="150" height="210" rx="20" fill="url(#paperGrad)"/>
          <!-- folded corner -->
          <path d="M240 100 L280 100 L280 140 Z" fill="url(#foldGrad)"/>
          <circle cx="175" cy="148" r="24" fill="#ffffff"/>
          <circle cx="175" cy="142" r="8" fill="#34c76f"/>
          <path d="M159 160a16 12 0 0 1 32 0z" fill="#34c76f"/>
          <rect class="highlight-bar" x="153" y="182" width="45" height="12" rx="6" fill="#37d488"/>
          <rect class="gray-line" x="153" y="206" width="60" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="153" y="224" width="55" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="153" y="242" width="58" height="8" rx="4" fill="#c9cfe0"/>
          <rect class="gray-line" x="153" y="260" width="50" height="8" rx="4" fill="#c9cfe0"/>
        </g>

        <!-- MAGNIFYING GLASS, roaming as if searching -->
        <g class="mag-roam" filter="url(#dropSoft)">
          <!-- outer black bevel ring -->
          <circle cx="235" cy="215" r="78" fill="url(#ringBevel)"/>
          <circle cx="235" cy="215" r="70" fill="none" stroke="#3a3a42" stroke-width="1.5" opacity="0.6"/>
          <circle cx="235" cy="215" r="66" fill="#0a0a0c"/>

          <!-- bright glass lens -->
          <circle cx="235" cy="215" r="62" fill="url(#lensGrad)"/>

          <!-- rim highlight -->
          <circle cx="235" cy="215" r="78" fill="none" stroke="#5c5c66" stroke-width="2" opacity="0.4"/>
          <circle cx="235" cy="215" r="62" fill="none" stroke="#000000" stroke-width="3"/>

          <!-- glossy specular shine -->
          <ellipse class="lens-shine" cx="205" cy="182" rx="24" ry="34" fill="#ffffff" opacity="0.75" transform="rotate(-28 205 182)"/>
          <circle cx="196" cy="220" r="6" fill="#ffffff" opacity="0.55"/>
        </g>
      </svg>
    </div>
    <div class="progress-track"><div class="progress-fill"></div></div>
    <p style="text-align:center;font-size:12px;color:#6d759a;">Please wait...</p>
    <button type="button"
        class="btn-cancel-search"
        onclick="showScreen('screen-search')">
    Cancel
</button>
  </section>

  <!-- SCREEN 3: RECORD FOUND -->
  <section class="screen" id="screen-found">
    <div class="icon-circle green">
    <svg
        width="36"
        height="36"
        viewBox="0 0 24 24"
        fill="none"
        stroke="#39FF88"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round">

        <path d="M20 6L9 17l-5-5"/>
    </svg>
</div>
    <h1 class="s-title">Record Found</h1>
    <p class="s-sub" id="foundDesc">Your name has been successfully verified in the MCRO database.</p>
    <p class="s-sub" style="margin-top:-12px;">You may now proceed with creating your GeoGene account.</p>
    <a href="#" id="proceedBtn" class="btn-primary">Proceed to ID Verification</a>
    <button type="button"
        class="btn-cancel-registration"
        onclick="showScreen('screen-search')">
    Cancel
</button>
  </section>

  <!-- SCREEN 4: RECORD NOT FOUND -->
  <section class="screen" id="screen-notfound">
    <div class="icon-circle red">
    <svg
        width="36"
        height="36"
        viewBox="0 0 24 24"
        fill="none"
        stroke="#ff5c6c"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round">

        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
    </svg>
</div>
    <h1 class="s-title">Record Not Found</h1>
    <p class="s-sub">No matching record was found in the MCRO database.</p>
    <p class="s-sub" style="margin-top:-12px;">Please check your full name and search again. If your record is still unavailable, visit the Municipal Civil Registry Office (MCRO).</p>
    <button type="button" class="btn-primary" onclick="showScreen('screen-search')" style="border:none;">Search Again</button>
  </section>
</div>

<script>
function showScreen(id) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

async function doSearch() {
  const name = document.getElementById('fullNameInput').value.trim();
  if (!name) {
    alert('Please enter your full name.');
    return;
  }

  showScreen('screen-searching');
  document.querySelector('.progress-fill').style.animation = 'none';
  void document.querySelector('.progress-fill').offsetWidth;
  document.querySelector('.progress-fill').style.animation = 'fillProgress 2.6s ease forwards';

  try {
    const res = await fetch('check-name.php?full_name=' + encodeURIComponent(name));
    const data = await res.json();

    setTimeout(() => {
      if (data.found) {
        document.getElementById('proceedBtn').href =
          '../identity-verification/scan-id.php?record_id=' + data.record.record_id + '&name=' + encodeURIComponent(name);
        showScreen('screen-found');
      } else {
        showScreen('screen-notfound');
      }
    }, 2700);
  } catch (err) {
    alert('Something went wrong while searching. Please try again.');
    showScreen('screen-search');
  }
}
</script>
</body>
</html>