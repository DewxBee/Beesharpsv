<?php
require_once __DIR__ . '/config.php';

// Admin auth gate — redirect to login if not authenticated
if (empty($_SESSION['admin_id'])) {
    // Show inline login form instead of redirect
    $csrfToken = csrfToken();
    ?><\!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bee Sharp SV — Admin Login</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#000;color:#e0e0e0;font-family:monospace;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.login-box{background:#111;border:1px solid #333;border-radius:10px;padding:32px;width:340px;}
h2{color:#F7931A;margin-bottom:20px;font-size:1.3rem;}
label{display:block;color:#F7931A;font-size:0.82rem;margin:12px 0 4px;}
input{width:100%;background:#1a1a1a;border:1px solid #333;color:#e0e0e0;padding:9px 12px;border-radius:4px;font-family:monospace;}
input:focus{outline:none;border-color:#F7931A;}
.btn{width:100%;background:#F7931A;color:#000;border:none;padding:12px;border-radius:6px;cursor:pointer;font-weight:bold;font-size:1rem;margin-top:16px;}
.btn:hover{background:#ffa833;}
.err{color:#ff5050;margin-top:8px;font-size:0.85rem;display:none;}
</style>
</head><body>
<div class="login-box">
  <h2>🔪 Bee Sharp SV Admin</h2>
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
  <label>Username or Email</label>
  <input type="text" id="adm-user" placeholder="admin">
  <label>Password</label>
  <input type="password" id="adm-pass">
  <p class="err" id="adm-err"></p>
  <button class="btn" onclick="adminLogin()">Login</button>
</div>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
async function adminLogin() {
  const username = document.getElementById('adm-user').value;
  const password = document.getElementById('adm-pass').value;
  const err = document.getElementById('adm-err');
  try {
    const res  = await fetch('/api/auth.php?action=admin_login', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({username, password, csrf_token: CSRF})
    });
    const data = await res.json();
    if (data.success) { location.reload(); }
    else { err.style.display='block'; err.textContent = data.message || 'Login failed'; }
  } catch(e) { err.style.display='block'; err.textContent='Error. Try again.'; }
}
document.addEventListener('keydown', e => { if (e.key === 'Enter') adminLogin(); });
</script>
</body></html>
<?php
    exit;
}
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bee Sharp SV – Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --orange:#F7931A; --orange-dark:#d4780e; --black:#000; --dark:#111;
  --card:#1a1a1a; --card2:#222; --text:#e0e0e0; --text-dim:#999; --border:#333;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--black);color:var(--text);font-family:'Rajdhani',sans-serif;font-size:15px;}
h1,h2,h3,h4{font-family:'Oswald',sans-serif;color:var(--orange);letter-spacing:1px;}
a{color:var(--orange);text-decoration:none;}

/* LAYOUT */
.admin-shell{display:flex;min-height:100vh;}
.sidebar{width:240px;background:var(--dark);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;height:100vh;overflow-y:auto;z-index:100;}
.main{margin-left:240px;flex:1;min-height:100vh;}
@media(max-width:768px){.sidebar{width:100%;height:auto;position:relative;}.main{margin-left:0;}.sidebar-links{display:none;flex-direction:column;}.sidebar-links.open{display:flex;}}

/* SIDEBAR */
.sidebar-brand{padding:20px 16px;border-bottom:1px solid var(--border);}
.sidebar-brand .logo{display:flex;align-items:center;gap:10px;}
.sidebar-brand img{width:40px;height:40px;border-radius:50%;object-fit:cover;}
.sidebar-brand h2{font-size:1rem;color:var(--orange);}
.sidebar-brand p{font-size:0.75rem;color:var(--text-dim);}
.sidebar-links{display:flex;flex-direction:column;padding:12px 0;flex:1;}
.sidebar-link{display:flex;align-items:center;gap:10px;padding:11px 20px;color:var(--text-dim);cursor:pointer;transition:all .2s;border-left:3px solid transparent;font-size:0.9rem;}
.sidebar-link:hover,.sidebar-link.active{color:var(--orange);background:rgba(247,147,26,0.06);border-left-color:var(--orange);}
.sidebar-link .icon{font-size:1.1rem;width:20px;text-align:center;}
.sidebar-section{padding:12px 20px 4px;color:#555;font-size:0.7rem;letter-spacing:2px;font-family:'Oswald',sans-serif;}
.sidebar-bottom{padding:16px;border-top:1px solid var(--border);}
.sidebar-bottom a{color:var(--text-dim);font-size:0.85rem;display:block;margin-bottom:6px;}
.sidebar-bottom a:hover{color:var(--orange);}

/* TOPBAR */
.topbar{background:var(--dark);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.topbar h3{color:var(--orange);font-size:1.1rem;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.notif-btn{background:var(--card);border:1px solid var(--border);color:var(--text);padding:6px 14px;border-radius:6px;cursor:pointer;font-family:'Oswald',sans-serif;font-size:0.85rem;transition:all .2s;}
.notif-btn:hover{border-color:var(--orange);color:var(--orange);}
.online-dot{width:8px;height:8px;background:#00c864;border-radius:50%;display:inline-block;margin-right:4px;}

/* CONTENT */
.content{padding:24px;display:none;}
.content.active{display:block;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;border-left:3px solid var(--orange);}
.stat-card .num{font-family:'Oswald',sans-serif;font-size:2rem;color:var(--orange);}
.stat-card .label{color:var(--text-dim);font-size:0.85rem;margin-top:2px;}
.stat-card .change{font-size:0.8rem;color:#00c864;margin-top:4px;}

/* TABLES */
.table-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--border);}
table{width:100%;border-collapse:collapse;font-size:0.88rem;}
thead th{background:var(--card2);color:var(--orange);font-family:'Oswald',sans-serif;padding:11px 14px;text-align:left;border-bottom:2px solid var(--orange);white-space:nowrap;}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);color:var(--text);}
tbody tr:hover td{background:rgba(247,147,26,0.04);}
.badge{padding:3px 9px;border-radius:12px;font-size:0.78rem;font-family:'Oswald',sans-serif;white-space:nowrap;}
.badge-green{background:rgba(0,200,100,0.15);color:#00c864;border:1px solid rgba(0,200,100,0.3);}
.badge-orange{background:rgba(247,147,26,0.15);color:var(--orange);border:1px solid rgba(247,147,26,0.3);}
.badge-gray{background:rgba(150,150,150,0.15);color:#999;border:1px solid rgba(150,150,150,0.3);}
.badge-blue{background:rgba(100,150,255,0.15);color:#6496ff;border:1px solid rgba(100,150,255,0.3);}
.badge-red{background:rgba(255,80,80,0.15);color:#ff5050;border:1px solid rgba(255,80,80,0.3);}
.btn{display:inline-block;padding:8px 18px;border-radius:6px;font-family:'Oswald',sans-serif;font-size:0.85rem;cursor:pointer;border:none;letter-spacing:0.5px;transition:all .2s;}
.btn-primary{background:var(--orange);color:#000;}
.btn-primary:hover{background:#ffa833;}
.btn-sm{padding:4px 10px;font-size:0.78rem;}
.btn-outline{background:none;border:1px solid var(--border);color:var(--text-dim);}
.btn-outline:hover{border-color:var(--orange);color:var(--orange);}
.btn-danger{background:rgba(255,80,80,0.15);border:1px solid rgba(255,80,80,0.3);color:#ff5050;}
.btn-danger:hover{background:rgba(255,80,80,0.25);}
.action-group{display:flex;gap:6px;flex-wrap:wrap;}

/* FORMS */
.form-group{margin-bottom:16px;}
.form-group label{display:block;color:var(--orange);font-family:'Oswald',sans-serif;font-size:0.85rem;margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;background:var(--card);border:1px solid var(--border);border-radius:6px;padding:9px 12px;color:var(--text);font-family:'Rajdhani',sans-serif;font-size:0.95rem;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--orange);}
.form-group select option{background:var(--card);}
.form-group textarea{resize:vertical;min-height:90px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:600px){.form-row{grid-template-columns:1fr;}}
.section-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px;}
.section-card h3{margin-bottom:14px;font-size:1rem;}

/* NOTIFICATION PANEL */
.notif-types{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:20px;}
.notif-type{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:16px;cursor:pointer;transition:all .2s;text-align:center;}
.notif-type:hover,.notif-type.selected{border-color:var(--orange);background:rgba(247,147,26,0.06);}
.notif-type .nt-icon{font-size:1.8rem;margin-bottom:8px;}
.notif-type h4{color:var(--orange);font-size:0.95rem;margin-bottom:4px;}
.notif-type p{color:var(--text-dim);font-size:0.8rem;}
.customer-select{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:12px;max-height:200px;overflow-y:auto;margin-bottom:16px;}
.customer-check{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #1a1a1a;}
.customer-check:last-child{border-bottom:none;}
.customer-check input[type=checkbox]{accent-color:var(--orange);width:16px;height:16px;}
.customer-check label{color:var(--text);font-size:0.9rem;cursor:pointer;}
.preview-box{background:#000;border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:16px;font-size:0.88rem;}
.preview-box .preview-header{color:var(--orange);font-family:'Oswald',sans-serif;margin-bottom:6px;}

/* CHARTS PLACEHOLDER */
.chart-placeholder{background:var(--card2);border:1px solid var(--border);border-radius:8px;padding:30px;text-align:center;color:var(--text-dim);font-size:0.9rem;margin-bottom:20px;}
.mini-bar{display:flex;align-items:flex-end;gap:6px;height:80px;padding:10px 0;}
.bar{flex:1;background:var(--orange);border-radius:3px 3px 0 0;opacity:0.7;transition:opacity .2s;}
.bar:hover{opacity:1;}

/* CONTROLS */
.controls{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;}
.controls input,.controls select{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:7px 12px;color:var(--text);font-family:'Rajdhani',sans-serif;flex:1;min-width:130px;}
.controls input:focus,.controls select:focus{outline:none;border-color:var(--orange);}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:200;display:none;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--dark);border:1px solid var(--border);border-radius:12px;padding:28px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto;}
.modal h3{margin-bottom:16px;}
.modal-close{float:right;background:none;border:none;color:var(--text-dim);font-size:1.2rem;cursor:pointer;margin-top:-4px;}
.modal-close:hover{color:var(--orange);}

/* TOAST */
.toast{position:fixed;bottom:24px;right:24px;background:var(--orange);color:#000;padding:12px 20px;border-radius:8px;font-family:'Oswald',sans-serif;z-index:9999;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none;}
.toast.show{opacity:1;transform:translateY(0);}

/* STATUS TIMELINE */
.timeline{padding-left:20px;border-left:2px solid var(--border);margin-bottom:20px;}
.tl-item{position:relative;padding:0 0 16px 20px;}
.tl-item::before{content:'';position:absolute;left:-9px;top:4px;width:14px;height:14px;border-radius:50%;background:var(--orange);border:2px solid #000;}
.tl-item.done::before{background:#00c864;}
.tl-item h4{font-size:0.9rem;color:var(--text);}
.tl-item p{font-size:0.8rem;color:var(--text-dim);}

/* SEARCH HIGHLIGHT */
.hl{background:rgba(247,147,26,0.3);border-radius:2px;}
</style>
</head>
<body>

<div class="admin-shell">
<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="logo">
      <img src="logo-black.jpeg" alt="Bee Sharp" onerror="this.style.display='none'">
      <div>
        <h2>BEE SHARP</h2>
        <p>Admin Panel</p>
      </div>
    </div>
  </div>

  <div class="sidebar-links">
    <div class="sidebar-section">MAIN</div>
    <div class="sidebar-link active" onclick="showSection('dashboard',this)"><span class="icon">📊</span> Dashboard</div>
    <div class="sidebar-link" onclick="showSection('orders',this)"><span class="icon">📋</span> Orders</div>
    <div class="sidebar-link" onclick="showSection('customers',this)"><span class="icon">👥</span> Customers</div>
    <div class="sidebar-link" onclick="showSection('schedule',this)"><span class="icon">📅</span> Schedule</div>

    <div class="sidebar-section">TOOLS</div>
    <div class="sidebar-link" onclick="showSection('notifications',this)"><span class="icon">🔔</span> Notifications</div>
    <div class="sidebar-link" onclick="showSection('inventory',this)"><span class="icon">🔪</span> Inventory</div>
    <div class="sidebar-link" onclick="showSection('analytics',this)"><span class="icon">📈</span> Analytics</div>
    <div class="sidebar-link" onclick="showSection('bitcoin',this)"><span class="icon">₿</span> Bitcoin</div>

    <div class="sidebar-section">SYSTEM</div>
    <div class="sidebar-link" onclick="showSection('settings',this)"><span class="icon">⚙️</span> Settings</div>
  </div>

  <div class="sidebar-bottom">
    <a href="index.html" target="_blank">← Back to Website</a>
    <a href="#" onclick="adminLogout()">🚪 Log Out</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">

<!-- TOPBAR -->
<div class="topbar">
  <h3 id="page-title">Dashboard</h3>
  <div class="topbar-right">
    <span style="color:var(--text-dim);font-size:0.85rem;"><span class="online-dot"></span>Admin</span>
    <button class="notif-btn" onclick="showSection('notifications',null)">🔔 Send Notification</button>
    <a href="https://wa.me/50379522492" target="_blank" class="notif-btn">💬 WhatsApp</a>
  </div>
</div>

<!-- ===== DASHBOARD ===== -->
<div class="content active" id="sec-dashboard">
  <div class="stats-row">
    <div class="stat-card"><div class="num">14</div><div class="label">Active Orders</div><div class="change">↑ 3 today</div></div>
    <div class="stat-card"><div class="num">47</div><div class="label">Total Customers</div><div class="change">↑ 2 this week</div></div>
    <div class="stat-card"><div class="num">$312</div><div class="label">Revenue (Month)</div><div class="change">↑ 18% vs last</div></div>
    <div class="stat-card"><div class="num">₿ 38%</div><div class="label">Bitcoin Payments</div><div class="change">↑ 5% this month</div></div>
    <div class="stat-card"><div class="num">156</div><div class="label">Items Sharpened</div><div class="change">This month</div></div>
    <div class="stat-card"><div class="num">4.9★</div><div class="label">Avg Rating</div><div class="change">From 23 reviews</div></div>
  </div>

  <div class="section-card">
    <h3>Recent Activity</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Service</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="recent-orders-body">
          <tr>
            <td>#1015</td><td>María G.</td><td>3 knives</td><td>Pick-up</td><td><span class="badge badge-orange">₿ Bitcoin</span></td>
            <td><span class="badge badge-blue">Scheduled</span></td>
            <td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1015','María G.','3 knives','Scheduled')">View</button><button class="btn btn-sm btn-primary" onclick="updateStatus('#1015')">Update</button></div></td>
          </tr>
          <tr>
            <td>#1014</td><td>Carlos M.</td><td>1 axe, 2 machetes</td><td>On-site</td><td><span class="badge badge-gray">Cash</span></td>
            <td><span class="badge badge-orange">In Progress</span></td>
            <td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1014','Carlos M.','1 axe, 2 machetes','In Progress')">View</button><button class="btn btn-sm btn-primary" onclick="updateStatus('#1014')">Update</button></div></td>
          </tr>
          <tr>
            <td>#1013</td><td>Ana R.</td><td>5 garden tools</td><td>Delivery</td><td><span class="badge badge-orange">₿ Bitcoin</span></td>
            <td><span class="badge badge-green">Delivered</span></td>
            <td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1013','Ana R.','5 garden tools','Delivered')">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Ana R.')">Notify</button></div></td>
          </tr>
          <tr>
            <td>#1012</td><td>Juan P.</td><td>2 knives, 1 pizza cutter</td><td>Market</td><td><span class="badge badge-gray">Cash</span></td>
            <td><span class="badge badge-green">Complete</span></td>
            <td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1012','Juan P.','2 knives, 1 pizza cutter','Complete')">View</button></div></td>
          </tr>
          <tr>
            <td>#1011</td><td>Sofia L.</td><td>4 knives, 2 shears</td><td>Pick-up</td><td><span class="badge badge-orange">₿ Bitcoin</span></td>
            <td><span class="badge badge-blue">Scheduled</span></td>
            <td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1011','Sofia L.','4 knives, 2 shears','Scheduled')">View</button><button class="btn btn-sm btn-primary" onclick="updateStatus('#1011')">Update</button></div></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px;">
      <button class="btn btn-outline" onclick="showSection('orders',null)">View All Orders →</button>
      <button class="btn btn-primary" onclick="openNewOrder()">+ New Order</button>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="section-card">
      <h3>Monthly Revenue</h3>
      <div class="mini-bar">
        <div class="bar" style="height:40%" title="Nov $180"></div>
        <div class="bar" style="height:55%" title="Dec $248"></div>
        <div class="bar" style="height:45%" title="Jan $203"></div>
        <div class="bar" style="height:60%" title="Feb $270"></div>
        <div class="bar" style="height:70%" title="Mar $315"></div>
        <div class="bar" style="height:69%" title="Apr $312"></div>
      </div>
      <div style="display:flex;justify-content:space-between;color:var(--text-dim);font-size:0.75rem;margin-top:4px;">
        <span>Nov</span><span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span>
      </div>
    </div>
    <div class="section-card">
      <h3>Payment Split</h3>
      <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
        <div>
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span style="color:var(--text-dim);">Cash</span><span>62%</span></div>
          <div style="background:var(--card2);border-radius:4px;height:8px;"><div style="background:#888;height:8px;border-radius:4px;width:62%;"></div></div>
        </div>
        <div>
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span style="color:var(--orange);">₿ Bitcoin</span><span style="color:var(--orange);">38%</span></div>
          <div style="background:var(--card2);border-radius:4px;height:8px;"><div style="background:var(--orange);height:8px;border-radius:4px;width:38%;"></div></div>
        </div>
      </div>
      <p style="color:var(--text-dim);font-size:0.8rem;margin-top:12px;">Bitcoin adoption growing 5% MoM 🚀</p>
    </div>
  </div>
</div>

<!-- ===== ORDERS ===== -->
<div class="content" id="sec-orders">
  <div class="controls">
    <input type="text" placeholder="Search orders..." oninput="filterOrders(this.value)">
    <select onchange="filterByStatus(this.value)">
      <option value="">All Statuses</option>
      <option value="Scheduled">Scheduled</option>
      <option value="Picked Up">Picked Up</option>
      <option value="In Progress">In Progress</option>
      <option value="Ready">Ready</option>
      <option value="Out for Delivery">Out for Delivery</option>
      <option value="Delivered">Delivered</option>
      <option value="Complete">Complete</option>
    </select>
    <select>
      <option value="">All Services</option>
      <option>Pick-up & Delivery</option>
      <option>On-site</option>
      <option>Farmers Market</option>
    </select>
    <button class="btn btn-primary" onclick="openNewOrder()">+ New Order</button>
  </div>

  <div class="table-wrap">
    <table id="orders-table">
      <thead>
        <tr><th>#</th><th>Customer</th><th>Phone</th><th>Items</th><th>Service</th><th>Date</th><th>Payment</th><th>Total</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr><td>#1015</td><td>María G.</td><td>+503 7811-1234</td><td>3 knives</td><td>Pick-up</td><td>Apr 15</td><td><span class="badge badge-orange">₿</span></td><td>$13.50</td><td><span class="badge badge-blue">Scheduled</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1015','María G.','3 knives','Scheduled')">View</button><button class="btn btn-sm btn-primary" onclick="updateStatus('#1015')">Update</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('María G.')">📨</button></div></td></tr>
        <tr><td>#1014</td><td>Carlos M.</td><td>+503 7822-5678</td><td>1 axe, 2 machetes</td><td>On-site</td><td>Apr 15</td><td><span class="badge badge-gray">Cash</span></td><td>$21.00</td><td><span class="badge badge-orange">In Progress</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1014','Carlos M.','1 axe, 2 machetes','In Progress')">View</button><button class="btn btn-sm btn-primary" onclick="updateStatus('#1014')">Update</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Carlos M.')">📨</button></div></td></tr>
        <tr><td>#1013</td><td>Ana R.</td><td>+503 7833-9012</td><td>5 garden tools</td><td>Delivery</td><td>Apr 14</td><td><span class="badge badge-orange">₿</span></td><td>$40.50</td><td><span class="badge badge-green">Delivered</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1013','Ana R.','5 garden tools','Delivered')">View</button></div></td></tr>
        <tr><td>#1012</td><td>Juan P.</td><td>+503 7844-3456</td><td>2 knives, 1 pizza cutter</td><td>Market</td><td>Apr 13</td><td><span class="badge badge-gray">Cash</span></td><td>$10.00</td><td><span class="badge badge-green">Complete</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1012','Juan P.','2 knives, 1 pizza cutter','Complete')">View</button></div></td></tr>
        <tr><td>#1011</td><td>Sofia L.</td><td>+503 7855-7890</td><td>4 knives, 2 shears</td><td>Pick-up</td><td>Apr 16</td><td><span class="badge badge-orange">₿</span></td><td>$36.00</td><td><span class="badge badge-blue">Scheduled</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1011','Sofia L.','4 knives, 2 shears','Scheduled')">View</button><button class="btn btn-sm btn-primary" onclick="updateStatus('#1011')">Update</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Sofia L.')">📨</button></div></td></tr>
        <tr><td>#1010</td><td>Roberto V.</td><td>+503 7866-2345</td><td>8 knives, 1 axe</td><td>Pick-up</td><td>Apr 12</td><td><span class="badge badge-orange">₿</span></td><td>$47.70</td><td><span class="badge badge-green">Delivered</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1010','Roberto V.','8 knives, 1 axe','Delivered')">View</button></div></td></tr>
        <tr><td>#1009</td><td>Laura C.</td><td>+503 7877-6789</td><td>2 loppers, 3 pruners</td><td>Delivery</td><td>Apr 11</td><td><span class="badge badge-gray">Cash</span></td><td>$45.00</td><td><span class="badge badge-green">Complete</span></td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="openOrder('#1009','Laura C.','2 loppers, 3 pruners','Complete')">View</button></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== CUSTOMERS ===== -->
<div class="content" id="sec-customers">
  <div class="controls">
    <input type="text" placeholder="Search customers...">
    <select>
      <option>All Customers</option>
      <option>Bitcoin Payers</option>
      <option>Frequent (5+ orders)</option>
      <option>New (Last 30 days)</option>
    </select>
    <button class="btn btn-primary" onclick="openNewCustomer()">+ New Customer</button>
    <button class="btn btn-outline" onclick="showSection('notifications',null)">📨 Mass Notify</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>WhatsApp</th><th>Area</th><th>Orders</th><th>Total Spent</th><th>Payment Pref.</th><th>Last Order</th><th>Actions</th></tr></thead>
      <tbody>
        <tr><td>María García</td><td>+503 7811-1234</td><td>Santa Tecla</td><td>6</td><td>$87.30</td><td><span class="badge badge-orange">₿ Bitcoin</span></td><td>Apr 15</td><td><div class="action-group"><button class="btn btn-sm btn-outline" onclick="viewCustomer('María García')">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('María García')">📨</button><a href="https://wa.me/50378111234" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
        <tr><td>Carlos Méndez</td><td>+503 7822-5678</td><td>San Salvador</td><td>4</td><td>$62.00</td><td><span class="badge badge-gray">Cash</span></td><td>Apr 15</td><td><div class="action-group"><button class="btn btn-sm btn-outline">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Carlos Méndez')">📨</button><a href="https://wa.me/50378225678" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
        <tr><td>Ana Rodríguez</td><td>+503 7833-9012</td><td>La Libertad</td><td>9</td><td>$186.50</td><td><span class="badge badge-orange">₿ Bitcoin</span></td><td>Apr 14</td><td><div class="action-group"><button class="btn btn-sm btn-outline">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Ana Rodríguez')">📨</button><a href="https://wa.me/50378339012" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
        <tr><td>Juan Pérez</td><td>+503 7844-3456</td><td>San Salvador</td><td>3</td><td>$28.00</td><td><span class="badge badge-gray">Cash</span></td><td>Apr 13</td><td><div class="action-group"><button class="btn btn-sm btn-outline">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Juan Pérez')">📨</button><a href="https://wa.me/50378443456" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
        <tr><td>Sofia López</td><td>+503 7855-7890</td><td>Santa Tecla</td><td>7</td><td>$134.20</td><td><span class="badge badge-orange">₿ Bitcoin</span></td><td>Apr 16</td><td><div class="action-group"><button class="btn btn-sm btn-outline">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Sofia López')">📨</button><a href="https://wa.me/50378557890" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
        <tr><td>Roberto Vásquez</td><td>+503 7866-2345</td><td>San Salvador</td><td>11</td><td>$312.80</td><td><span class="badge badge-orange">₿ Bitcoin</span></td><td>Apr 12</td><td><div class="action-group"><button class="btn btn-sm btn-outline">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Roberto Vásquez')">📨</button><a href="https://wa.me/50378662345" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
        <tr><td>Laura Castillo</td><td>+503 7877-6789</td><td>La Libertad</td><td>5</td><td>$98.00</td><td><span class="badge badge-gray">Cash</span></td><td>Apr 11</td><td><div class="action-group"><button class="btn btn-sm btn-outline">View</button><button class="btn btn-sm btn-outline" onclick="notifyCustomer('Laura Castillo')">📨</button><a href="https://wa.me/50378776789" target="_blank" class="btn btn-sm btn-outline">💬</a></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== SCHEDULE ===== -->
<div class="content" id="sec-schedule">
  <div class="controls">
    <input type="date" id="sched-view-date">
    <select>
      <option>All Service Types</option>
      <option>Pick-up</option>
      <option>Delivery</option>
      <option>On-site</option>
      <option>Market</option>
    </select>
    <button class="btn btn-primary" onclick="openNewOrder()">+ Add Appointment</button>
  </div>

  <div class="section-card">
    <h3>📅 Today's Schedule — Apr 15, 2026</h3>
    <div style="margin-top:14px;">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Time</th><th>Customer</th><th>Service</th><th>Items</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr><td>9:00 AM</td><td>María G.</td><td>Pick-up</td><td>3 knives</td><td>Santa Tecla</td><td><span class="badge badge-blue">Scheduled</span></td><td><button class="btn btn-sm btn-primary" onclick="updateStatus('#1015')">Update</button></td></tr>
            <tr><td>10:30 AM</td><td>Carlos M.</td><td>On-site</td><td>1 axe, 2 machetes</td><td>San Salvador</td><td><span class="badge badge-orange">In Progress</span></td><td><button class="btn btn-sm btn-primary" onclick="updateStatus('#1014')">Update</button></td></tr>
            <tr><td>2:00 PM</td><td>Sofia L.</td><td>Pick-up</td><td>4 knives, 2 shears</td><td>Santa Tecla</td><td><span class="badge badge-blue">Scheduled</span></td><td><button class="btn btn-sm btn-primary" onclick="updateStatus('#1011')">Update</button></td></tr>
            <tr><td>4:00 PM</td><td>—</td><td colspan="4" style="color:var(--text-dim);">Available slot</td><td><button class="btn btn-sm btn-outline" onclick="openNewOrder()">+ Book</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="section-card">
    <h3>🌾 Next Farmers Market</h3>
    <p style="color:var(--text-dim);margin-bottom:12px;">Club Cocal Bitcoin Farmers Market</p>
    <div style="display:flex;gap:20px;flex-wrap:wrap;">
      <div><span style="color:var(--orange);">Date:</span> <span style="color:var(--text);">TBD — Check Club Cocal schedule</span></div>
      <div><span style="color:var(--orange);">Location:</span> <span style="color:var(--text);">Club Cocal, El Salvador</span></div>
    </div>
    <div style="margin-top:14px;">
      <a href="https://wa.me/50379522492" target="_blank" class="btn btn-outline btn-sm">Announce via WhatsApp</a>
    </div>
  </div>
</div>

<!-- ===== NOTIFICATIONS ===== -->
<div class="content" id="sec-notifications">
  <div class="section-card">
    <h3>🔔 Send Notification</h3>
    <p style="color:var(--text-dim);margin-bottom:20px;font-size:0.9rem;">Send WhatsApp messages to customers about their orders, promotions, or announcements.</p>

    <div class="notif-types">
      <div class="notif-type selected" id="nt-status" onclick="selectNotifType('status')">
        <div class="nt-icon">📦</div>
        <h4>Order Status</h4>
        <p>Notify customer of their order progress</p>
      </div>
      <div class="notif-type" id="nt-ready" onclick="selectNotifType('ready')">
        <div class="nt-icon">✅</div>
        <h4>Ready / Complete</h4>
        <p>Items ready for pickup or delivered</p>
      </div>
      <div class="notif-type" id="nt-promo" onclick="selectNotifType('promo')">
        <div class="nt-icon">⚡</div>
        <h4>Promotion</h4>
        <p>Bitcoin discount or special offer</p>
      </div>
      <div class="notif-type" id="nt-market" onclick="selectNotifType('market')">
        <div class="nt-icon">🌾</div>
        <h4>Market Event</h4>
        <p>Farmers market announcement</p>
      </div>
      <div class="notif-type" id="nt-custom" onclick="selectNotifType('custom')">
        <div class="nt-icon">✏️</div>
        <h4>Custom</h4>
        <p>Write your own message</p>
      </div>
    </div>

    <div class="form-group">
      <label>Recipients</label>
      <select id="recipient-type" onchange="updateRecipients()">
        <option value="single">Single Customer</option>
        <option value="all">All Customers</option>
        <option value="bitcoin">Bitcoin Customers Only</option>
        <option value="active">Active Orders Only</option>
        <option value="select">Select Customers</option>
      </select>
    </div>

    <div id="single-recipient" class="form-group">
      <label>Customer</label>
      <select id="notif-customer">
        <option>María García (+503 7811-1234)</option>
        <option>Carlos Méndez (+503 7822-5678)</option>
        <option>Ana Rodríguez (+503 7833-9012)</option>
        <option>Juan Pérez (+503 7844-3456)</option>
        <option>Sofia López (+503 7855-7890)</option>
        <option>Roberto Vásquez (+503 7866-2345)</option>
        <option>Laura Castillo (+503 7877-6789)</option>
      </select>
    </div>

    <div id="select-recipients" style="display:none;" class="form-group">
      <label>Select Customers</label>
      <div class="customer-select">
        <div class="customer-check"><input type="checkbox" id="c1"><label for="c1">María García · +503 7811-1234</label></div>
        <div class="customer-check"><input type="checkbox" id="c2"><label for="c2">Carlos Méndez · +503 7822-5678</label></div>
        <div class="customer-check"><input type="checkbox" id="c3"><label for="c3">Ana Rodríguez · +503 7833-9012</label></div>
        <div class="customer-check"><input type="checkbox" id="c4"><label for="c4">Juan Pérez · +503 7844-3456</label></div>
        <div class="customer-check"><input type="checkbox" id="c5"><label for="c5">Sofia López · +503 7855-7890</label></div>
        <div class="customer-check"><input type="checkbox" id="c6"><label for="c6">Roberto Vásquez · +503 7866-2345</label></div>
        <div class="customer-check"><input type="checkbox" id="c7"><label for="c7">Laura Castillo · +503 7877-6789</label></div>
      </div>
    </div>

    <div class="form-group">
      <label>Message</label>
      <textarea id="notif-msg" rows="5" oninput="updatePreview()"></textarea>
    </div>

    <div class="preview-box">
      <div class="preview-header">📱 WhatsApp Preview</div>
      <div id="notif-preview" style="color:var(--text-dim);">Your message will appear here...</div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <button class="btn btn-primary" onclick="sendNotification()">📤 Send via WhatsApp</button>
      <button class="btn btn-outline" onclick="clearNotif()">Clear</button>
    </div>
  </div>

  <div class="section-card">
    <h3>📜 Notification History</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Type</th><th>Recipients</th><th>Message</th><th>Status</th></tr></thead>
        <tbody>
          <tr><td>Apr 14, 2:30 PM</td><td>Order Ready</td><td>Ana Rodríguez</td><td>"Your items are ready for delivery..."</td><td><span class="badge badge-green">Sent</span></td></tr>
          <tr><td>Apr 13, 10:00 AM</td><td>Promotion</td><td>All (7)</td><td>"⚡ This week: 10% off with Bitcoin..."</td><td><span class="badge badge-green">Sent</span></td></tr>
          <tr><td>Apr 12, 9:00 AM</td><td>Market Event</td><td>All (7)</td><td>"🌾 We'll be at Club Cocal this Saturday!"</td><td><span class="badge badge-green">Sent</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ===== INVENTORY ===== -->
<div class="content" id="sec-inventory">
  <div class="stats-row">
    <div class="stat-card"><div class="num">48</div><div class="label">Knives Sharpened (Month)</div></div>
    <div class="stat-card"><div class="num">19</div><div class="label">Garden Tools</div></div>
    <div class="stat-card"><div class="num">23</div><div class="label">Axes / Machetes</div></div>
    <div class="stat-card"><div class="num">7</div><div class="label">Pizza Cutters (Free!)</div></div>
  </div>
  <div class="section-card">
    <h3>🔪 Equipment & Supplies</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Item</th><th>Status</th><th>Last Service</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>Belt Grinder #1</td><td><span class="badge badge-green">Operational</span></td><td>Apr 10</td><td>Belt changed</td></tr>
          <tr><td>Whetstone Set</td><td><span class="badge badge-green">Operational</span></td><td>Apr 12</td><td>8000/3000 grit</td></tr>
          <tr><td>Angle Guide</td><td><span class="badge badge-green">Operational</span></td><td>—</td><td>—</td></tr>
          <tr><td>Honing Rod (ceramic)</td><td><span class="badge badge-orange">Low Stock</span></td><td>—</td><td>Need 2 more</td></tr>
          <tr><td>Delivery Packaging</td><td><span class="badge badge-green">Stocked</span></td><td>—</td><td>25 units</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ===== ANALYTICS ===== -->
<div class="content" id="sec-analytics">
  <div class="stats-row">
    <div class="stat-card"><div class="num">$312</div><div class="label">This Month</div><div class="change">↑ 18% vs Mar</div></div>
    <div class="stat-card"><div class="num">$1,529</div><div class="label">Year to Date</div></div>
    <div class="stat-card"><div class="num">156</div><div class="label">Items Sharpened</div></div>
    <div class="stat-card"><div class="num">$2.00</div><div class="label">Avg per Item</div></div>
  </div>
  <div class="section-card">
    <h3>📈 Top Services</h3>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <div><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Knives ($5)</span><span>48 items · $240</span></div><div style="background:var(--card2);border-radius:4px;height:10px;"><div style="background:var(--orange);height:10px;border-radius:4px;width:77%;"></div></div></div>
      <div><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Garden Tools ($9)</span><span>19 items · $171</span></div><div style="background:var(--card2);border-radius:4px;height:10px;"><div style="background:var(--orange);height:10px;border-radius:4px;width:55%;"></div></div></div>
      <div><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Axes/Machetes ($7)</span><span>23 items · $161</span></div><div style="background:var(--card2);border-radius:4px;height:10px;"><div style="background:var(--orange);height:10px;border-radius:4px;width:52%;"></div></div></div>
      <div><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Pizza Cutters (Free)</span><span>7 items · $0</span></div><div style="background:var(--card2);border-radius:4px;height:10px;"><div style="background:#555;height:10px;border-radius:4px;width:22%;"></div></div></div>
    </div>
  </div>
</div>

<!-- ===== BITCOIN ===== -->
<div class="content" id="sec-bitcoin">
  <div class="stats-row">
    <div class="stat-card"><div class="num">₿ 38%</div><div class="label">Bitcoin Payment Rate</div></div>
    <div class="stat-card"><div class="num">$118</div><div class="label">Bitcoin Revenue (Month)</div></div>
    <div class="stat-card"><div class="num">18</div><div class="label">Bitcoin Transactions</div></div>
    <div class="stat-card"><div class="num">$11.80</div><div class="label">Discounts Given</div></div>
  </div>
  <div class="section-card">
    <h3>₿ Bitcoin Settings</h3>
    <div class="form-group">
      <label>Lightning Address / LNURL</label>
      <input type="text" placeholder="beesharpsv@your-lightning-provider.com">
    </div>
    <div class="form-group">
      <label>On-Chain Address (for display)</label>
      <input type="text" placeholder="bc1q...">
    </div>
    <div class="form-group">
      <label>Bitcoin Discount Rate</label>
      <select><option>10%</option><option>5%</option><option>15%</option><option>20%</option></select>
    </div>
    <button class="btn btn-primary" onclick="showToast('Bitcoin settings saved!')">Save Settings</button>
  </div>
  <div class="section-card">
    <h3>Recent Bitcoin Transactions</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Customer</th><th>Amount (USD)</th><th>Method</th><th>Status</th></tr></thead>
        <tbody>
          <tr><td>Apr 15</td><td>María García</td><td>$13.50</td><td>⚡ Lightning</td><td><span class="badge badge-orange">Pending</span></td></tr>
          <tr><td>Apr 14</td><td>Ana Rodríguez</td><td>$40.50</td><td>⚡ Lightning</td><td><span class="badge badge-green">Confirmed</span></td></tr>
          <tr><td>Apr 12</td><td>Roberto Vásquez</td><td>$47.70</td><td>₿ On-chain</td><td><span class="badge badge-green">Confirmed</span></td></tr>
          <tr><td>Apr 10</td><td>Sofia López</td><td>$36.00</td><td>⚡ Lightning</td><td><span class="badge badge-green">Confirmed</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ===== SETTINGS ===== -->
<div class="content" id="sec-settings">
  <div class="section-card">
    <h3>⚙️ Business Settings</h3>
    <div class="form-row">
      <div class="form-group"><label>Business Name</label><input value="Bee Sharp SV"></div>
      <div class="form-group"><label>WhatsApp</label><input value="+503 7952-2492"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Email</label><input value="bee-sharpSV@proton.me"></div>
      <div class="form-group"><label>Service Area</label><input value="San Salvador, Santa Tecla, La Libertad"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Delivery Fee ($)</label><input type="number" value="10"></div>
      <div class="form-group"><label>Free Delivery Minimum (items)</label><input type="number" value="10"></div>
    </div>
    <button class="btn btn-primary" onclick="showToast('Settings saved!')">Save Settings</button>
  </div>
  <div class="section-card">
    <h3>🔐 Admin Password</h3>
    <div class="form-group"><label>Current Password</label><input type="password"></div>
    <div class="form-row">
      <div class="form-group"><label>New Password</label><input type="password"></div>
      <div class="form-group"><label>Confirm Password</label><input type="password"></div>
    </div>
    <button class="btn btn-primary" onclick="showToast('Password updated!')">Update Password</button>
  </div>
</div>

</div><!-- end main -->
</div><!-- end shell -->

<!-- ORDER MODAL -->
<div class="modal-overlay" id="order-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('order-modal')">✕</button>
    <h3 id="modal-order-title">Order #1015</h3>
    <div id="modal-order-body"></div>
  </div>
</div>

<!-- STATUS UPDATE MODAL -->
<div class="modal-overlay" id="status-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('status-modal')">✕</button>
    <h3>Update Order Status</h3>
    <div id="status-modal-body">
      <div class="form-group">
        <label>Order</label>
        <input type="text" id="status-order-id" readonly>
      </div>
      <div class="form-group">
        <label>New Status</label>
        <select id="new-status">
          <option>Scheduled</option>
          <option>Picked Up</option>
          <option>In Progress</option>
          <option>Ready for Delivery</option>
          <option>Out for Delivery</option>
          <option>Delivered</option>
          <option>Complete</option>
        </select>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea id="status-notes" placeholder="Optional notes..."></textarea>
      </div>
      <div class="form-group">
        <label><input type="checkbox" id="send-wa-notif" checked style="accent-color:var(--orange);margin-right:8px;">Send WhatsApp notification to customer</label>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" onclick="confirmStatusUpdate()">Update Status</button>
        <button class="btn btn-outline" onclick="closeModal('status-modal')">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- NOTIFY CUSTOMER MODAL -->
<div class="modal-overlay" id="notify-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('notify-modal')">✕</button>
    <h3>Send Notification</h3>
    <div class="form-group">
      <label>Customer</label>
      <input type="text" id="notify-customer-name" readonly>
    </div>
    <div class="form-group">
      <label>Message</label>
      <textarea id="notify-msg" rows="5">Hello! This is Bee Sharp SV. Your items are ready. </textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button class="btn btn-primary" onclick="sendQuickNotify()">💬 Send via WhatsApp</button>
      <button class="btn btn-outline" onclick="closeModal('notify-modal')">Cancel</button>
    </div>
  </div>
</div>

<!-- NEW ORDER MODAL -->
<div class="modal-overlay" id="new-order-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('new-order-modal')">✕</button>
    <h3>New Order</h3>
    <div class="form-row">
      <div class="form-group"><label>Customer Name</label><input type="text" id="no-name"></div>
      <div class="form-group"><label>WhatsApp</label><input type="tel" id="no-phone"></div>
    </div>
    <div class="form-group"><label>Service Type</label>
      <select id="no-service"><option>Pick-up & Delivery</option><option>On-site</option><option>Farmers Market</option></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Date</label><input type="date" id="no-date"></div>
      <div class="form-group"><label>Time</label><input type="time" id="no-time" value="09:00"></div>
    </div>
    <div class="form-group"><label>Items</label><textarea id="no-items" placeholder="3x knife ($5), 1x axe ($7)..."></textarea></div>
    <div class="form-row">
      <div class="form-group"><label>Payment</label><select id="no-payment"><option>Cash</option><option>Bitcoin Lightning</option><option>Bitcoin On-Chain</option></select></div>
      <div class="form-group"><label>Status</label><select id="no-status"><option>Scheduled</option><option>In Progress</option></select></div>
    </div>
    <button class="btn btn-primary" style="width:100%;" onclick="saveNewOrder()">Create Order</button>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="admin-toast"></div>

<script>
let currentSection = 'dashboard';

function showSection(id, el) {
  document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
  document.getElementById('sec-' + id).classList.add('active');
  if (el) el.classList.add('active');
  const titles = {dashboard:'Dashboard',orders:'Orders',customers:'Customers',schedule:'Schedule',notifications:'Notifications',inventory:'Inventory',analytics:'Analytics',bitcoin:'Bitcoin',settings:'Settings'};
  document.getElementById('page-title').textContent = titles[id] || id;
  currentSection = id;
}

function showToast(msg) {
  const t = document.getElementById('admin-toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openOrder(id, name, items, status) {
  document.getElementById('modal-order-title').textContent = 'Order ' + id;
  document.getElementById('modal-order-body').innerHTML = `
    <div class="timeline">
      <div class="tl-item ${['Scheduled','Picked Up','In Progress','Ready for Delivery','Out for Delivery','Delivered','Complete'].indexOf(status) >= 0 ? 'done':''}">
        <h4>Scheduled</h4><p>Order created</p>
      </div>
      <div class="tl-item ${['Picked Up','In Progress','Ready for Delivery','Out for Delivery','Delivered','Complete'].indexOf(status) >= 0 ? 'done':''}">
        <h4>Picked Up</h4><p>Items collected</p>
      </div>
      <div class="tl-item ${['In Progress','Ready for Delivery','Out for Delivery','Delivered','Complete'].indexOf(status) >= 0 ? 'done':''}">
        <h4>In Progress</h4><p>Sharpening underway</p>
      </div>
      <div class="tl-item ${['Delivered','Complete'].indexOf(status) >= 0 ? 'done':''}">
        <h4>Delivered / Complete</h4><p>Job done!</p>
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <button class="btn btn-primary" onclick="updateStatus('${id}');closeModal('order-modal');">Update Status</button>
      <button class="btn btn-outline" onclick="closeModal('order-modal')">Close</button>
    </div>
  `;
  openModal('order-modal');
}

function updateStatus(orderId) {
  document.getElementById('status-order-id').value = orderId;
  openModal('status-modal');
}

function confirmStatusUpdate() {
  const status = document.getElementById('new-status').value;
  const sendWA = document.getElementById('send-wa-notif').checked;
  if (sendWA) {
    const msg = `🔪 *BEE SHARP SV UPDATE*\n\nYour order status has been updated to: *${status}*\n\nThank you for choosing Bee Sharp SV! ₿`;
    window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
  }
  closeModal('status-modal');
  showToast('Status updated to: ' + status);
}

function notifyCustomer(name) {
  document.getElementById('notify-customer-name').value = name;
  document.getElementById('notify-msg').value = `Hello ${name}! This is Bee Sharp SV. `;
  openModal('notify-modal');
}

function sendQuickNotify() {
  const msg = document.getElementById('notify-msg').value;
  window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
  closeModal('notify-modal');
  showToast('Opening WhatsApp...');
}

function openNewOrder() { openModal('new-order-modal'); }
function openNewCustomer() { showToast('Customer form coming soon!'); }
function viewCustomer(name) { showToast('Customer profile: ' + name); }

function saveNewOrder() {
  const name = document.getElementById('no-name').value;
  if (!name) { showToast('Please enter customer name'); return; }
  closeModal('new-order-modal');
  showToast('Order created for ' + name + '! ✓');
}

function updateRecipients() {
  const type = document.getElementById('recipient-type').value;
  document.getElementById('single-recipient').style.display = type === 'single' ? 'block' : 'none';
  document.getElementById('select-recipients').style.display = type === 'select' ? 'block' : 'none';
}

function selectNotifType(type) {
  document.querySelectorAll('.notif-type').forEach(n => n.classList.remove('selected'));
  document.getElementById('nt-' + type).classList.add('selected');
  const msgs = {
    status: '🔪 BEE SHARP SV UPDATE\n\nHello! Your order status has been updated to: [STATUS]\n\nQuestions? Reply to this message.',
    ready: '✅ BEE SHARP SV — READY!\n\nHello! Great news — your items have been sharpened and are ready. We will deliver shortly!\n\nThank you for choosing Bee Sharp SV! ₿',
    promo: '⚡ BEE SHARP SV SPECIAL OFFER!\n\nPay with Bitcoin (Lightning or on-chain) and get 10% OFF your next order!\n\nBook now: wa.me/50379522492',
    market: '🌾 BEE SHARP SV — FARMERS MARKET!\n\nWe will be at Club Cocal Bitcoin Farmers Market this Saturday! Bring your knives for same-day on-site sharpening. See you there!',
    custom: ''
  };
  document.getElementById('notif-msg').value = msgs[type] || '';
  updatePreview();
}

function updatePreview() {
  const msg = document.getElementById('notif-msg').value;
  document.getElementById('notif-preview').textContent = msg || 'Your message will appear here...';
}

function sendNotification() {
  const msg = document.getElementById('notif-msg').value;
  if (!msg.trim()) { showToast('Please enter a message'); return; }
  window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
  showToast('Opening WhatsApp to send notification ✓');
}

function clearNotif() {
  document.getElementById('notif-msg').value = '';
  document.getElementById('notif-preview').textContent = 'Your message will appear here...';
}

function filterOrders(q) {
  const rows = document.querySelectorAll('#orders-table tbody tr');
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}

function filterByStatus(status) {
  const rows = document.querySelectorAll('#orders-table tbody tr');
  rows.forEach(row => {
    row.style.display = !status || row.textContent.includes(status) ? '' : 'none';
  });
}

function adminLogout() {
  if (confirm('Log out of admin panel?')) window.location.href = 'index.html';
}

// Set min date for schedule
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  document.querySelectorAll('input[type=date]').forEach(d => { d.min = today; d.value = today; });
});
</script>
<script src="api.js"></script>
</body>
</html>
