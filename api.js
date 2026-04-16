// ============================================================
// BEE SHARP SV — Frontend API Connector
// Bridges index.html & admin.html to the PHP backend
// Include AFTER your page content:  <script src="api.js"></script>
// ============================================================

const API = {
    base: '/api',

    // Generic fetch helper
    async call(endpoint, params = {}, method = 'GET', body = null) {
        const url = new URL(this.base + '/' + endpoint + '.php', window.location.origin);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'include',
        };
        if (body) opts.body = JSON.stringify(body);
        try {
            const res  = await fetch(url, opts);
            const json = await res.json();
            return json;
        } catch (e) {
            console.error('API error:', e);
            return { success: false, message: 'Network error. Please try again.' };
        }
    },

    // Shorthand helpers
    get(ep, params)       { return this.call(ep, params, 'GET'); },
    post(ep, params, body){ return this.call(ep, params, 'POST', body); },

    // ── AUTH ────────────────────────────────────────────
    auth: {
        register: (d)  => API.post('auth', { action: 'register' }, d),
        login:    (d)  => API.post('auth', { action: 'login' }, d),
        adminLogin:(d) => API.post('auth', { action: 'admin_login' }, d),
        logout:   ()   => API.post('auth', { action: 'logout' }),
        me:       ()   => API.get ('auth', { action: 'me' }),
    },

    // ── ORDERS ──────────────────────────────────────────
    orders: {
        create:       (d)  => API.post('orders', { action: 'create' }, d),
        list:         (p)  => API.get ('orders', { action: 'list', ...p }),
        get:          (id) => API.get ('orders', { action: 'get', id }),
        myOrders:     ()   => API.get ('orders', { action: 'my_orders' }),
        updateStatus: (d)  => API.post('orders', { action: 'update_status' }, d),
        cancel:       (d)  => API.post('orders', { action: 'cancel' }, d),
    },

    // ── CUSTOMERS ───────────────────────────────────────
    customers: {
        list:   (p) => API.get ('customers', { action: 'list', ...p }),
        get:    (id)=> API.get ('customers', { action: 'get', id }),
        create: (d) => API.post('customers', { action: 'create' }, d),
        update: (d) => API.post('customers', { action: 'update' }, d),
        delete: (d) => API.post('customers', { action: 'delete' }, d),
        stats:  ()  => API.get ('customers', { action: 'stats' }),
    },

    // ── SCHEDULE ────────────────────────────────────────
    schedule: {
        getSlots:    (date) => API.get ('schedule', { action: 'get_slots', date }),
        bookSlot:    (d)    => API.post('schedule', { action: 'book_slot' }, d),
        releaseSlot: (d)    => API.post('schedule', { action: 'release_slot' }, d),
        blockDay:    (d)    => API.post('schedule', { action: 'block_day' }, d),
        adminView:   (p)    => API.get ('schedule', { action: 'admin_view', ...p }),
    },

    // ── NOTIFICATIONS ────────────────────────────────────
    notifications: {
        send:      (d)  => API.post('notifications', { action: 'send' }, d),
        list:      (p)  => API.get ('notifications', { action: 'list', ...p }),
        templates: ()   => API.get ('notifications', { action: 'templates' }),
    },

    // ── SETTINGS ────────────────────────────────────────
    settings: {
        get:            ()  => API.get ('settings', { action: 'get' }),
        update:         (d) => API.post('settings', { action: 'update' }, d),
        changePassword: (d) => API.post('settings', { action: 'change_password' }, d),
        dashboardStats: ()  => API.get ('settings', { action: 'dashboard_stats' }),
    },
};

