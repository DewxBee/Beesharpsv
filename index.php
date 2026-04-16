<?php
require_once __DIR__ . '/config.php';
$csrfToken   = csrfToken();
$sessionData = json_encode([
    'admin_id'      => $_SESSION['admin_id']      ?? null,
    'customer_id'   => $_SESSION['customer_id']   ?? null,
    'customer_name' => $_SESSION['customer_name'] ?? null,
    'role'          => $_SESSION['role']           ?? null,
]);
?>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
<title><?php echo htmlspecialchars($businessName); ?> – Professional Sharpening</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --orange: #F7931A;
  --orange-dark: #d4780e;
  --orange-light: #ffa833;
  --black: #000000;
  --dark: #111111;
  --card: #1a1a1a;
  --card2: #222222;
  --text: #e0e0e0;
  --text-dim: #999;
  --border: #333;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--black); color:var(--text); font-family:'Rajdhani',sans-serif; font-size:16px; line-height:1.6; }
h1,h2,h3,h4,h5 { font-family:'Oswald',sans-serif; color:var(--orange); letter-spacing:1px; }
a { color:var(--orange); text-decoration:none; }
a:hover { color:var(--orange-light); }
img { max-width:100%; }

/* NAV */
nav {
  position:sticky; top:0; z-index:1000;
  background:#000000ee;
  backdrop-filter:blur(10px);
  border-bottom:1px solid var(--border);
  padding:0 16px;
  display:flex; align-items:center; justify-content:space-between;
  height:64px;
}
.nav-logo { display:flex; align-items:center; gap:10px; }
.nav-logo img { height:44px; width:44px; border-radius:50%; object-fit:cover; }
.nav-logo span { font-family:'Oswald',sans-serif; font-size:1.3rem; color:var(--orange); font-weight:700; }
.nav-links { display:flex; gap:4px; flex-wrap:wrap; }
.nav-links a { font-family:'Oswald',sans-serif; font-size:0.85rem; padding:6px 10px; border-radius:4px; color:var(--text); transition:all .2s; letter-spacing:0.5px; }
.nav-links a:hover, .nav-links a.active { color:var(--orange); background:rgba(247,147,26,0.1); }
.nav-right { display:flex; align-items:center; gap:8px; }
.lang-toggle { background:var(--card); border:1px solid var(--border); color:var(--orange); padding:5px 12px; border-radius:20px; cursor:pointer; font-family:'Oswald',sans-serif; font-size:0.8rem; transition:all .2s; }
.lang-toggle:hover { background:var(--orange); color:#000; }
.hamburger { display:none; flex-direction:column; gap:5px; cursor:pointer; padding:4px; }
.hamburger span { width:24px; height:2px; background:var(--orange); border-radius:2px; transition:all .3s; }
.mobile-menu { display:none; background:#000; border-top:1px solid var(--border); padding:12px 16px; flex-direction:column; gap:4px; }
.mobile-menu a { font-family:'Oswald',sans-serif; font-size:1rem; padding:10px 8px; color:var(--text); border-bottom:1px solid #1a1a1a; display:block; }
.mobile-menu a:hover { color:var(--orange); }
@media(max-width:768px) {
  .nav-links { display:none; }
  .hamburger { display:flex; }
  .mobile-menu.open { display:flex; }
}

/* PAGES */
.page { display:none; min-height:100vh; }
.page.active { display:block; }

/* HERO */
.hero {
  background: linear-gradient(135deg, #000 0%, #1a0a00 50%, #000 100%);
  text-align:center; padding:80px 20px 60px;
  border-bottom:2px solid var(--orange);
  position:relative; overflow:hidden;
}
.hero::before {
  content:''; position:absolute; inset:0;
  background: radial-gradient(ellipse at center, rgba(247,147,26,0.08) 0%, transparent 70%);
}
.hero-logo { width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid var(--orange); box-shadow:0 0 30px rgba(247,147,26,0.4); margin-bottom:24px; position:relative; z-index:1; }
.hero h1 { font-size:clamp(2rem,6vw,3.5rem); margin-bottom:8px; position:relative; z-index:1; }
.hero .tagline { color:var(--orange-light); font-size:1.1rem; font-family:'Oswald',sans-serif; letter-spacing:2px; margin-bottom:16px; position:relative; z-index:1; }
.hero p { color:var(--text-dim); max-width:560px; margin:0 auto 28px; font-size:1rem; position:relative; z-index:1; }
.hero-btns { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; position:relative; z-index:1; }
.btn { display:inline-block; padding:12px 28px; border-radius:6px; font-family:'Oswald',sans-serif; font-size:1rem; letter-spacing:1px; font-weight:600; transition:all .2s; cursor:pointer; border:none; }
.btn-primary { background:var(--orange); color:#000; }
.btn-primary:hover { background:var(--orange-light); color:#000; transform:translateY(-2px); }
.btn-outline { background:transparent; color:var(--orange); border:2px solid var(--orange); }
.btn-outline:hover { background:var(--orange); color:#000; transform:translateY(-2px); }
.btn-dark { background:var(--card); color:var(--text); border:1px solid var(--border); }
.btn-dark:hover { border-color:var(--orange); color:var(--orange); }

/* SECTION */
section { padding:60px 20px; max-width:1100px; margin:0 auto; }
.section-title { font-size:clamp(1.5rem,4vw,2.5rem); margin-bottom:8px; }
.section-sub { color:var(--text-dim); margin-bottom:40px; font-size:1rem; }
.divider { border:none; border-top:1px solid var(--border); margin:20px 0; }

/* CARDS */
.cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:20px; }
.card { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:24px; transition:border-color .2s, transform .2s; }
.card:hover { border-color:var(--orange); transform:translateY(-3px); }
.card-icon { font-size:2rem; margin-bottom:12px; }
.card h3 { color:var(--orange); margin-bottom:8px; font-size:1.2rem; }
.card p { color:var(--text-dim); font-size:0.95rem; }

/* PRICING TABLE */
.pricing-table { width:100%; border-collapse:collapse; margin-bottom:30px; }
.pricing-table th { background:var(--orange); color:#000; font-family:'Oswald',sans-serif; padding:12px 16px; text-align:left; font-size:1rem; }
.pricing-table td { padding:12px 16px; border-bottom:1px solid var(--border); color:var(--text); }
.pricing-table tr:nth-child(even) td { background:var(--card); }
.pricing-table tr:hover td { background:rgba(247,147,26,0.05); }
.price-badge { background:var(--orange); color:#000; font-weight:700; padding:3px 10px; border-radius:20px; font-family:'Oswald',sans-serif; font-size:0.9rem; }
.discount-box { background:var(--card); border:1px solid var(--orange); border-radius:10px; padding:20px; margin-bottom:20px; }
.discount-box h3 { color:var(--orange); margin-bottom:10px; }
.discount-box ul { list-style:none; }
.discount-box ul li { padding:6px 0; border-bottom:1px solid var(--border); color:var(--text); }
.discount-box ul li:before { content:'⚡ '; color:var(--orange); }
.free-badge { background:rgba(247,147,26,0.15); border:1px solid var(--orange); color:var(--orange); padding:12px 20px; border-radius:8px; text-align:center; font-family:'Oswald',sans-serif; font-size:1.1rem; margin-top:20px; }

/* SERVICES */
.service-item { display:flex; gap:20px; align-items:flex-start; background:var(--card); border:1px solid var(--border); border-radius:10px; padding:20px; margin-bottom:16px; transition:border-color .2s; }
.service-item:hover { border-color:var(--orange); }
.service-icon { font-size:2.2rem; min-width:48px; text-align:center; }
.service-info h3 { color:var(--orange); margin-bottom:4px; }
.service-info p { color:var(--text-dim); font-size:0.95rem; }

/* ABOUT */
.about-grid { display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center; }
@media(max-width:640px) { .about-grid { grid-template-columns:1fr; } }
.about-img { border-radius:12px; border:2px solid var(--orange); box-shadow:0 0 20px rgba(247,147,26,0.2); width:100%; }
.about-text h2 { margin-bottom:16px; }
.about-text p { color:var(--text-dim); margin-bottom:12px; }
.social-links { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }
.social-btn { display:flex; align-items:center; gap:8px; background:var(--card); border:1px solid var(--border); padding:8px 16px; border-radius:6px; color:var(--text); font-size:0.9rem; transition:all .2s; }
.social-btn:hover { border-color:var(--orange); color:var(--orange); }
.social-btn svg { width:18px; height:18px; }

/* FORMS */
.form-group { margin-bottom:18px; }
.form-group label { display:block; color:var(--orange); font-family:'Oswald',sans-serif; font-size:0.9rem; margin-bottom:6px; letter-spacing:0.5px; }
.form-group input, .form-group select, .form-group textarea {
  width:100%; background:var(--card); border:1px solid var(--border); border-radius:6px;
  padding:10px 14px; color:var(--text); font-family:'Rajdhani',sans-serif; font-size:1rem;
  transition:border-color .2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  outline:none; border-color:var(--orange); box-shadow:0 0 0 2px rgba(247,147,26,0.15);
}
.form-group select option { background:var(--card); }
.form-group textarea { resize:vertical; min-height:100px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:500px) { .form-row { grid-template-columns:1fr; } }
.form-note { color:var(--text-dim); font-size:0.85rem; margin-top:4px; }
.checkbox-group { display:flex; align-items:center; gap:10px; }
.checkbox-group input[type=checkbox] { width:18px; height:18px; accent-color:var(--orange); }

/* ORDER ITEMS */
.order-item-row { display:flex; gap:10px; align-items:center; margin-bottom:10px; background:var(--card); padding:12px; border-radius:8px; border:1px solid var(--border); }
.order-item-row select, .order-item-row input { flex:1; }
.remove-btn { background:none; border:1px solid #444; color:#888; padding:6px 10px; border-radius:4px; cursor:pointer; }
.remove-btn:hover { border-color:#f44; color:#f44; }
#add-item-btn { background:none; border:1px dashed var(--orange); color:var(--orange); padding:10px; width:100%; border-radius:6px; cursor:pointer; font-family:'Oswald',sans-serif; font-size:0.95rem; margin-bottom:16px; }
#add-item-btn:hover { background:rgba(247,147,26,0.08); }
.order-summary { background:var(--card2); border:1px solid var(--orange); border-radius:8px; padding:16px; margin-bottom:20px; }
.order-summary h4 { color:var(--orange); margin-bottom:10px; }
.summary-row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid var(--border); font-size:0.95rem; }
.summary-total { display:flex; justify-content:space-between; padding:10px 0 0; font-family:'Oswald',sans-serif; font-size:1.2rem; color:var(--orange); }

/* PAYMENT */
.payment-options { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
@media(max-width:500px) { .payment-options { grid-template-columns:1fr; } }
.payment-option { background:var(--card); border:2px solid var(--border); border-radius:10px; padding:20px; text-align:center; cursor:pointer; transition:all .2s; }
.payment-option:hover, .payment-option.selected { border-color:var(--orange); background:rgba(247,147,26,0.08); }
.payment-option .pay-icon { font-size:2.5rem; margin-bottom:8px; }
.payment-option h3 { color:var(--orange); margin-bottom:4px; }
.payment-option p { color:var(--text-dim); font-size:0.85rem; }
.bitcoin-discount { background:rgba(247,147,26,0.1); border:1px solid var(--orange); border-radius:6px; padding:10px 16px; color:var(--orange); text-align:center; margin-bottom:20px; font-family:'Oswald',sans-serif; }

/* LOGIN */
.auth-container { max-width:420px; margin:60px auto; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:36px; }
.auth-container h2 { text-align:center; margin-bottom:8px; }
.auth-container p { text-align:center; color:var(--text-dim); margin-bottom:28px; font-size:0.9rem; }
.auth-tabs { display:flex; margin-bottom:24px; border-bottom:1px solid var(--border); }
.auth-tab { flex:1; padding:10px; text-align:center; font-family:'Oswald',sans-serif; cursor:pointer; color:var(--text-dim); border-bottom:2px solid transparent; transition:all .2s; }
.auth-tab.active { color:var(--orange); border-bottom-color:var(--orange); }
.auth-form { display:none; }
.auth-form.active { display:block; }
.forgot-link { text-align:right; margin-top:-10px; margin-bottom:16px; }
.forgot-link a { font-size:0.85rem; color:var(--text-dim); }
.forgot-link a:hover { color:var(--orange); }

/* CRM TABLE */
.crm-controls { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.crm-controls input, .crm-controls select { background:var(--card); border:1px solid var(--border); border-radius:6px; padding:8px 12px; color:var(--text); font-family:'Rajdhani',sans-serif; flex:1; min-width:140px; }
.crm-controls input:focus, .crm-controls select:focus { outline:none; border-color:var(--orange); }
.data-table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size:0.9rem; }
.data-table th { background:var(--card2); color:var(--orange); font-family:'Oswald',sans-serif; padding:10px 12px; text-align:left; border-bottom:2px solid var(--orange); font-size:0.85rem; }
.data-table td { padding:10px 12px; border-bottom:1px solid var(--border); color:var(--text); }
.data-table tr:hover td { background:rgba(247,147,26,0.04); }
.badge { padding:3px 8px; border-radius:12px; font-size:0.78rem; font-family:'Oswald',sans-serif; }
.badge-green { background:rgba(0,200,100,0.15); color:#00c864; border:1px solid rgba(0,200,100,0.3); }
.badge-orange { background:rgba(247,147,26,0.15); color:var(--orange); border:1px solid rgba(247,147,26,0.3); }
.badge-gray { background:rgba(150,150,150,0.15); color:#999; border:1px solid rgba(150,150,150,0.3); }
.badge-blue { background:rgba(100,150,255,0.15); color:#6496ff; border:1px solid rgba(100,150,255,0.3); }
.action-btn { background:none; border:1px solid var(--border); color:var(--text-dim); padding:3px 8px; border-radius:4px; cursor:pointer; font-size:0.8rem; margin-right:4px; }
.action-btn:hover { border-color:var(--orange); color:var(--orange); }
.table-wrapper { overflow-x:auto; }
.stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:30px; }
.stat-card { background:var(--card); border:1px solid var(--border); border-radius:8px; padding:16px; text-align:center; }
.stat-card .stat-num { font-family:'Oswald',sans-serif; font-size:2rem; color:var(--orange); }
.stat-card .stat-label { color:var(--text-dim); font-size:0.85rem; }

/* PRIVACY */
.policy-content h3 { color:var(--orange); margin:24px 0 8px; }
.policy-content p, .policy-content li { color:var(--text-dim); margin-bottom:8px; line-height:1.7; }
.policy-content ul { padding-left:20px; }

/* MAP */
.map-placeholder { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:40px; text-align:center; margin-top:20px; }
.map-placeholder a { color:var(--orange); font-family:'Oswald',sans-serif; font-size:1.1rem; }

/* FOOTER */
footer { background:var(--dark); border-top:2px solid var(--orange); padding:40px 20px 20px; }
.footer-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:30px; max-width:1100px; margin:0 auto 30px; }
.footer-col h4 { color:var(--orange); font-family:'Oswald',sans-serif; margin-bottom:14px; font-size:1.1rem; letter-spacing:1px; }
.footer-col p, .footer-col a { color:var(--text-dim); font-size:0.9rem; display:block; margin-bottom:6px; }
.footer-col a:hover { color:var(--orange); }
.footer-bottom { text-align:center; color:var(--text-dim); font-size:0.8rem; border-top:1px solid var(--border); padding-top:20px; max-width:1100px; margin:0 auto; }

/* WHATSAPP FLOAT */
.wa-float { position:fixed; bottom:24px; right:24px; z-index:999; width:58px; height:58px; background:#25D366; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 16px rgba(37,211,102,0.5); transition:transform .2s; text-decoration:none; }
.wa-float:hover { transform:scale(1.1); background:#20bc5a; }
.wa-float svg { width:32px; height:32px; fill:#fff; }

/* NOTIFICATIONS */
.toast { position:fixed; top:80px; right:20px; background:var(--orange); color:#000; padding:12px 20px; border-radius:8px; font-family:'Oswald',sans-serif; font-size:1rem; z-index:9999; opacity:0; transform:translateY(-10px); transition:all .3s; pointer-events:none; }
.toast.show { opacity:1; transform:translateY(0); }

/* PAGE HERO */
.page-hero { background:linear-gradient(to right, #000, #1a0800, #000); border-bottom:1px solid var(--border); padding:40px 20px; text-align:center; }
.page-hero h1 { font-size:clamp(1.8rem,5vw,3rem); }
.page-hero p { color:var(--text-dim); margin-top:8px; }

/* ADMIN LINK */
.admin-bar { background:var(--card2); border-bottom:1px solid var(--border); padding:6px 20px; text-align:right; }
.admin-bar a { color:var(--text-dim); font-size:0.8rem; }
.admin-bar a:hover { color:var(--orange); }

/* TABS */
.tab-nav { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:24px; flex-wrap:wrap; }
.tab-btn { padding:10px 20px; font-family:'Oswald',sans-serif; background:none; border:none; color:var(--text-dim); cursor:pointer; font-size:1rem; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .2s; }
.tab-btn.active { color:var(--orange); border-bottom-color:var(--orange); }
.tab-content { display:none; }
.tab-content.active { display:block; }

/* SCHEDULING CALENDAR */
.time-slots { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin-top:16px; }
.time-slot { background:var(--card); border:1px solid var(--border); border-radius:6px; padding:10px; text-align:center; cursor:pointer; font-size:0.9rem; transition:all .2s; }
.time-slot:hover { border-color:var(--orange); color:var(--orange); }
.time-slot.selected { background:var(--orange); color:#000; border-color:var(--orange); font-weight:700; }
.time-slot.unavailable { opacity:0.3; cursor:not-allowed; }

/* BITCOIN INFO */
.bitcoin-box { background:var(--card); border:1px solid var(--orange); border-radius:10px; padding:24px; margin-bottom:20px; }
.bitcoin-box h3 { color:var(--orange); margin-bottom:12px; }
.bitcoin-addr { background:#000; border:1px solid var(--border); border-radius:6px; padding:10px 14px; font-family:monospace; color:var(--orange); word-break:break-all; margin:10px 0; font-size:0.85rem; }
.qr-placeholder { width:160px; height:160px; background:var(--card2); border:2px solid var(--orange); border-radius:8px; display:flex; align-items:center; justify-content:center; margin:16px auto; color:var(--text-dim); font-size:0.85rem; text-align:center; }

/* ALERTS */
.alert { border-radius:8px; padding:14px 18px; margin-bottom:16px; font-size:0.95rem; }
.alert-info { background:rgba(100,150,255,0.1); border:1px solid rgba(100,150,255,0.3); color:#6496ff; }
.alert-success { background:rgba(0,200,100,0.1); border:1px solid rgba(0,200,100,0.3); color:#00c864; }
.alert-orange { background:rgba(247,147,26,0.1); border:1px solid var(--orange); color:var(--orange); }

/* CONTACT FORM */
.contact-form { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:30px; margin-bottom:40px; }
.contact-form h3 { color:var(--orange); margin-bottom:20px; }
.contact-status { margin-bottom:16px; display:none; padding:12px; border-radius:6px; }
.contact-status.success { background:rgba(0,200,100,0.1); border:1px solid rgba(0,200,100,0.3); color:#00c864; display:block; }
.contact-status.error { background:rgba(200,0,0,0.1); border:1px solid rgba(200,0,0,0.3); color:#ff6b6b; display:block; }
</style>
</head>
<body>

<!-- ADMIN BAR -->
<div class="admin-bar">
  <a href="admin.html" target="_blank" data-en="Admin Panel" data-es="Panel Admin">Admin Panel</a>
</div>

<!-- NAV -->
<nav>
  <div class="nav-logo">
    <img src="logo-black.jpeg" alt="<?php echo htmlspecialchars($businessName); ?>" onerror="this.style.display='none'">
    <span><?php echo htmlspecialchars($businessName); ?></span>
  </div>
  <div class="nav-links">
    <a href="#" class="active" onclick="showPage('home',this)" data-en="Home" data-es="Inicio">Home</a>
    <a href="#" onclick="showPage('services',this)" data-en="Services" data-es="Servicios">Services</a>
    <a href="#" onclick="showPage('pricing',this)" data-en="Pricing" data-es="Precios">Pricing</a>
    <a href="#" onclick="showPage('order',this)" data-en="Order" data-es="Ordenar">Order</a>
    <a href="#" onclick="showPage('payment',this)" data-en="Payment" data-es="Pago">Payment</a>
    <a href="#" onclick="showPage('about',this)" data-en="About" data-es="Nosotros">About</a>
    <a href="#" onclick="showPage('contact',this)" data-en="Contact" data-es="Contacto">Contact</a>
    <a href="#" onclick="showPage('login',this)" data-en="Login" data-es="Acceso">Login</a>
    <a href="#" onclick="showPage('privacy',this)" data-en="Privacy" data-es="Privacidad">Privacy</a>
  </div>
  <div class="nav-right">
    <button class="lang-toggle" onclick="toggleLang()" id="langBtn">ES</button>
    <div class="hamburger" onclick="toggleMenu()" id="hamburger">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
  <a href="#" onclick="showPage('home',null,true)" data-en="Home" data-es="Inicio">Home</a>
  <a href="#" onclick="showPage('services',null,true)" data-en="Services" data-es="Servicios">Services</a>
  <a href="#" onclick="showPage('pricing',null,true)" data-en="Pricing" data-es="Precios">Pricing</a>
  <a href="#" onclick="showPage('order',null,true)" data-en="Order" data-es="Ordenar">Order</a>
  <a href="#" onclick="showPage('payment',null,true)" data-en="Payment" data-es="Pago">Payment</a>
  <a href="#" onclick="showPage('about',null,true)" data-en="About" data-es="Nosotros">About</a>
  <a href="#" onclick="showPage('contact',null,true)" data-en="Contact" data-es="Contacto">Contact</a>
  <a href="#" onclick="showPage('login',null,true)" data-en="Login" data-es="Acceso">Login</a>
  <a href="#" onclick="showPage('privacy',null,true)" data-en="Privacy" data-es="Privacidad">Privacy</a>
</div>

<!-- ===== HOME PAGE ===== -->
<div class="page active" id="page-home">
  <div class="hero">
    <img src="logo-black.jpeg" class="hero-logo" alt="<?php echo htmlspecialchars($businessName); ?>">
    <h1 data-en="BEE SHARP SV" data-es="BEE SHARP SV">BEE SHARP SV</h1>
    <div class="tagline" data-en="Professional Sharpening Services" data-es="Servicios Profesionales de Afilado">Professional Sharpening Services</div>
    <p data-en="Fueling the Bitcoin Circular Economy · San Salvador, Santa Tecla, La Libertad" data-es="Impulsando la Economía Circular Bitcoin · San Salvador, Santa Tecla, La Libertad">Fueling the Bitcoin Circular Economy · San Salvador, Santa Tecla, La Libertad</p>
    <div class="hero-btns">
      <button class="btn btn-primary" onclick="showPage('order')" data-en="Book Now" data-es="Reservar Ahora">Book Now</button>
      <button class="btn btn-outline" onclick="showPage('pricing')" data-en="See Pricing" data-es="Ver Precios">See Pricing</button>
      <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" class="btn btn-dark" target="_blank">WhatsApp</a>
    </div>
  </div>

  <section>
    <h2 class="section-title" data-en="What We Do" data-es="Lo Que Hacemos">What We Do</h2>
    <p class="section-sub" data-en="Residential, Restaurants & Landscaping · Pick-up/Delivery & On-site" data-es="Residencial, Restaurantes y Jardinería · Recogida/Entrega y En sitio">Residential, Restaurants & Landscaping · Pick-up/Delivery & On-site</p>
    <div class="cards">
      <div class="card">
        <div class="card-icon">🔪</div>
        <h3 data-en="Kitchen Knives" data-es="Cuchillos de Cocina">Kitchen Knives</h3>
        <p data-en="Professional sharpening for all knife types. Chefs and home cooks welcome." data-es="Afilado profesional para todo tipo de cuchillos. Cocineros y hogares bienvenidos.">Professional sharpening for all knife types. Chefs and home cooks welcome.</p>
      </div>
      <div class="card">
        <div class="card-icon">🪓</div>
        <h3 data-en="Axes & Machetes" data-es="Hachas y Machetes">Axes & Machetes</h3>
        <p data-en="Axes, hatchets, and machetes restored to razor-sharp edges." data-es="Hachas, hachetas y machetes restaurados a filo de navaja.">Axes, hatchets, and machetes restored to razor-sharp edges.</p>
      </div>
      <div class="card">
        <div class="card-icon">✂️</div>
        <h3 data-en="Garden Tools" data-es="Herramientas de Jardín">Garden Tools</h3>
        <p data-en="Shears, loppers, pruners and more for landscaping professionals." data-es="Tijeras, podadoras, cortadoras y más para profesionales del paisajismo.">Shears, loppers, pruners and more for landscaping professionals.</p>
      </div>
      <div class="card">
        <div class="card-icon">₿</div>
        <h3 data-en="Bitcoin Accepted" data-es="Se Acepta Bitcoin">Bitcoin Accepted</h3>
        <p data-en="Pay with Lightning or on-chain Bitcoin and get <?php echo htmlspecialchars($btcDiscount); ?>% off every time." data-es="Paga con Lightning o Bitcoin on-chain y obtén <?php echo htmlspecialchars($btcDiscount); ?>% de descuento siempre.">Pay with Lightning or on-chain Bitcoin and get <?php echo htmlspecialchars($btcDiscount); ?>% off every time.</p>
      </div>
      <div class="card">
        <div class="card-icon">🚗</div>
        <h3 data-en="Pick-up & Delivery" data-es="Recogida y Entrega">Pick-up & Delivery</h3>
        <p data-en="We come to you. Free delivery with 10+ items." data-es="Vamos hasta ti. Entrega gratuita con 10+ artículos.">We come to you. Free delivery with 10+ items.</p>
      </div>
      <div class="card">
        <div class="card-icon">🌾</div>
        <h3 data-en="Bitcoin Farmers Market" data-es="Mercado Agricola Bitcoin">Bitcoin Farmers Market</h3>
        <p data-en="Find us on-site at Club Cocal Bitcoin Farmers Markets." data-es="Encuéntranos en el Mercado Agrícola Bitcoin del Club Cocal.">Find us on-site at Club Cocal Bitcoin Farmers Markets.</p>
      </div>
    </div>
  </section>

  <section style="background:var(--card);border-radius:12px;padding:40px 20px;border:1px solid var(--border);max-width:1100px;margin:0 auto 40px;">
    <h2 class="section-title" data-en="Marketing Offer" data-es="Oferta de Marketing">Marketing Offer</h2>
    <p style="color:var(--text-dim);margin-bottom:16px;" data-en="Send us a before & after sharpening comparison video and receive $2 back in Bitcoin if we use it in our marketing!" data-es="¡Envíanos un video comparativo antes y después del afilado y recibe $2 de vuelta en Bitcoin si lo usamos en nuestro marketing!">Send us a before & after sharpening comparison video and receive $2 back in Bitcoin if we use it in our marketing!</p>
    <p style="color:var(--text-dim);font-size:0.85rem;" data-en="By sending images you consent to our using them in our social media." data-es="Al enviar imágenes, consientes que las usemos en nuestras redes sociales.">By sending images you consent to our using them in our social media.</p>
    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
      <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" class="btn btn-primary" target="_blank" data-en="Send via WhatsApp" data-es="Enviar por WhatsApp">Send via WhatsApp</a>
      <a href="https://instagram.com/BEESHAPE_SV" class="btn btn-outline" target="_blank" data-en="Tag on Instagram" data-es="Etiqueta en Instagram">Tag on Instagram</a>
    </div>
  </section>

  <section style="max-width:1100px;margin:0 auto 40px;padding:0 20px;">
    <h2 class="section-title" data-en="Find Us" data-es="Encuéntranos">Find Us</h2>
    <div class="map-placeholder">
      <p style="color:var(--text-dim);margin-bottom:16px;" data-en="San Salvador, Santa Tecla, La Libertad, El Salvador" data-es="San Salvador, Santa Tecla, La Libertad, El Salvador">San Salvador, Santa Tecla, La Libertad, El Salvador</p>
      <a href="https://www.google.com/maps/search/bitcoin+farmers+market+club+cocal+el+salvador" target="_blank" class="btn btn-outline" data-en="View on Google Maps" data-es="Ver en Google Maps">View on Google Maps</a>
    </div>
  </section>
</div>

<!-- ===== SERVICES PAGE ===== -->
<div class="page" id="page-services">
  <div class="page-hero">
    <h1 data-en="Our Services" data-es="Nuestros Servicios">Our Services</h1>
    <p data-en="Professional sharpening for every blade" data-es="Afilado profesional para cada hoja">Professional sharpening for every blade</p>
  </div>
  <section>
    <div class="service-item">
      <div class="service-icon">🔪</div>
      <div class="service-info">
        <h3 data-en="Kitchen Knives" data-es="Cuchillos de Cocina">Kitchen Knives</h3>
        <p data-en="All knives sharpened to professional standards. Chef's knives, paring knives, bread knives, fillet knives, and more. Residential and restaurant clients welcome." data-es="Todos los cuchillos afilados a estándares profesionales. Cuchillos de chef, de pelar, de pan, de filete y más. Clientes residenciales y de restaurantes bienvenidos.">All knives sharpened to professional standards. Chef's knives, paring knives, bread knives, fillet knives, and more. Residential and restaurant clients welcome.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">🪓</div>
      <div class="service-info">
        <h3 data-en="Axes, Hatchets & Machetes" data-es="Hachas, Hachetas y Machetes">Axes, Hatchets & Machetes</h3>
        <p data-en="Heavy-duty blades restored to optimal cutting performance. Perfect for landscapers and outdoor professionals." data-es="Hojas de uso intensivo restauradas al rendimiento de corte óptimo. Perfectas para paisajistas y profesionales al aire libre.">Heavy-duty blades restored to optimal cutting performance. Perfect for landscapers and outdoor professionals.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">✂️</div>
      <div class="service-info">
        <h3 data-en="Garden Tools, Shears, Loppers & Pruners" data-es="Herramientas de Jardín, Tijeras, Podadoras">Garden Tools, Shears, Loppers & Pruners</h3>
        <p data-en="Precision sharpening for all garden cutting tools. Keep your landscaping equipment performing at its best." data-es="Afilado de precisión para todas las herramientas de corte de jardín. Mantén tu equipo de jardinería al máximo rendimiento.">Precision sharpening for all garden cutting tools. Keep your landscaping equipment performing at its best.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">🛠️</div>
      <div class="service-info">
        <h3 data-en="Repairs & Restoration" data-es="Reparaciones y Restauración">Repairs & Restoration</h3>
        <p data-en="Damaged tips, broken blades, chips, and rust restoration. Contact us via WhatsApp with a photo for a custom quote." data-es="Puntas dañadas, hojas rotas, muescas y restauración de óxido. Contáctanos por WhatsApp con una foto para una cotización personalizada.">Damaged tips, broken blades, chips, and rust restoration. Contact us via WhatsApp with a photo for a custom quote.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">💰</div>
      <div class="service-info">
        <h3 data-en="Buy Used & Antique Knives" data-es="Compramos Cuchillos Usados y Antiguos">Buy Used & Antique Knives</h3>
        <p data-en="We buy quality used and antique knives. Contact us with photos and we'll make you an offer." data-es="Compramos cuchillos usados y antiguos de calidad. Contáctanos con fotos y te haremos una oferta.">We buy quality used and antique knives. Contact us with photos and we'll make you an offer.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">🍕</div>
      <div class="service-info">
        <h3 data-en="Pizza Cutters" data-es="Cortadores de Pizza">Pizza Cutters</h3>
        <p data-en="We love pizza! Rotary pizza cutters are always sharpened for free. No questions asked." data-es="¡Amamos la pizza! Las cortadoras de pizza rotativas siempre se afilan gratis. Sin preguntas.">We love pizza! Rotary pizza cutters are always sharpened for free. No questions asked.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">🚗</div>
      <div class="service-info">
        <h3 data-en="Pick-up & Delivery Service" data-es="Servicio de Recogida y Entrega">Pick-up & Delivery Service</h3>
        <p data-en="Delivery fee of $10 is waived with 10 items or more. We currently offer pick-up/delivery or on-site sharpening. Farmers Markets on-site sharpening available." data-es="La tarifa de entrega de $10 se exime con 10 artículos o más. Actualmente ofrecemos recogida/entrega o afilado en sitio. Afilado en sitio disponible en Mercados Agrícolas.">Delivery fee of $10 is waived with 10 items or more. We currently offer pick-up/delivery or on-site sharpening. Farmers Markets on-site sharpening available.</p>
      </div>
    </div>
    <div class="service-item">
      <div class="service-icon">🌾</div>
      <div class="service-info">
        <h3 data-en="Bitcoin Farmers Market" data-es="Mercado Agrícola Bitcoin">Bitcoin Farmers Market</h3>
        <p data-en="Find us at the Club Cocal Bitcoin Farmers Market for on-site same-day sharpening. Bring your blades, leave them sharp." data-es="Encuéntranos en el Mercado Agrícola Bitcoin del Club Cocal para afilado en sitio el mismo día. Trae tus hojas, llévalas afiladas.">Find us at the Club Cocal Bitcoin Farmers Market for on-site same-day sharpening. Bring your blades, leave them sharp.</p>
      </div>
    </div>

    <div style="margin-top:30px;text-align:center;">
      <button class="btn btn-primary" onclick="showPage('order')" data-en="Schedule a Service" data-es="Agendar un Servicio">Schedule a Service</button>
    </div>
  </section>
</div>

<!-- ===== PRICING PAGE ===== -->
<div class="page" id="page-pricing">
  <div class="page-hero">
    <h1 data-en="Pricing & Discounts" data-es="Precios y Descuentos">Pricing & Discounts</h1>
    <p data-en="Transparent pricing. Bitcoin discounts. Free pizza cutters." data-es="Precios transparentes. Descuentos en Bitcoin. Cortadores de pizza gratis.">Transparent pricing. Bitcoin discounts. Free pizza cutters.</p>
  </div>
  <section>
    <table class="pricing-table">
      <thead>
        <tr>
          <th data-en="Item" data-es="Artículo">Item</th>
          <th data-en="Price" data-es="Precio">Price</th>
          <th data-en="Notes" data-es="Notas">Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($pricing): ?>
          <?php foreach ($pricing as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['name']); ?></td>
              <td><span class="price-badge"><?php echo htmlspecialchars($item['price']); ?></span></td>
              <td><?php echo htmlspecialchars($item['notes']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td data-en="All Knives" data-es="Todos los Cuchillos">All Knives</td>
            <td><span class="price-badge">$5.00</span></td>
            <td data-en="Per knife, any style" data-es="Por cuchillo, cualquier estilo">Per knife, any style</td>
          </tr>
          <tr>
            <td data-en="Axe / Hatchet / Machete" data-es="Hacha / Hacheta / Machete">Axe / Hatchet / Machete</td>
            <td><span class="price-badge">$7.00</span></td>
            <td data-en="Per item" data-es="Por artículo">Per item</td>
          </tr>
          <tr>
            <td data-en="Garden Tools" data-es="Herramientas de Jardín">Garden Tools (Shears, Loppers, Pruners)</td>
            <td><span class="price-badge">$9.00</span></td>
            <td data-en="Per item" data-es="Por artículo">Per item</td>
          </tr>
          <tr>
            <td data-en="Pizza Cutters (Rotary)" data-es="Cortadoras de Pizza (Rotativas)">Pizza Cutters (Rotary)</td>
            <td><span class="price-badge" style="background:#00c864;color:#000;">FREE</span></td>
            <td data-en="Always free — we love pizza!" data-es="¡Siempre gratis, amamos la pizza!">Always free</td>
          </tr>
          <tr>
            <td data-en="Delivery Fee" data-es="Tarifa de Entrega">Delivery Fee</td>
            <td><span class="price-badge">$10.00</span></td>
            <td data-en="Waived with 10+ items" data-es="Exenta con 10+ artículos">Waived with 10+ items</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="discount-box">
      <h3 data-en="Available Discounts" data-es="Descuentos Disponibles">Available Discounts</h3>
      <ul>
        <li data-en="Bitcoin discount_text" data-es="bitcoin_discount_text"><?php echo htmlspecialchars($btcDiscount); ?>% discount on all sales paid with Bitcoin (Lightning or on-chain)</li>
        <li data-en="10% discount with orders of 10 items or more" data-es="10% de descuento con pedidos de 10 artículos o más">10% discount with orders of 10 items or more</li>
        <li data-en="Discounts can stack! 10 items + Bitcoin payment = 20% off!" data-es="¡Los descuentos se acumulan! 10 artículos + pago Bitcoin = ¡20% de descuento!">Discounts can stack! 10 items + Bitcoin payment = 20% off!</li>
      </ul>
    </div>

    <div class="free-badge">
      <span data-en="BONUS: Pizza cutters are ALWAYS sharpened for FREE! We love pizza." data-es="BONUS: ¡Las cortadoras de pizza SIEMPRE se afilan GRATIS! Amamos la pizza.">BONUS: Pizza cutters are ALWAYS sharpened for FREE! We love pizza.</span>
    </div>

    <div style="margin-top:30px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
      <button class="btn btn-primary" onclick="showPage('order')" data-en="Place an Order" data-es="Hacer un Pedido">Place an Order</button>
      <button class="btn btn-outline" onclick="showPage('payment')" data-en="Payment Options" data-es="Opciones de Pago">Payment Options</button>
    </div>
  </section>
</div>

<!-- ===== ORDER PAGE ===== -->
<div class="page" id="page-order">
  <div class="page-hero">
    <h1 data-en="Order & Schedule" data-es="Pedir y Agendar">Order & Schedule</h1>
    <p data-en="Pick-up, delivery, or on-site — your choice" data-es="Recogida, entrega o en sitio — tu elección">Pick-up, delivery, or on-site — your choice</p>
  </div>
  <section>
    <div class="tab-nav">
      <button class="tab-btn active" onclick="switchTab('order-tab','new-order')" data-en="New Order" data-es="Nuevo Pedido">New Order</button>
      <button class="tab-btn" onclick="switchTab('order-tab','my-orders')" data-en="My Orders" data-es="Mis Pedidos">My Orders</button>
      <button class="tab-btn" onclick="switchTab('order-tab','schedule')" data-en="Schedule" data-es="Agendar">Schedule</button>
    </div>

    <!-- NEW ORDER TAB -->
    <div class="tab-content active" id="order-tab-new-order">
      <div class="form-row">
        <div class="form-group">
          <label data-en="Full Name" data-es="Nombre Completo">Full Name</label>
          <input type="text" id="o-name" placeholder="John Doe">
        </div>
        <div class="form-group">
          <label data-en="WhatsApp / Phone" data-es="WhatsApp / Teléfono">WhatsApp / Phone</label>
          <input type="tel" id="o-phone" placeholder="+503 7952-2492">
        </div>
      </div>
      <div class="form-group">
        <label data-en="Service Type" data-es="Tipo de Servicio">Service Type</label>
        <select id="o-service">
          <option value="pickup" data-en="Pick-up & Delivery" data-es="Recogida y Entrega">Pick-up & Delivery</option>
          <option value="onsite" data-en="On-site Sharpening" data-es="Afilado en Sitio">On-site Sharpening</option>
          <option value="market" data-en="Bitcoin Farmers Market (Club Cocal)" data-es="Mercado Agrícola Bitcoin (Club Cocal)">Bitcoin Farmers Market (Club Cocal)</option>
        </select>
      </div>
      <div class="form-group">
        <label data-en="Address / Location" data-es="Dirección / Ubicación">Address / Location</label>
        <input type="text" id="o-address" placeholder="San Salvador, Santa Tecla...">
      </div>

      <h3 style="color:var(--orange);margin-bottom:14px;font-size:1.1rem;" data-en="Items to Sharpen" data-es="Artículos para Afilar">Items to Sharpen</h3>
      <div id="order-items">
        <div class="order-item-row">
          <select onchange="calcTotal()">
            <option value="5" data-en="Knife ($5)" data-es="Cuchillo ($5)">Knife ($5)</option>
            <option value="7" data-en="Axe/Machete ($7)" data-es="Hacha/Machete ($7)">Axe/Machete ($7)</option>
            <option value="9" data-en="Garden Tool ($9)" data-es="Herramienta de Jardín ($9)">Garden Tool ($9)</option>
            <option value="0" data-en="Pizza Cutter (FREE)" data-es="Cortadora de Pizza (GRATIS)">Pizza Cutter (FREE)</option>
            <option value="0" data-en="Repair (Quote)" data-es="Reparación (Cotización)">Repair (Quote)</option>
          </select>
          <input type="number" value="1" min="1" max="99" style="max-width:70px;" onchange="calcTotal()">
          <button class="remove-btn" onclick="removeItem(this)">✕</button>
        </div>
      </div>
      <button id="add-item-btn" onclick="addItem()" data-en="+ Add Another Item" data-es="+ Agregar Otro Artículo">+ Add Another Item</button>

      <div class="order-summary" id="order-summary">
        <h4 data-en="Order Summary" data-es="Resumen del Pedido">Order Summary</h4>
        <div class="summary-row"><span data-en="Subtotal" data-es="Subtotal">Subtotal</span><span id="s-sub">$0.00</span></div>
        <div class="summary-row"><span data-en="Delivery Fee" data-es="Tarifa de Entrega">Delivery Fee</span><span id="s-del">$0.00</span></div>
        <div class="summary-row"><span data-en="Discount" data-es="Descuento">Discount</span><span id="s-disc" style="color:#00c864;">-$0.00</span></div>
        <div class="summary-total"><span data-en="TOTAL" data-es="TOTAL">TOTAL</span><span id="s-total">$0.00</span></div>
      </div>

      <div class="form-group">
        <label data-en="Payment Method" data-es="Método de Pago">Payment Method</label>
        <select id="o-payment" onchange="calcTotal()">
          <option value="cash" data-en="Cash" data-es="Efectivo">Cash</option>
          <option value="bitcoin" data-en="Bitcoin (Lightning or On-Chain) — Discount!" data-es="Bitcoin (Lightning u On-Chain) — ¡Descuento!">Bitcoin (Lightning or On-Chain) — Discount!</option>
        </select>
      </div>
      <div class="form-group">
        <label data-en="Preferred Date" data-es="Fecha Preferida">Preferred Date</label>
        <input type="date" id="o-date">
      </div>
      <div class="form-group">
        <label data-en="Notes" data-es="Notas">Notes</label>
        <textarea id="o-notes" placeholder="Any special instructions, damaged tips, etc."></textarea>
      </div>
      <div class="form-group checkbox-group">
        <input type="checkbox" id="o-consent">
        <label for="o-consent" style="color:var(--text-dim);font-size:0.9rem;" data-en="I consent to before/after photos being used in social media marketing" data-es="Consiento el uso de fotos antes/después en marketing de redes sociales">I consent to before/after photos being used in social media marketing</label>
      </div>
      <button class="btn btn-primary" style="width:100%;margin-top:8px;" onclick="submitOrder()" data-en="Submit Order via WhatsApp" data-es="Enviar Pedido por WhatsApp">Submit Order via WhatsApp</button>
    </div>

    <!-- MY ORDERS TAB -->
    <div class="tab-content" id="order-tab-my-orders">
      <div class="alert alert-info" data-en="Please log in to view your order history." data-es="Por favor inicia sesión para ver tu historial de pedidos.">Please log in to view your order history.</div>
      <button class="btn btn-outline" onclick="showPage('login')" data-en="Log In" data-es="Iniciar Sesión">Log In</button>
    </div>

    <!-- SCHEDULE TAB -->
    <div class="tab-content" id="order-tab-schedule">
      <div class="form-group">
        <label data-en="Select a Date" data-es="Selecciona una Fecha">Select a Date</label>
        <input type="date" id="sched-date" onchange="updateSlots()">
      </div>
      <h4 style="color:var(--orange);margin-bottom:8px;" data-en="Available Time Slots" data-es="Horarios Disponibles">Available Time Slots</h4>
      <div class="time-slots" id="time-slots">
        <div class="time-slot" onclick="selectSlot(this)">9:00 AM</div>
        <div class="time-slot" onclick="selectSlot(this)">10:00 AM</div>
        <div class="time-slot" onclick="selectSlot(this)">11:00 AM</div>
        <div class="time-slot unavailable">12:00 PM</div>
        <div class="time-slot" onclick="selectSlot(this)">1:00 PM</div>
        <div class="time-slot" onclick="selectSlot(this)">2:00 PM</div>
        <div class="time-slot" onclick="selectSlot(this)">3:00 PM</div>
        <div class="time-slot unavailable">4:00 PM</div>
        <div class="time-slot" onclick="selectSlot(this)">5:00 PM</div>
      </div>
      <div style="margin-top:20px;">
        <button class="btn btn-primary" onclick="bookSlot()" data-en="Book Selected Slot" data-es="Reservar Horario Seleccionado">Book Selected Slot</button>
      </div>
    </div>
  </section>
</div>

<!-- ===== PAYMENT PAGE ===== -->
<div class="page" id="page-payment">
  <div class="page-hero">
    <h1 data-en="Payment Options" data-es="Opciones de Pago">Payment Options</h1>
    <p data-en="Cash or Bitcoin — your choice, your discount" data-es="Efectivo o Bitcoin — tu elección, tu descuento">Cash or Bitcoin — your choice, your discount</p>
  </div>
  <section>
    <div class="payment-options">
      <div class="payment-option selected" id="pay-cash" onclick="selectPayment('cash')">
        <div class="pay-icon">💵</div>
        <h3 data-en="Cash" data-es="Efectivo">Cash</h3>
        <p data-en="Pay on delivery or at the market. No fees." data-es="Paga en la entrega o en el mercado. Sin comisiones.">Pay on delivery or at the market. No fees.</p>
      </div>
      <div class="payment-option" id="pay-bitcoin" onclick="selectPayment('bitcoin')">
        <div class="pay-icon">₿</div>
        <h3>Bitcoin</h3>
        <p data-en="Lightning Network or on-chain. Discount automatically applied." data-es="Red Lightning u on-chain. Descuento aplicado automáticamente.">Lightning Network or on-chain. Discount automatically applied.</p>
      </div>
    </div>

    <div id="cash-info">
      <div class="bitcoin-box" style="border-color:var(--border);">
        <h3 data-en="Cash Payment" data-es="Pago en Efectivo">Cash Payment</h3>
        <p style="color:var(--text-dim);" data-en="Pay cash on delivery or at the Bitcoin Farmers Market. Our team will collect payment when your items are returned." data-es="Paga en efectivo en la entrega o en el Mercado Agrícola Bitcoin. Nuestro equipo cobrará cuando sus artículos sean devueltos.">Pay cash on delivery or at the Bitcoin Farmers Market. Our team will collect payment when your items are returned.</p>
        <ul style="color:var(--text-dim);padding-left:20px;margin-top:12px;">
          <li data-en="Exact change appreciated" data-es="Se agradece el cambio exacto">Exact change appreciated</li>
          <li data-en="El Salvador USD accepted" data-es="Se aceptan USD de El Salvador">El Salvador USD accepted</li>
          <li data-en="Delivery fee: $10 (waived with 10+ items)" data-es="Tarifa de entrega: $10 (exenta con 10+ artículos)">Delivery fee: $10 (waived with 10+ items)</li>
        </ul>
      </div>
    </div>

    <div id="bitcoin-info" style="display:none;">
      <div class="bitcoin-discount" data-en="Bitcoin payment = discount on your entire order!" data-es="¡Pago en Bitcoin = descuento en todo tu pedido!">Bitcoin payment = discount on your entire order!</div>
      <div class="bitcoin-box">
        <h3 data-en="Lightning Network (Recommended)" data-es="Red Lightning (Recomendada)">Lightning Network (Recommended)</h3>
        <p style="color:var(--text-dim);margin-bottom:12px;" data-en="Fast, cheap, and instant Bitcoin payments. Perfect for small amounts." data-es="Pagos Bitcoin rápidos, baratos e instantáneos. Perfecto para pequeñas cantidades.">Fast, cheap, and instant Bitcoin payments. Perfect for small amounts.</p>
        <div class="qr-placeholder" data-en="Lightning QR code generated at checkout" data-es="Código QR Lightning generado en caja">Lightning QR code generated at checkout</div>
        <p style="color:var(--text-dim);font-size:0.85rem;text-align:center;" data-en="Send your order first via WhatsApp and we'll send you a Lightning invoice." data-es="Envía tu pedido primero por WhatsApp y te enviaremos una factura Lightning.">Send your order first via WhatsApp and we'll send you a Lightning invoice.</p>
      </div>
      <div class="bitcoin-box">
        <h3 data-en="On-Chain Bitcoin" data-es="Bitcoin On-Chain">On-Chain Bitcoin</h3>
        <p style="color:var(--text-dim);margin-bottom:12px;" data-en="Traditional Bitcoin transaction. Contact us via WhatsApp for our receiving address." data-es="Transacción Bitcoin tradicional. Contáctanos por WhatsApp para nuestra dirección de recepción.">Traditional Bitcoin transaction. Contact us via WhatsApp for our receiving address.</p>
        <div class="bitcoin-addr" data-en="Contact us via WhatsApp for Bitcoin address" data-es="Contáctanos por WhatsApp para dirección Bitcoin">Contact us via WhatsApp for Bitcoin address</div>
      </div>
      <div class="bitcoin-box">
        <h3 data-en="Bitcoin & El Salvador" data-es="Bitcoin y El Salvador">Bitcoin & El Salvador</h3>
        <p style="color:var(--text-dim);" data-en="El Salvador was the first country to adopt Bitcoin as legal tender. Bee Sharp SV is proud to fuel the Bitcoin circular economy — spend locally, support local business, keep sats circulating." data-es="El Salvador fue el primer país en adoptar Bitcoin como moneda de curso legal. Bee Sharp SV se enorgullece de impulsar la economía circular Bitcoin — gasta localmente, apoya el negocio local, mantén los sats circulando.">El Salvador was the first country to adopt Bitcoin as legal tender. Bee Sharp SV is proud to fuel the Bitcoin circular economy — spend locally, support local business, keep sats circulating.</p>
        <img src="bitcoin-logo.png" alt="Bitcoin" style="height:60px;margin-top:16px;display:block;" onerror="this.style.display='none'">
      </div>
    </div>

    <div style="margin-top:20px;text-align:center;">
      <button class="btn btn-primary" onclick="showPage('order')" data-en="Place an Order" data-es="Hacer un Pedido">Place an Order</button>
    </div>
  </section>
</div>

<!-- ===== ABOUT PAGE ===== -->
<div class="page" id="page-about">
  <div class="page-hero">
    <h1 data-en="About Us" data-es="Acerca de Nosotros">About Us</h1>
    <p data-en="The Bitcoin sharpening people of El Salvador" data-es="El equipo de afilado Bitcoin de El Salvador">The Bitcoin sharpening people of El Salvador</p>
  </div>
  <section>
    <div class="about-grid">
      <div>
        <img src="logo-black.jpeg" class="about-img" alt="<?php echo htmlspecialchars($businessName); ?>">
      </div>
      <div class="about-text">
        <h2><?php echo htmlspecialchars($businessName); ?></h2>
        <p data-en="We are professional sharpeners serving residential, restaurant, and landscaping clients across San Salvador, Santa Tecla, and La Libertad, El Salvador." data-es="Somos afiladores profesionales que servimos a clientes residenciales, de restaurantes y paisajismo en San Salvador, Santa Tecla y La Libertad, El Salvador.">We are professional sharpeners serving residential, restaurant, and landscaping clients across San Salvador, Santa Tecla, and La Libertad, El Salvador.</p>
        <p data-en="We proudly accept Bitcoin — both Lightning Network and on-chain — as part of our commitment to fueling El Salvador's Bitcoin circular economy. Every satoshi spent with us stays local." data-es="Aceptamos con orgullo Bitcoin, tanto en Lightning Network como on-chain, como parte de nuestro compromiso con impulsar la economía circular Bitcoin de El Salvador. Cada satoshi gastado con nosotros se queda local.">We proudly accept Bitcoin — both Lightning Network and on-chain — as part of our commitment to fueling El Salvador's Bitcoin circular economy. Every satoshi spent with us stays local.</p>
        <p data-en="Find us at the Club Cocal Bitcoin Farmers Market for on-site sharpening, or schedule a pick-up and delivery anywhere in our service area." data-es="Encuéntranos en el Mercado Agrícola Bitcoin del Club Cocal para afilado en sitio, o programa una recogida y entrega en cualquier lugar de nuestra área de servicio.">Find us at the Club Cocal Bitcoin Farmers Market for on-site sharpening, or schedule a pick-up and delivery anywhere in our service area.</p>
        <div class="social-links">
          <a href="https://instagram.com/BEESHAPE_SV" class="social-btn" target="_blank">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            @BEESHAPE_SV
          </a>
          <a href="https://www.facebook.com/BeeSharpSV/" class="social-btn" target="_blank">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            BeeSharpSV
          </a>
          <a href="https://t.me/BEE_SHARP" class="social-btn" target="_blank">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            @BEE_SHARP
          </a>
          <a href="https://primal.net/BEESHARP" class="social-btn" target="_blank">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 6.628 5.374 12 12 12 6.628 0 12-5.372 12-12 0-6.627-5.372-12-12-12zm0 2c5.514 0 10 4.486 10 10s-4.486 10-10 10S2 17.514 2 12 6.486 2 12 2zm0 3a7 7 0 1 0 0 14A7 7 0 0 0 12 5z"/></svg>
            @BEESHARP (Nostr/Primal)
          </a>
          <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" class="social-btn" target="_blank">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
            WhatsApp
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ===== CONTACT PAGE ===== -->
<div class="page" id="page-contact">
  <div class="page-hero">
    <h1 data-en="Contact Us" data-es="Contáctanos">Contact Us</h1>
    <p data-en="Get in touch with our team" data-es="Ponte en contacto con nuestro equipo">Get in touch with our team</p>
  </div>
  <section>
    <div class="contact-form">
      <h3 data-en="Send us a Message" data-es="Envíanos un Mensaje">Send us a Message</h3>
      <div id="contact-status" class="contact-status"></div>
      <form id="contact-form-element" onsubmit="submitContactForm(event)">
        <div class="form-row">
          <div class="form-group">
            <label data-en="Full Name" data-es="Nombre Completo">Full Name</label>
            <input type="text" name="name" required placeholder="John Doe">
          </div>
          <div class="form-group">
            <label data-en="Email" data-es="Email">Email</label>
            <input type="email" name="email" required placeholder="email@example.com">
          </div>
        </div>
        <div class="form-group">
          <label data-en="Phone (Optional)" data-es="Teléfono (Opcional)">Phone (Optional)</label>
          <input type="tel" name="phone" placeholder="+503 XXXX-XXXX">
        </div>
        <div class="form-group">
          <label data-en="Subject" data-es="Asunto">Subject</label>
          <input type="text" name="subject" required placeholder="How can we help?">
        </div>
        <div class="form-group">
          <label data-en="Message" data-es="Mensaje">Message</label>
          <textarea name="message" required placeholder="Tell us more..."></textarea>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <button type="submit" class="btn btn-primary" style="width:100%;" data-en="Send Message" data-es="Enviar Mensaje">Send Message</button>
      </form>
    </div>

    <div style="margin-top:40px;padding:40px;background:var(--card);border:1px solid var(--border);border-radius:10px;text-align:center;">
      <h3 style="color:var(--orange);margin-bottom:20px;" data-en="Other Ways to Reach Us" data-es="Otras Formas de Contactarnos">Other Ways to Reach Us</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-top:20px;">
        <div>
          <p style="color:var(--orange);font-weight:700;margin-bottom:8px;">WhatsApp</p>
          <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" class="btn btn-outline" target="_blank"><?php echo htmlspecialchars($whatsapp); ?></a>
        </div>
        <div>
          <p style="color:var(--orange);font-weight:700;margin-bottom:8px;" data-en="Email" data-es="Email">Email</p>
          <a href="mailto:bee-sharpSV@proton.me" class="btn btn-outline">bee-sharpSV@proton.me</a>
        </div>
        <div>
          <p style="color:var(--orange);font-weight:700;margin-bottom:8px;" data-en="Location" data-es="Ubicación">Location</p>
          <p style="color:var(--text-dim);font-size:0.9rem;padding:0 12px;">San Salvador, Santa Tecla, La Libertad</p>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ===== LOGIN PAGE ===== -->
<div class="page" id="page-login">
  <div class="page-hero">
    <h1 data-en="Customer Account" data-es="Cuenta de Cliente">Customer Account</h1>
    <p data-en="Track your orders and manage your profile" data-es="Rastrea tus pedidos y administra tu perfil">Track your orders and manage your profile</p>
  </div>
  <section>
    <div class="auth-container">
      <h2 data-en="Welcome Back" data-es="Bienvenido de Nuevo">Welcome Back</h2>
      <p data-en="Sign in to view your orders and history" data-es="Inicia sesión para ver tus pedidos e historial">Sign in to view your orders and history</p>
      <div class="auth-tabs">
        <div class="auth-tab active" onclick="switchAuthTab('login-form')" data-en="Sign In" data-es="Iniciar Sesión">Sign In</div>
        <div class="auth-tab" onclick="switchAuthTab('register-form')" data-en="Register" data-es="Registrarse">Register</div>
      </div>

      <div class="auth-form active" id="login-form">
        <div class="form-group">
          <label data-en="Email or WhatsApp" data-es="Email o WhatsApp">Email or WhatsApp</label>
          <input type="text" placeholder="email@example.com or +503...">
        </div>
        <div class="form-group">
          <label data-en="Password" data-es="Contraseña">Password</label>
          <input type="password" placeholder="••••••••">
        </div>
        <div class="forgot-link"><a href="#" data-en="Forgot password?" data-es="¿Olvidaste tu contraseña?">Forgot password?</a></div>
        <button class="btn btn-primary" style="width:100%;" onclick="demoLogin()" data-en="Sign In" data-es="Iniciar Sesión">Sign In</button>
        <div style="text-align:center;margin-top:16px;color:var(--text-dim);font-size:0.9rem;">
          <span data-en="Or contact us via" data-es="O contáctanos via">Or contact us via</span>
          <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" target="_blank"> WhatsApp</a>
        </div>
      </div>

      <div class="auth-form" id="register-form">
        <div class="form-row">
          <div class="form-group">
            <label data-en="First Name" data-es="Nombre">First Name</label>
            <input type="text">
          </div>
          <div class="form-group">
            <label data-en="Last Name" data-es="Apellido">Last Name</label>
            <input type="text">
          </div>
        </div>
        <div class="form-group">
          <label data-en="Email" data-es="Email">Email</label>
          <input type="email">
        </div>
        <div class="form-group">
          <label data-en="WhatsApp Number" data-es="Número de WhatsApp">WhatsApp Number</label>
          <input type="tel" placeholder="+503...">
        </div>
        <div class="form-group">
          <label data-en="Password" data-es="Contraseña">Password</label>
          <input type="password">
        </div>
        <div class="form-group">
          <label data-en="Address" data-es="Dirección">Address</label>
          <input type="text">
        </div>
        <button class="btn btn-primary" style="width:100%;" onclick="showToast('Account created! We will confirm via WhatsApp.')" data-en="Create Account" data-es="Crear Cuenta">Create Account</button>
      </div>
    </div>

    <!-- LOGGED IN DEMO STATE -->
    <div id="customer-dashboard" style="display:none;max-width:700px;margin:0 auto;">
      <div class="alert alert-success" data-en="Signed in as Demo Customer" data-es="Iniciado como Cliente Demo">Signed in as Demo Customer</div>
      <div class="stats-row">
        <div class="stat-card"><div class="stat-num">3</div><div class="stat-label" data-en="Total Orders" data-es="Pedidos Totales">Total Orders</div></div>
        <div class="stat-card"><div class="stat-num">12</div><div class="stat-label" data-en="Items Sharpened" data-es="Artículos Afilados">Items Sharpened</div></div>
        <div class="stat-card"><div class="stat-num">₿</div><div class="stat-label" data-en="Bitcoin Payer" data-es="Paga con Bitcoin">Bitcoin Payer</div></div>
      </div>
      <h3 style="color:var(--orange);margin-bottom:16px;" data-en="Your Orders" data-es="Tus Pedidos">Your Orders</h3>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>#</th><th data-en="Date" data-es="Fecha">Date</th><th data-en="Items" data-es="Artículos">Items</th><th data-en="Total" data-es="Total">Total</th><th data-en="Status" data-es="Estado">Status</th></tr></thead>
          <tbody>
            <tr><td>#1003</td><td>Apr 10, 2026</td><td>4 knives</td><td>$18.00</td><td><span class="badge badge-green" data-en="Delivered" data-es="Entregado">Delivered</span></td></tr>
            <tr><td>#1007</td><td>Apr 12, 2026</td><td>2 knives, 1 axe</td><td>$16.20</td><td><span class="badge badge-orange" data-en="In Progress" data-es="En Proceso">In Progress</span></td></tr>
            <tr><td>#1011</td><td>Apr 14, 2026</td><td>3 garden tools</td><td>$27.00</td><td><span class="badge badge-blue" data-en="Scheduled" data-es="Programado">Scheduled</span></td></tr>
          </tbody>
        </table>
      </div>
      <button class="btn btn-outline" onclick="logOut()" style="margin-top:12px;" data-en="Sign Out" data-es="Cerrar Sesión">Sign Out</button>
    </div>
  </section>
</div>

<!-- ===== PRIVACY PAGE ===== -->
<div class="page" id="page-privacy">
  <div class="page-hero">
    <h1 data-en="Privacy Policy" data-es="Política de Privacidad">Privacy Policy</h1>
    <p data-en="Last updated: April 2026" data-es="Última actualización: Abril 2026">Last updated: April 2026</p>
  </div>
  <section>
    <div class="policy-content">
      <h3 data-en="1. Information We Collect" data-es="1. Información que Recopilamos">1. Information We Collect</h3>
      <p data-en="We collect information you provide when placing orders: name, WhatsApp/phone number, email address, and delivery address. We do not collect payment card data." data-es="Recopilamos la información que proporcionas al realizar pedidos: nombre, número de WhatsApp/teléfono, correo electrónico y dirección de entrega. No recopilamos datos de tarjetas de pago.">We collect information you provide when placing orders: name, WhatsApp/phone number, email address, and delivery address. We do not collect payment card data.</p>

      <h3 data-en="2. How We Use Your Information" data-es="2. Cómo Usamos tu Información">2. How We Use Your Information</h3>
      <ul>
        <li data-en="To process and fulfill your sharpening orders" data-es="Para procesar y completar tus pedidos de afilado">To process and fulfill your sharpening orders</li>
        <li data-en="To communicate with you about order status via WhatsApp" data-es="Para comunicarnos contigo sobre el estado del pedido por WhatsApp">To communicate with you about order status via WhatsApp</li>
        <li data-en="To send delivery notifications" data-es="Para enviar notificaciones de entrega">To send delivery notifications</li>
        <li data-en="To improve our services" data-es="Para mejorar nuestros servicios">To improve our services</li>
      </ul>

      <h3 data-en="3. Photos & Videos" data-es="3. Fotos y Videos">3. Photos & Videos</h3>
      <p data-en="If you voluntarily send us before/after sharpening photos or videos via WhatsApp or social media, you consent to our use of this content in our marketing materials and social media accounts. You will receive $2 in Bitcoin if your content is used." data-es="Si voluntariamente nos envías fotos o videos antes/después del afilado por WhatsApp o redes sociales, consientes el uso de este contenido en nuestros materiales de marketing y cuentas de redes sociales. Recibirás $2 en Bitcoin si tu contenido es utilizado.">If you voluntarily send us before/after sharpening photos or videos via WhatsApp or social media, you consent to our use of this content in our marketing materials and social media accounts. You will receive $2 in Bitcoin if your content is used.</p>

      <h3 data-en="4. Bitcoin Payments" data-es="4. Pagos en Bitcoin">4. Bitcoin Payments</h3>
      <p data-en="Bitcoin transactions are pseudonymous and recorded on the public blockchain. We do not store your Bitcoin wallet addresses beyond what is necessary to process payment." data-es="Las transacciones de Bitcoin son seudónimas y se registran en la blockchain pública. No almacenamos tus direcciones de cartera Bitcoin más allá de lo necesario para procesar el pago.">Bitcoin transactions are pseudonymous and recorded on the public blockchain. We do not store your Bitcoin wallet addresses beyond what is necessary to process payment.</p>

      <h3 data-en="5. Data Sharing" data-es="5. Compartir Datos">5. Data Sharing</h3>
      <p data-en="We do not sell or share your personal data with third parties. Your information is used solely for order fulfillment and customer communication." data-es="No vendemos ni compartimos tus datos personales con terceros. Tu información se utiliza únicamente para el cumplimiento de pedidos y la comunicación con clientes.">We do not sell or share your personal data with third parties. Your information is used solely for order fulfillment and customer communication.</p>

      <h3 data-en="6. Data Security" data-es="6. Seguridad de Datos">6. Data Security</h3>
      <p data-en="We use WhatsApp (end-to-end encrypted) and ProtonMail (end-to-end encrypted) for communications. We take reasonable steps to protect your personal information." data-es="Usamos WhatsApp (cifrado de extremo a extremo) y ProtonMail (cifrado de extremo a extremo) para comunicaciones. Tomamos medidas razonables para proteger tu información personal.">We use WhatsApp (end-to-end encrypted) and ProtonMail (end-to-end encrypted) for communications. We take reasonable steps to protect your personal information.</p>

      <h3 data-en="7. Your Rights" data-es="7. Tus Derechos">7. Your Rights</h3>
      <p data-en="You may request deletion or correction of your personal data at any time by contacting us via WhatsApp or email." data-es="Puedes solicitar la eliminación o corrección de tus datos personales en cualquier momento contactándonos por WhatsApp o correo electrónico.">You may request deletion or correction of your personal data at any time by contacting us via WhatsApp or email.</p>

      <h3 data-en="8. Contact" data-es="8. Contacto">8. Contact</h3>
      <p>WhatsApp: <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>"><?php echo htmlspecialchars($whatsapp); ?></a> | Email: <a href="mailto:bee-sharpSV@proton.me">bee-sharpSV@proton.me</a></p>

      <div class="divider"></div>
      <p style="font-size:0.85rem;color:var(--text-dim);" data-en="© 2026 Bee Sharp SV. All rights reserved." data-es="© 2026 Bee Sharp SV. Todos los derechos reservados.">© 2026 Bee Sharp SV. All rights reserved.</p>
    </div>
  </section>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div class="footer-col">
      <h4><?php echo htmlspecialchars($businessName); ?></h4>
      <p data-en="Professional Sharpening Services" data-es="Servicios Profesionales de Afilado">Professional Sharpening Services</p>
      <p data-en="Fueling the Bitcoin Circular Economy" data-es="Impulsando la Economía Circular Bitcoin">Fueling the Bitcoin Circular Economy</p>
      <p data-en="San Salvador · Santa Tecla · La Libertad" data-es="San Salvador · Santa Tecla · La Libertad">San Salvador · Santa Tecla · La Libertad</p>
    </div>
    <div class="footer-col">
      <h4 data-en="Quick Links" data-es="Enlaces Rápidos">Quick Links</h4>
      <a href="#" onclick="showPage('services')" data-en="Services" data-es="Servicios">Services</a>
      <a href="#" onclick="showPage('pricing')" data-en="Pricing" data-es="Precios">Pricing</a>
      <a href="#" onclick="showPage('order')" data-en="Order Now" data-es="Pedir Ahora">Order Now</a>
      <a href="#" onclick="showPage('payment')" data-en="Payment Options" data-es="Opciones de Pago">Payment Options</a>
      <a href="#" onclick="showPage('contact')" data-en="Contact" data-es="Contacto">Contact</a>
      <a href="#" onclick="showPage('privacy')" data-en="Privacy Policy" data-es="Política de Privacidad">Privacy Policy</a>
      <a href="admin.html" target="_blank" data-en="Admin Panel" data-es="Panel Admin">Admin Panel</a>
    </div>
    <div class="footer-col">
      <h4 data-en="Contact" data-es="Contacto">Contact</h4>
      <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" target="_blank">WhatsApp: <?php echo htmlspecialchars($whatsapp); ?></a>
      <a href="mailto:bee-sharpSV@proton.me">bee-sharpSV@proton.me</a>
    </div>
    <div class="footer-col">
      <h4 data-en="Follow Us" data-es="Síguenos">Follow Us</h4>
      <a href="https://instagram.com/BEESHAPE_SV" target="_blank">Instagram: @BEESHAPE_SV</a>
      <a href="https://www.facebook.com/BeeSharpSV/" target="_blank">Facebook: BeeSharpSV</a>
      <a href="https://t.me/BEE_SHARP" target="_blank">Telegram: @BEE_SHARP</a>
      <a href="https://primal.net/BEESHARP" target="_blank">Nostr/Primal: @BEESHARP</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p data-en="© 2026 Bee Sharp SV. All rights reserved. | Professional Sharpening Services · El Salvador" data-es="© 2026 Bee Sharp SV. Todos los derechos reservados. | Servicios Profesionales de Afilado · El Salvador">© 2026 Bee Sharp SV. All rights reserved. | Professional Sharpening Services · El Salvador</p>
  </div>
</footer>

<!-- WHATSAPP FLOAT BUTTON -->
<a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', htmlspecialchars($whatsapp)); ?>" class="wa-float" target="_blank" title="WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
</a>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ========== SESSION STATE (injected by PHP) ==========
const SESSION = <?php echo $sessionData; ?>;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ========== LANGUAGE ==========
let lang = 'en';
function toggleLang() {
  lang = lang === 'en' ? 'es' : 'en';
  document.getElementById('langBtn').textContent = lang === 'en' ? 'ES' : 'EN';
  document.querySelectorAll('[data-en]').forEach(el => {
    if (el.tagName === 'INPUT' && el.placeholder) {
      el.placeholder = lang === 'es' ? (el.getAttribute('data-es-placeholder') || el.placeholder) : (el.getAttribute('data-en-placeholder') || el.placeholder);
    } else {
      el.textContent = lang === 'es' ? el.getAttribute('data-es') : el.getAttribute('data-en');
    }
  });
}

// ========== NAVIGATION ==========
function showPage(id, linkEl, closeMenu) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-' + id).classList.add('active');
  document.querySelectorAll('.nav-links a').forEach(a => a.classList.remove('active'));
  if (linkEl) linkEl.classList.add('active');
  window.scrollTo(0, 0);
  if (closeMenu) toggleMenu(true);
  return false;
}

function toggleMenu(forceClose) {
  const menu = document.getElementById('mobileMenu');
  if (forceClose) { menu.classList.remove('open'); return; }
  menu.classList.toggle('open');
}

// ========== TABS ==========
function switchTab(groupId, tabId) {
  const group = document.getElementById('order-tab-new-order').parentElement;
  group.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  group.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
  document.getElementById(groupId + '-' + tabId).classList.add('active');
  event.target.classList.add('active');
}

function switchAuthTab(formId) {
  document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.getElementById(formId).classList.add('active');
  event.target.classList.add('active');
}

// ========== ORDER CALCULATOR ==========
const prices = {'5':5,'7':7,'9':9,'0':0};
function addItem() {
  const container = document.getElementById('order-items');
  const row = document.createElement('div');
  row.className = 'order-item-row';
  row.innerHTML = `<select onchange="calcTotal()">
    <option value="5">Knife ($5)</option>
    <option value="7">Axe/Machete ($7)</option>
    <option value="9">Garden Tool ($9)</option>
    <option value="0">Pizza Cutter (FREE)</option>
    <option value="0">Repair (Quote)</option>
  </select>
  <input type="number" value="1" min="1" max="99" style="max-width:70px;" onchange="calcTotal()">
  <button class="remove-btn" onclick="removeItem(this)">✕</button>`;
  container.appendChild(row);
  calcTotal();
}
function removeItem(btn) {
  btn.parentElement.remove();
  calcTotal();
}
function calcTotal() {
  const rows = document.querySelectorAll('.order-item-row');
  let subtotal = 0, totalItems = 0;
  rows.forEach(row => {
    const price = parseFloat(row.querySelector('select').value) || 0;
    const qty = parseInt(row.querySelector('input').value) || 1;
    subtotal += price * qty;
    totalItems += qty;
  });
  const service = document.getElementById('o-service')?.value;
  const payment = document.getElementById('o-payment')?.value;
  let delivery = service === 'pickup' && totalItems < 10 ? 10 : 0;
  let discount = 0;
  if (totalItems >= 10) discount += subtotal * 0.1;
  if (payment === 'bitcoin') discount += subtotal * 0.1;
  const total = subtotal + delivery - discount;
  document.getElementById('s-sub').textContent = '$' + subtotal.toFixed(2);
  document.getElementById('s-del').textContent = '$' + delivery.toFixed(2);
  document.getElementById('s-disc').textContent = '-$' + discount.toFixed(2);
  document.getElementById('s-total').textContent = '$' + total.toFixed(2);
}

function submitOrder() {
  const name = document.getElementById('o-name').value || 'Customer';
  const phone = document.getElementById('o-phone').value;
  const service = document.getElementById('o-service').value;
  const address = document.getElementById('o-address').value;
  const payment = document.getElementById('o-payment').value;
  const date = document.getElementById('o-date').value;
  const notes = document.getElementById('o-notes').value;
  const total = document.getElementById('s-total').textContent;
  let items = [];
  document.querySelectorAll('.order-item-row').forEach(row => {
    const type = row.querySelector('select').options[row.querySelector('select').selectedIndex].text;
    const qty = row.querySelector('input').value;
    items.push(qty + 'x ' + type);
  });
  const msg = `BEE SHARP SV ORDER\n\nName: ${name}\nPhone: ${phone}\nService: ${service}\nAddress: ${address}\nDate: ${date}\nPayment: ${payment}\n\nItems:\n${items.join('\n')}\n\nTotal: ${total}\n\nNotes: ${notes}`;
  window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
  showToast('Order sent via WhatsApp!');
}

// ========== SCHEDULE ==========
let selectedSlot = null;
function selectSlot(el) {
  if (el.classList.contains('unavailable')) return;
  document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  selectedSlot = el.textContent;
}
function bookSlot() {
  const date = document.getElementById('sched-date').value;
  if (!selectedSlot) { showToast('Please select a time slot'); return; }
  if (!date) { showToast('Please select a date'); return; }
  const msg = `SCHEDULE REQUEST\n\nDate: ${date}\nTime: ${selectedSlot}\n\nI'd like to schedule a sharpening service.`;
  window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
  showToast('Booking request sent!');
}
async function updateSlots() {
  const date = document.getElementById('sched-date')?.value;
  if (\!date) return;
  try {
    const res  = await fetch('/api/schedule.php?action=get_slots&date=' + date, {credentials:'include'});
    const data = await res.json();
    const container = document.getElementById('time-slots-container');
    if (\!container) return;
    if (data.data?.closed) {
      container.innerHTML = '<p style="color:#F7931A;">Closed on Sundays — please select another day.</p>';
      return;
    }
    if (data.success && data.data?.slots) {
      container.innerHTML = data.data.slots.map(s =>
        `<div class="time-slot${s.available ? '' : ' unavailable'}" onclick="${s.available ? 'selectSlot(this)' : ''}">${s.time}</div>`
      ).join('');
    }
  } catch(e) {}
}

// ========== PAYMENT ==========
function selectPayment(type) {
  document.getElementById('pay-cash').classList.toggle('selected', type === 'cash');
  document.getElementById('pay-bitcoin').classList.toggle('selected', type === 'bitcoin');
  document.getElementById('cash-info').style.display = type === 'cash' ? 'block' : 'none';
  document.getElementById('bitcoin-info').style.display = type === 'bitcoin' ? 'block' : 'none';
}

// ========== AUTH ==========
async function customerLogin(e) {
  if (e) e.preventDefault();
  const login    = document.getElementById('login-email')?.value || document.getElementById('login-phone')?.value || '';
  const password = document.getElementById('login-password')?.value || '';
  try {
    const res  = await fetch('/api/auth.php?action=login', {
      method: 'POST', credentials: 'include',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({login, password, csrf_token: CSRF_TOKEN})
    });
    const data = await res.json();
    if (data.success) {
      SESSION.customer_id   = data.data.id;
      SESSION.customer_name = data.data.name;
      SESSION.role          = 'customer';
      document.querySelector('.auth-container').style.display = 'none';
      document.getElementById('customer-dashboard').style.display = 'block';
      document.getElementById('dash-name') && (document.getElementById('dash-name').textContent = data.data.name);
      loadMyOrders();
    } else { showToast(data.message || 'Login failed'); }
  } catch(err) { showToast('Login error. Try again.'); }
}

function demoLogin() { customerLogin(null); }

async function logOut() {
  await fetch('/api/auth.php?action=logout', {method:'POST', credentials:'include'});
  SESSION.customer_id = SESSION.customer_name = SESSION.role = null;
  document.querySelector('.auth-container').style.display = 'block';
  document.getElementById('customer-dashboard').style.display = 'none';
}

async function loadMyOrders() {
  try {
    const res  = await fetch('/api/orders.php?action=my_orders', {credentials:'include'});
    const data = await res.json();
    const tbody = document.getElementById('my-orders-body');
    if (\!tbody) return;
    if (\!data.success || \!data.data.orders.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">No orders yet.</td></tr>';
      return;
    }
    tbody.innerHTML = data.data.orders.map(o => `
      <tr>
        <td>${o.order_number}</td>
        <td>${new Date(o.created_at).toLocaleDateString()}</td>
        <td>${o.item_count} item(s)</td>
        <td><span class="status-badge status-${o.status}">${o.status}</span></td>
        <td>$${parseFloat(o.total).toFixed(2)}</td>
      </tr>`).join('');
  } catch(e) {}
}

// ========== CONTACT FORM ==========
async function submitContactForm(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const statusDiv = document.getElementById('contact-status');

  try {
    const response = await fetch('/api/schedule.php?action=get_slots', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if (response.ok) {
      statusDiv.className = 'contact-status success';
      statusDiv.textContent = 'Message sent successfully! We will get back to you soon.';
      form.reset();
      setTimeout(() => statusDiv.style.display = 'none', 5000);
    } else {
      statusDiv.className = 'contact-status error';
      statusDiv.textContent = data.message || 'Failed to send message. Please try again.';
    }
  } catch (error) {
    statusDiv.className = 'contact-status error';
    statusDiv.textContent = 'Error sending message. Please contact us via WhatsApp.';
  }
}

// ========== TOAST ==========
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// Set today as min date for scheduling
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  const dateInputs = document.querySelectorAll('input[type=date]');
  dateInputs.forEach(d => d.setAttribute('min', today));
  calcTotal();
});
</script>
</body>
</html>
