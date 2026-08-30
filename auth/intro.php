<?php
session_start();
require_once __DIR__ . '/../includes/logo.php';
// Kung naka-login na, diretso sa dashboard imbis makita pag-usab ang intro
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Welcome</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{overflow-x:hidden;max-width:100%;}
  body{
    min-height:100vh;
    display:flex;align-items:center;justify-content:center;
    background:#e8e8e6;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  }

  .phone{
    width:360px;height:680px;
    border-radius:32px;
    overflow:hidden;
    position:relative;
    box-shadow:0 20px 50px rgba(0,0,0,0.25);
    background:#0B132B;
  }

  @media (max-width: 480px){
    body{ display:block; }
    .phone{
      width:100%;height:100vh;height:100dvh;
      border-radius:0;box-shadow:none;
    }
  }

  .intro-screen{
    position:absolute;inset:0;
    background:radial-gradient(circle at 20% 15%, rgba(57,255,136,0.22), transparent 45%), radial-gradient(circle at 85% 85%, rgba(57,255,136,0.15), transparent 40%), linear-gradient(160deg,#152244 0%,#0B132B 100%);
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:40px 32px;text-align:center;color:#fff;
    transition:transform .7s cubic-bezier(.65,0,.35,1), opacity .6s ease;
    overflow:hidden;
  }
  .intro-screen.hide{ transform:translateY(-100%); opacity:0; pointer-events:none; }

  .blob{
    position:absolute;border-radius:50%;
    background:rgba(255,255,255,0.08);
    animation:float 7s ease-in-out infinite;
  }
  .blob.b1{width:180px;height:180px;top:-60px;left:-50px;animation-delay:0s;}
  .blob.b2{width:120px;height:120px;bottom:40px;right:-40px;animation-delay:1.5s;}
  .blob.b3{width:80px;height:80px;bottom:-20px;left:40px;animation-delay:3s;}
  @keyframes float{
    0%,100%{transform:translateY(0) scale(1);}
    50%{transform:translateY(-16px) scale(1.06);}
  }

  .hello-text{font-size:14px;font-weight:400;opacity:.85;letter-spacing:.06em;text-transform:uppercase;animation:fadeSlideUp .7s ease .1s both;}
  .welcome-text{font-size:28px;font-weight:700;margin-top:4px;margin-bottom:34px;animation:fadeSlideUp .7s ease .25s both;}

  .logo-circle{
    width:92px;height:92px;border-radius:50%;
    background:rgba(57,255,136,0.12);border:1px solid rgba(57,255,136,0.3);
    display:flex;align-items:center;justify-content:center;
    margin-bottom:18px;
    animation:popIn .7s cubic-bezier(.34,1.56,.64,1) .4s both, pulse 2.6s ease-in-out 1.2s infinite;
    backdrop-filter:blur(4px);
  }
  .logo-circle{
    width:100px;
    height:100px;
    border-radius:50%;
    background:rgba(255,255,255,0.15);
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:18px;
    animation:
        popIn .7s cubic-bezier(.34,1.56,.64,1) .4s both,
        pulse 2.6s ease-in-out 1.2s infinite;
    backdrop-filter:blur(4px);
    overflow:hidden;
}

.logo-img{
    width:90px;
    height:90px;
    object-fit:contain;
    display:block;
    animation:logoGlow 2.6s ease-in-out 1.2s infinite;
}

@keyframes logoGlow{
    0%,100%{
        transform:scale(1);
        filter:drop-shadow(0 0 0 rgba(255,255,255,0));
    }
    50%{
        transform:scale(1.08);
        filter:drop-shadow(0 0 10px rgba(255,255,255,.45));
    }
}

  .brand-name{font-size:24px;font-weight:800;letter-spacing:.01em;margin-bottom:10px;animation:fadeSlideUp .7s ease .5s both;}
  .brand-tagline{font-size:12.5px;line-height:1.6;opacity:.8;max-width:230px;margin-bottom:38px;animation:fadeSlideUp .7s ease .6s both;}

  @keyframes fadeSlideUp{ from{opacity:0;transform:translateY(14px);} to{opacity:1;transform:translateY(0);} }
  @keyframes popIn{ 0%{opacity:0;transform:scale(0.4);} 100%{opacity:1;transform:scale(1);} }
  @keyframes pulse{ 0%,100%{box-shadow:0 0 0 0 rgba(57,255,136,0.35);} 50%{box-shadow:0 0 0 16px rgba(57,255,136,0);} }

  .get-started-btn{
    width:70%;
    height:40px;

    /* Center horizontally */
    margin:0 auto;

    background:linear-gradient(160deg,#5cffa3,#39FF88);
    color:#0B132B;

    border:none;
    border-radius:50px;

    padding:0;
    font-size:15px;
    font-weight:650;

    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;

    /* Entrance animation */
    animation:
        buttonAppear .8s cubic-bezier(.22,1,.36,1) .75s both,
        buttonGlow 2.8s ease-in-out 1.6s infinite;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        filter .2s ease;
}

/* Nice entrance */
@keyframes buttonAppear{
    0%{
        opacity:0;
        transform:translateY(25px) scale(.92);
    }
    60%{
        opacity:1;
        transform:translateY(-3px) scale(1.02);
    }
    100%{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

/* Soft green glow */
@keyframes buttonGlow{
    0%,100%{
        box-shadow:0 6px 20px rgba(57,255,136,.20);
    }
    50%{
        box-shadow:0 8px 28px rgba(57,255,136,.45);
    }
}

/* Mouse hover */
.get-started-btn:hover{
    transform:translateY(-2px) scale(1.02);
    filter:brightness(1.05);
    box-shadow:0 10px 30px rgba(57,255,136,.45);
}

/* Click animation */
.get-started-btn:active{
    transform:scale(.95);
    box-shadow:0 4px 12px rgba(57,255,136,.25);
}
</style>