// ============================================================
// ORDER FORM — replaces the WhatsApp-only submitOrder()
// Submits to backend AND opens WhatsApp confirmation
// ============================================================
async function submitOrderBackend() {
    const items = [];
    const itemTypeMap = {
        '5': 'knife', '7': 'axe_machete', '9': 'garden_tool', '0': 'pizza_cutter'
    };

    document.querySelectorAll('.order-item-row').forEach(row => {
        const priceVal = row.querySelector('select').value;
        const qty      = parseInt(row.querySelector('input').value) || 1;
        const text     = row.querySelector('select').options[row.querySelector('select').selectedIndex].text;
        const type     = itemTypeMap[priceVal] || (text.toLowerCase().includes('repair') ? 'repair' : 'other');
        items.push({ item_type: type, quantity: qty, description: text });
    });

    const payload = {
        customer_name:   document.getElementById('o-name')?.value || '',
        customer_phone:  document.getElementById('o-phone')?.value || '',
        service_type:    (document.getElementById('o-service')?.value || 'pickup_delivery').replace('-', '_'),
        pickup_address:  document.getElementById('o-address')?.value || '',
        scheduled_date:  document.getElementById('o-date')?.value || null,
        payment_method:  document.getElementById('o-payment')?.value === 'bitcoin' ? 'bitcoin_lightning' : 'cash',
        notes:           document.getElementById('o-notes')?.value || '',
        media_consent:   document.getElementById('o-consent')?.checked || false,
        items,
    };

    if (!payload.customer_name || !payload.customer_phone) {
        showToast('Please enter your name and phone number.');
        return;
    }

    // Show loading state
    const btn = event?.target;
    if (btn) { btn.textContent = 'Submitting...'; btn.disabled = true; }

    const result = await API.orders.create(payload);

    if (btn) { btn.textContent = 'Submit Order via WhatsApp'; btn.disabled = false; }

    if (result.success) {
        showToast(`Order ${result.data.order_number} created! ✓`);
        // Open WhatsApp with the message
        if (result.data.wa_link) window.open(result.data.wa_link, '_blank');
    } else {
        showToast(result.message || 'Error creating order.');
    }
}

// ============================================================
// SCHEDULE — load real availability from backend
// ============================================================
async function loadScheduleSlots(date) {
    if (!date) return;
    const result = await API.schedule.getSlots(date);
    if (!result.success) return;

    const container = document.getElementById('time-slots');
    if (!container) return;
    container.innerHTML = '';
    result.data.slots.forEach(slot => {
        const div = document.createElement('div');
        div.className = 'time-slot' + (slot.available ? '' : ' unavailable');
        div.textContent = slot.time;
        if (slot.available) div.onclick = () => selectSlot(div);
        container.appendChild(div);
    });
}

// Override the updateSlots function from index.html
function updateSlots() {
    const date = document.getElementById('sched-date')?.value;
    if (date) loadScheduleSlots(date);
}

// Override bookSlot to use backend
async function bookSlotBackend() {
    const date = document.getElementById('sched-date')?.value;
    const selectedEl = document.querySelector('.time-slot.selected');
    if (!selectedEl) { showToast('Please select a time slot.'); return; }
    if (!date) { showToast('Please select a date.'); return; }

    const result = await API.schedule.bookSlot({ date, time: selectedEl.textContent });
    if (result.success) {
        showToast(`Slot booked: ${date} at ${selectedEl.textContent} ✓`);
        const msg = `📅 *SCHEDULE REQUEST — BEE SHARP SV*\n\nDate: ${date}\nTime: ${selectedEl.textContent}\n\nI'd like to confirm my sharpening appointment.`;
        window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
    } else {
        showToast(result.message || 'Slot unavailable. Please choose another time.');
        loadScheduleSlots(date); // Refresh availability
    }
}

// ============================================================
// AUTH — customer login/register via backend
// ============================================================
async function backendLogin(formEl) {
    const login    = formEl.querySelector('[name=login]')?.value || formEl.querySelector('input[type=text]')?.value || '';
    const password = formEl.querySelector('input[type=password]')?.value || '';
    const result   = await API.auth.login({ login, password });

    if (result.success) {
        showToast('Welcome back! ✓');
        // Show customer dashboard
        document.querySelector('.auth-container').style.display = 'none';
        document.getElementById('customer-dashboard').style.display = 'block';
        loadMyOrders();
    } else {
        showToast(result.message || 'Login failed.');
    }
}

async function backendRegister(formEl) {
    const data = {
        first_name:    formEl.querySelector('[name=first_name]')?.value || '',
        last_name:     formEl.querySelector('[name=last_name]')?.value  || '',
        email:         formEl.querySelector('[name=email]')?.value      || '',
        whatsapp:      formEl.querySelector('[name=whatsapp]')?.value   || '',
        password:      formEl.querySelector('[name=password]')?.value   || '',
        address:       formEl.querySelector('[name=address]')?.value    || '',
        media_consent: formEl.querySelector('[name=media_consent]')?.checked || false,
    };
    const result = await API.auth.register(data);
    if (result.success) {
        showToast('Account created! ✓');
        document.querySelector('.auth-container').style.display = 'none';
        document.getElementById('customer-dashboard').style.display = 'block';
        loadMyOrders();
    } else {
        showToast(result.message || 'Registration failed.');
    }
}

