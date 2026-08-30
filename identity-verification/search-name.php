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