// ============================================================
// CUSTOMER DASHBOARD — load real orders
// ============================================================
async function loadMyOrders() {
    const result = await API.orders.myOrders();
    if (!result.success) return;

    const tbody = document.querySelector('#customer-dashboard table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const statusClass = { complete:'badge-green', delivered:'badge-green', in_progress:'badge-orange', scheduled:'badge-blue', cancelled:'badge-gray', pending:'badge-gray' };

    result.data.orders.forEach(o => {
        const tr = document.createElement('tr');
        const cls = statusClass[o.status] || 'badge-gray';
        const label = o.status.replace('_', ' ');
        tr.innerHTML = `<td>${o.order_number}</td><td>${o.created_at?.slice(0,10)||''}</td><td>${o.item_count||0} items</td><td>$${parseFloat(o.total).toFixed(2)}</td><td><span class="badge ${cls}">${label}</span></td>`;
        tbody.appendChild(tr);
    });

    // Update stats
    const nums = document.querySelectorAll('#customer-dashboard .stat-num');
    if (nums[0]) nums[0].textContent = result.data.orders.length;
    if (nums[1]) nums[1].textContent = result.data.orders.reduce((s, o) => s + (parseInt(o.item_count)||0), 0);
}

// ============================================================
// ADMIN DASHBOARD — load live stats from backend
// ============================================================
async function loadAdminStats() {
    const result = await API.settings.dashboardStats();
    if (!result.success) return;
    const d = result.data;
    // Update stat cards if they exist
    const cards = document.querySelectorAll('.stat-card .num');
    if (cards[0]) cards[0].textContent = d.active_orders;
    if (cards[1]) cards[1].textContent = d.total_customers;
    if (cards[2]) cards[2].textContent = '$' + parseFloat(d.revenue_month).toFixed(0);
    if (cards[3]) cards[3].textContent = '₿ ' + d.btc_rate_month;
    if (cards[4]) cards[4].textContent = d.items_month;
}

async function loadAdminOrders(params = {}) {
    const result = await API.orders.list(params);
    if (!result.success) return;
    const tbody = document.querySelector('#orders-table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const statusClass = { complete:'badge-green', delivered:'badge-green', in_progress:'badge-orange', scheduled:'badge-blue', cancelled:'badge-gray', pending:'badge-gray', picked_up:'badge-orange', ready:'badge-green', out_for_delivery:'badge-orange' };
    const payClass = { cash:'badge-gray', bitcoin_lightning:'badge-orange', bitcoin_onchain:'badge-orange' };
    const payLabel = { cash:'Cash', bitcoin_lightning:'₿ Lightning', bitcoin_onchain:'₿ On-chain' };

    result.data.orders.forEach(o => {
        const sc = statusClass[o.status] || 'badge-gray';
        const pc = payClass[o.payment_method] || 'badge-gray';
        const pl = payLabel[o.payment_method] || o.payment_method;
        const sl = o.status.replace(/_/g,' ');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${o.order_number}</td>
            <td>${o.customer_name}</td>
            <td>${o.customer_phone}</td>
            <td>${o.item_count||0} items</td>
            <td>${o.service_type||''}</td>
            <td>${o.scheduled_date||o.created_at?.slice(0,10)||''}</td>
            <td><span class="badge ${pc}">${pl}</span></td>
            <td>$${parseFloat(o.total).toFixed(2)}</td>
            <td><span class="badge ${sc}">${sl}</span></td>
            <td>
              <div class="action-group">
                <button class="btn btn-sm btn-outline" onclick="adminViewOrder(${o.id})">View</button>
                <button class="btn btn-sm btn-primary" onclick="adminUpdateStatus(${o.id})">Update</button>
                <button class="btn btn-sm btn-outline" onclick="adminNotifyOrder(${o.id},'${o.customer_name}')">📨</button>
              </div>
            </td>`;
        tbody.appendChild(tr);
    });
}

async function adminViewOrder(id) {
    const result = await API.orders.get(id);
    if (!result.success) { showToast('Order not found'); return; }
    const o = result.data;
    document.getElementById('modal-order-title').textContent = 'Order ' + o.order_number;
    const steps = ['pending','scheduled','picked_up','in_progress','ready','out_for_delivery','delivered','complete'];
    const curIdx = steps.indexOf(o.status);
    const timelineHtml = steps.map((s, i) => `
        <div class="tl-item ${i <= curIdx ? 'done' : ''}">
          <h4>${s.replace(/_/g,' ')}</h4>
        </div>`).join('');
    document.getElementById('modal-order-body').innerHTML = `
        <p style="color:#999;margin-bottom:12px;">Customer: <strong style="color:#e0e0e0">${o.customer_name}</strong> · ${o.customer_phone}</p>
        <p style="color:#999;margin-bottom:16px;">Total: <strong style="color:#F7931A">$${parseFloat(o.total).toFixed(2)}</strong> · ${o.payment_method}</p>
        <div class="timeline">${timelineHtml}</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn btn-primary" onclick="adminUpdateStatus(${o.id});closeModal('order-modal')">Update Status</button>
          <a href="https://wa.me/${o.customer_phone.replace(/[^0-9]/g,'')}?text=${encodeURIComponent('🔪 Bee Sharp SV: Hello ' + o.customer_name + '! Regarding your order ' + o.order_number + '...')}" target="_blank" class="btn btn-outline">💬 WhatsApp</a>
          <button class="btn btn-outline" onclick="closeModal('order-modal')">Close</button>
        </div>`;
    openModal('order-modal');
}

function adminUpdateStatus(id) {
    document.getElementById('status-order-id').value = id;
    openModal('status-modal');
}

async function confirmStatusUpdateBackend() {
    const orderId  = parseInt(document.getElementById('status-order-id').value);
    const status   = document.getElementById('new-status').value;
    const note     = document.getElementById('status-notes').value;
    const sendWA   = document.getElementById('send-wa-notif').checked;

    const result = await API.orders.updateStatus({ order_id: orderId, status, note });
    if (result.success) {
        showToast('Status updated: ' + status);
        if (sendWA) {
            const msg = `🔪 *BEE SHARP SV UPDATE*\n\nYour order status has been updated to: *${status.replace(/_/g,' ')}*\n\nThank you for choosing Bee Sharp SV! ₿`;
            window.open('https://wa.me/50379522492?text=' + encodeURIComponent(msg), '_blank');
        }
        closeModal('status-modal');
        loadAdminOrders();
    } else {
        showToast(result.message || 'Update failed.');
    }
}

function adminNotifyOrder(id, name) {
    document.getElementById('notify-customer-name').value = name;
    document.getElementById('notify-msg').value = `Hello ${name}! This is Bee Sharp SV. Your order update: `;
    openModal('notify-modal');
}

async function loadNotifTemplates() {
    const result = await API.notifications.templates();
    if (!result.success) return;
    window._notifTemplates = result.data;
}

async function sendNotificationBackend() {
    const msg    = document.getElementById('notif-msg')?.value || '';
    const type   = document.querySelector('.notif-type.selected')?.id?.replace('nt-','') || 'custom';
    const recType= document.getElementById('recipient-type')?.value || 'single';
    const custEl = document.getElementById('notif-customer');
    const custId = custEl?.selectedOptions?.[0]?.getAttribute('data-id') || null;

    if (!msg.trim()) { showToast('Please enter a message.'); return; }

    const result = await API.notifications.send({
        type, recipient: recType, customer_id: custId, message: msg
    });

    if (result.success) {
        const links = result.data.wa_links || [];
        if (links.length > 0) {
            // Open first link, user can open others
            window.open(links[0].wa_link, '_blank');
            if (links.length > 1) showToast(`${links.length} recipients — opening first. Open others manually.`);
            else showToast('Notification sent! ✓');
        } else {
            window.open(result.data.fallback_wa_link, '_blank');
            showToast('Opening WhatsApp...');
        }
    } else {
        showToast(result.message || 'Failed to send notification.');
    }
}

// ============================================================
// ADMIN CUSTOMERS — load from backend
// ============================================================
async function loadAdminCustomers(params = {}) {
    const result = await API.customers.list(params);
    if (!result.success) return;
    const tbody = document.querySelector('#sec-customers table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const payBadge = { bitcoin_lightning:'badge-orange', bitcoin_onchain:'badge-orange', cash:'badge-gray', any:'badge-gray' };
    const payLabel = { bitcoin_lightning:'₿ Bitcoin', bitcoin_onchain:'₿ On-chain', cash:'Cash', any:'Any' };

    result.data.customers.forEach(c => {
        const pb = payBadge[c.payment_pref] || 'badge-gray';
        const pl = payLabel[c.payment_pref] || c.payment_pref;
        const phone = (c.whatsapp||'').replace(/[^0-9]/g,'');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${c.first_name} ${c.last_name}</td>
            <td>${c.whatsapp||''}</td>
            <td>${c.area||'-'}</td>
            <td>${c.order_count||0}</td>
            <td>$${parseFloat(c.total_spent||0).toFixed(2)}</td>
            <td><span class="badge ${pb}">${pl}</span></td>
            <td>${(c.last_order_at||'').slice(0,10)||'-'}</td>
            <td>
              <div class="action-group">
                <button class="btn btn-sm btn-outline" onclick="viewCustomer('${c.first_name} ${c.last_name}')">View</button>
                <button class="btn btn-sm btn-outline" onclick="notifyCustomer('${c.first_name} ${c.last_name}')">📨</button>
                <a href="https://wa.me/${phone}" target="_blank" class="btn btn-sm btn-outline">💬</a>
              </div>
            </td>`;
        tbody.appendChild(tr);
    });
}

// ============================================================
// INIT — detect which page we're on and load data
// ============================================================
document.addEventListener('DOMContentLoaded', async () => {
    const isAdmin = document.getElementById('sec-dashboard') !== null;

    if (isAdmin) {
        // Admin page initialisation
        const me = await API.auth.me();
        if (!me.success || me.data?.role !== 'admin') {
            // Show login form
            document.querySelector('.main').innerHTML = `
                <div class="topbar"><h3>Admin Login</h3></div>
                <div style="max-width:380px;margin:80px auto;padding:20px;">
                  <div class="section-card">
                    <h3>🔐 Admin Login</h3>
                    <div class="form-group"><label>Username or Email</label><input type="text" id="al-user"></div>
                    <div class="form-group"><label>Password</label><input type="password" id="al-pass" onkeydown="if(event.key==='Enter')adminDoLogin()"></div>
                    <button class="btn btn-primary" style="width:100%" onclick="adminDoLogin()">Sign In</button>
                    <p style="color:#555;font-size:0.8rem;margin-top:12px;text-align:center">Default after install: admin / [your chosen password]</p>
                  </div>
                </div>`;
        } else {
            loadAdminStats();
            loadAdminOrders();
            loadNotifTemplates();
        }

        // Override confirmStatusUpdate to use backend
        window.confirmStatusUpdate = confirmStatusUpdateBackend;

    } else {
        // Main site — check login state
        const me = await API.auth.me();
        if (me.success && me.data?.role === 'customer') {
            // Auto-show dashboard if already logged in
            const authContainer = document.querySelector('.auth-container');
            const dashboard     = document.getElementById('customer-dashboard');
            if (authContainer && dashboard) {
                authContainer.style.display = 'none';
                dashboard.style.display     = 'block';
                const nameEl = document.querySelector('#customer-dashboard .alert-success');
                if (nameEl) nameEl.textContent = '✅ Signed in as ' + me.data.name;
                loadMyOrders();
            }
        }

        // Override submitOrder to use backend
        window.submitOrder = submitOrderBackend;
        // Override bookSlot to use backend
        window.bookSlot = bookSlotBackend;
    }
});

// Admin login handler
async function adminDoLogin() {
    const user = document.getElementById('al-user')?.value || '';
    const pass = document.getElementById('al-pass')?.value || '';
    const result = await API.auth.adminLogin({ username: user, password: pass });
    if (result.success) {
        window.location.reload();
    } else {
        alert(result.message || 'Login failed.');
    }
}

async function adminLogout() {
    await API.auth.logout();
    if (confirm('Log out of admin panel?')) window.location.reload();
}
