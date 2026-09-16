/* ============================================================
   Kopitro Prediction Portal — Application Logic
   Backend : PHP Native + MySQL (PDO)
   Seluruh data diambil dari database via API, tanpa data dummy.
   ============================================================ */

// ===================== KONFIGURASI =====================
const API_BASE = 'api';
const PER_PAGE = 10;
const HARGA_PER_CUP = 18000;
const CHART_FONT = { family: 'Inter' };
const GRID_COLOR = 'rgba(127,85,57,0.08)';
const TOOLTIP_BG = '#2A1204';

// ===================== STATE GLOBAL =====================
const S = {
    user: null,
    isGuest: false,
    salesData: [],
    currentPage: 1,
    totalPages: 1,
    activeReport: 1,
    coef: { b0: 0, b1: 0, b2: 0, b3: 0, b4: 0 },
    charts: {},
    coefLoaded: false,
};

// ===================== UTILITAS =====================
const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);
const fmt = (n, d = 2) => Number(n).toFixed(d);
const fmtRp = (n) => new Intl.NumberFormat('id-ID').format(Math.round(n));

function showEl(id) { const e = document.getElementById(id); if (e) e.classList.remove('hidden'); }
function hideEl(id) { const e = document.getElementById(id); if (e) e.classList.add('hidden'); }

function htmlLoading(msg = 'Memuat data...') {
    return `<div class="flex flex-col items-center justify-center py-10"><div class="animate-spin w-8 h-8 border-4 border-coffee-200 border-t-coffee-700 rounded-full"></div><span class="mt-3 text-coffee-500 text-sm">${msg}</span></div>`;
}
function htmlEmpty(msg = 'Tidak ada data ditemukan') {
    return `<div class="flex flex-col items-center justify-center py-12 text-coffee-400"><svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg><span class="text-sm font-medium">${msg}</span></div>`;
}
function htmlError(msg) {
    return `<div class="flex flex-col items-center justify-center py-12 text-rose-500"><span class="text-3xl mb-2">⚠️</span><span class="text-sm font-medium">${msg}</span><button onclick="location.reload()" class="mt-3 text-xs underline hover:no-underline">Coba lagi</button></div>`;
}

function toast(msg, type = 'success') {
    let t = document.getElementById('app-toast');
    if (!t) { t = document.createElement('div'); t.id = 'app-toast'; document.body.appendChild(t); }
    const c = { success: 'bg-emerald-600', error: 'bg-rose-600', info: 'bg-coffee-700', warning: 'bg-amber-500' };
    t.className = `fixed top-20 right-4 z-[200] px-5 py-3 rounded-xl shadow-lg text-sm font-semibold text-white transition-all duration-300 ${c[type] || c.info}`;
    t.textContent = msg;
    clearTimeout(t._t);
    t._t = setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(120%)'; }, 3000);
}

function destroyChart(key) { if (S.charts[key]) { S.charts[key].destroy(); S.charts[key] = null; } }
function destroyAllCharts() { Object.keys(S.charts).forEach(destroyChart); }

function radioVal(name) { const c = document.querySelector(`input[name="${name}"]:checked`); return c ? parseInt(c.value) : 0; }

function calcY(x1, x2, x3, x4) {
    const c = S.coef;
    return c.b0 + (c.b1 * x1) + (c.b2 * x2) + (c.b3 * x3) + (c.b4 * x4);
}

function statusLabel(y) { return y >= 30 ? 'Tinggi' : y >= 20 ? 'Sedang' : 'Rendah'; }
function statusColor(y) { return y >= 30 ? 'text-emerald-600' : y >= 20 ? 'text-amber-600' : 'text-rose-600'; }

function animateNumber(el, from, to, dur = 600) {
    if (!el) return;
    const start = performance.now();
    const diff = to - from;
    (function step(now) {
        const p = Math.min((now - start) / dur, 1);
        const ease = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(from + diff * ease);
        if (p < 1) requestAnimationFrame(step);
    })(start);
}

// ===================== API WRAPPER =====================
async function api(endpoint, opts = {}) {
    const cfg = { credentials: 'include', headers: { 'Content-Type': 'application/json' }, ...opts };
    if (opts.body instanceof FormData) { delete cfg.headers['Content-Type']; cfg.body = opts.body; }
    else if (opts.body) { cfg.body = JSON.stringify(opts.body); }

    const res = await fetch(`${API_BASE}/${endpoint}`, cfg);
    if (res.status === 401) { handleSessionExpired(); throw new Error('Sesi berakhir'); }
    const json = await res.json();
    if (json.status === 'error') throw new Error(json.message || 'Kesalahan server');
    return json;
}

// ===================== AUTENTIKASI =====================
async function handleLogin(e) {
    e.preventDefault();
    hideEl('login-error');
    const btn = $('#login-btn');
    btn.disabled = true;
    btn.textContent = 'Memverifikasi...';

    try {
        const res = await api('login.php', {
            method: 'POST',
            body: { username: $('#login-username').value.trim(), password: $('#login-password').value.trim() }
        });
        S.user = res.data;
        S.isGuest = false;
        hideEl('login-screen');
        showEl('app-content');
        updateUserBadge();
        await initApp();
        toast('Selamat datang, ' + (res.data.nama || res.data.username || 'Admin'));
    } catch (err) {
        $('#login-error-msg').textContent = err.message || 'Gagal terhubung ke server';
        showEl('login-error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Masuk ke Sistem';
    }
}

function loginAsGuest() {
    S.isGuest = true;
    S.user = { username: 'Tamu', nama: 'Pengunjung' };
    hideEl('login-screen');
    showEl('app-content');
    updateUserBadge();
    initApp();
    toast('Masuk sebagai Pengunjung (mode baca saja)', 'info');
}

async function handleLogout() {
    try { if (!S.isGuest) await api('logout.php', { method: 'POST' }); } catch (_) {}
    S.user = null;
    S.isGuest = false;
    S.coefLoaded = false;
    S.coef = { b0: 0, b1: 0, b2: 0, b3: 0, b4: 0 };
    destroyAllCharts();
    showEl('login-screen');
    hideEl('app-content');
    $('#login-form').reset();
    hideEl('login-error');
}

function handleSessionExpired() {
    toast('Sesi Anda telah berakhir, silakan login kembali', 'warning');
    setTimeout(handleLogout, 500);
}

async function checkSession() {
    try {
        const res = await api('cek_session.php');
        if (res.status === 'success' && res.data) {
            S.user = res.data;
            S.isGuest = false;
            hideEl('login-screen');
            showEl('app-content');
            updateUserBadge();
            await initApp();
        } else { showEl('login-screen'); }
    } catch (_) { showEl('login-screen'); }
}

function updateUserBadge() {
    const b = $('#user-badge');
    if (b) b.textContent = S.isGuest ? 'Tamu' : (S.user?.nama || S.user?.username || 'Admin');
}

function guestGuard(action) {
    if (S.isGuest) { toast(`Mode tamu tidak dapat ${action}`, 'warning'); return true; }
    return false;
}

// ===================== NAVIGASI =====================
const TABS = ['dashboard', 'forecast', 'validation', 'data', 'reports', 'pwa'];

function switchTab(tab) {
    TABS.forEach(t => {
        const el = document.getElementById('tab-' + t);
        if (el) el.classList.add('hidden');
        const nav = document.getElementById('nav-' + t);
        if (nav) { nav.classList.remove('bg-coffee-800', 'text-white'); nav.classList.add('text-coffee-300'); }
    });
    const tabEl = document.getElementById('tab-' + tab);
    if (tabEl) tabEl.classList.remove('hidden');
    const navEl = document.getElementById('nav-' + tab);
    if (navEl) { navEl.classList.add('bg-coffee-800', 'text-white'); navEl.classList.remove('text-coffee-300'); }

    switch (tab) {
        case 'dashboard': loadDashboard(); break;
        case 'forecast': generateForecastRows(); break;
        case 'validation': loadValidation(); break;
        case 'data': loadDataset(); break;
        case 'reports': loadReport(S.activeReport); break;
    }
}

// ===================== INISIALISASI APLIKASI =====================
async function initApp() {
    await fetchCoefficients();
    await loadDashboard();
}

// ===================== KOEFISIEN =====================
async function fetchCoefficients() {
    try {
        const x1 = radioVal('x1'), x2 = radioVal('x2'), x3 = radioVal('x3');
        const x4 = parseFloat($('#x4-slider')?.value || 0.45);
        const res = await api('prediksi.php?action=create', {
            method: 'POST',
            body: { tanggal: new Date().toISOString().split('T')[0], cuaca: x1, hari_gajian: x2, promosi: x3, daring: x4 }
        });
        if (res.data) {
            if (res.data.koefisien) {
                S.coef = res.data.koefisien;
                S.coefLoaded = true;
            }
            if (res.data.prediksi !== undefined) {
                updatePredictionDisplay(res.data.prediksi);
            }
            updateEquationDisplay();
            updateAnalysisText();
        }
    } catch (err) {
        console.warn('Gagal mengambil koefisien awal:', err.message);
    }
}

function updateEquationDisplay() {
    const el = $('#equation-display');
    if (!el || !S.coefLoaded) return;
    const c = S.coef;
    el.innerHTML = `<span class="text-amber-400">Y</span> = <span class="text-emerald-400">${fmt(c.b0, 4)}</span> + (<span class="text-emerald-400">${fmt(c.b1, 4)}</span> &times; <span class="text-amber-300">X1</span>) + (<span class="text-emerald-400">${fmt(c.b2, 4)}</span> &times; <span class="text-amber-300">X2</span>) + (<span class="text-emerald-400">${fmt(c.b3, 4)}</span> &times; <span class="text-amber-300">X3</span>) + (<span class="text-emerald-400">${fmt(c.b4, 4)}</span> &times; <span class="text-amber-300">X4</span>)`;
}

function updateAnalysisText() {
    const el = $('#analysis-text');
    if (!el || !S.coefLoaded) return;
    const weights = [
        { name: 'Daring (X4)', val: Math.abs(S.coef.b4) },
        { name: 'Promosi (X3)', val: Math.abs(S.coef.b3) },
        { name: 'Cuaca (X1)', val: Math.abs(S.coef.b1) },
        { name: 'Gajian (X2)', val: Math.abs(S.coef.b2) },
    ].sort((a, b) => b.val - a.val);
    el.innerHTML = `Variabel <strong>${weights[0].name}</strong> dan <strong>${weights[1].name}</strong> memegang kendali bobot kenaikan paling signifikan pada persentase penjualan harian.`;
}

// ===================== DASHBOARD =====================
async function loadDashboard() {
    try {
        const res = await api('penjualan.php?action=read');
        const data = res.data || [];
        S.salesData = data;
        updateDashboardStats(data);
    } catch (err) {
        console.error('Gagal memuat statistik dashboard:', err.message);
    }
    if (S.coefLoaded) updateUIAndCalculate();
}

function updateDashboardStats(data) {
    if (!data.length) return;
    const total = data.reduce((s, d) => s + Number(d.penjualan), 0);
    const avg = total / data.length;
    // Statistik ditampilkan melalui laporan jika ada, di sini data sudah tersedia untuk chart
}

function updatePredictionDisplay(y) {
    const predEl = $('#prediction-y');
    if (predEl) predEl.textContent = fmt(y);
    const pct = Math.min(100, Math.round((y / 50) * 100));
    const pctEl = $('#target-percentage');
    if (pctEl) pctEl.textContent = pct + '%';
    const barEl = $('#target-progress-bar');
    if (barEl) barEl.style.width = pct + '%';
    const statusEl = $('#prediction-status-badge');
    if (statusEl) { statusEl.textContent = statusLabel(y); statusEl.className = 'font-bold ' + statusColor(y); }
}

function updateUIAndCalculate() {
    // Update visual radio
    ['x1', 'x2', 'x3'].forEach(name => {
        [0, 1].forEach(v => {
            const lbl = document.getElementById(`label-${name}-${v}`);
            if (!lbl) return;
            const inp = lbl.querySelector('input');
            if (inp && inp.checked) { lbl.classList.add('border-coffee-600', 'bg-coffee-100'); lbl.classList.remove('border-coffee-100'); }
            else { lbl.classList.remove('border-coffee-600', 'bg-coffee-100'); lbl.classList.add('border-coffee-100'); }
        });
    });

    const x4Val = parseFloat($('#x4-slider')?.value || 0.45);
    const x4Disp = $('#x4-display-val');
    if (x4Disp) x4Disp.textContent = Math.round(x4Val * 100) + '%';

    if (!S.coefLoaded) return;

    const x1 = radioVal('x1'), x2 = radioVal('x2'), x3 = radioVal('x3');
    const y = calcY(x1, x2, x3, x4Val);
    updatePredictionDisplay(y);
    renderSensitivityChart(x1, x2, x3);
}

function resetInputs() {
    const defaults = { x1: '1', x2: '0', x3: '1' };
    Object.entries(defaults).forEach(([n, v]) => { const i = document.querySelector(`input[name="${n}"][value="${v}"]`); if (i) i.checked = true; });
    const sl = $('#x4-slider'); if (sl) sl.value = 0.45;
    updateUIAndCalculate();
}

async function triggerPredictionAnimation() {
    if (guestGuard('menghitung prediksi')) return;
    const btn = $('#btn-predict');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div> Menghitung...';

    try {
        const x1 = radioVal('x1'), x2 = radioVal('x2'), x3 = radioVal('x3');
        const x4 = parseFloat($('#x4-slider')?.value || 0.45);
        const res = await api('prediksi.php?action=create', {
            method: 'POST',
            body: { tanggal: new Date().toISOString().split('T')[0], cuaca: x1, hari_gajian: x2, promosi: x3, daring: x4 }
        });
        if (res.data) {
            const y = res.data.prediksi;
            const prev = parseFloat($('#prediction-y').textContent) || 0;
            animateNumber($('#prediction-y'), prev, y);
            const pct = Math.min(100, Math.round((y / 50) * 100));
            setTimeout(() => {
                $('#target-percentage').textContent = pct + '%';
                $('#target-progress-bar').style.width = pct + '%';
                const st = $('#prediction-status-badge');
                st.textContent = statusLabel(y); st.className = 'font-bold ' + statusColor(y);
            }, 620);
            if (res.data.koefisien) { S.coef = res.data.koefisien; S.coefLoaded = true; updateEquationDisplay(); updateAnalysisText(); }
            toast('Prediksi berhasil: ' + fmt(y) + ' cup');
        }
    } catch (err) {
        toast('Gagal menghitung prediksi: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

// ===================== SENSITIVITY CHART =====================
function renderSensitivityChart(x1, x2, x3) {
    const ctx = document.getElementById('sensitivityChart');
    if (!ctx || !S.coefLoaded) return;

    const labels = [], data = [];
    for (let i = 0; i <= 100; i += 5) {
        const x4 = i / 100;
        labels.push(i + '%');
        data.push(calcY(x1, x2, x3, x4));
    }

    destroyChart('sensitivity');
    S.charts.sensitivity = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Volume Penjualan (Cup)', data,
                borderColor: '#7F5539', backgroundColor: 'rgba(127,85,57,0.1)',
                fill: true, tension: 0.4, pointRadius: 2, pointHoverRadius: 6, borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: TOOLTIP_BG, titleFont: CHART_FONT, bodyFont: CHART_FONT, callbacks: { label: (c) => fmt(c.parsed.y) + ' Cup' } } },
            scales: {
                x: { title: { display: true, text: 'Kontribusi Daring (X4)', font: { ...CHART_FONT, size: 11 } }, ticks: { font: { ...CHART_FONT, size: 10 }, maxTicksLimit: 10 }, grid: { color: GRID_COLOR } },
                y: { title: { display: true, text: 'Prediksi Y (Cup)', font: { ...CHART_FONT, size: 11 } }, ticks: { font: { ...CHART_FONT, size: 10 } }, grid: { color: GRID_COLOR } },
            }
        }
    });
}

// ===================== FORECAST =====================
function generateForecastRows() {
    const days = parseInt($('#forecast-days')?.value || 7);
    const tbody = $('#forecast-rows-tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    const today = new Date();

    for (let i = 0; i < days; i++) {
        const d = new Date(today); d.setDate(d.getDate() + i + 1);
        const dayName = d.toLocaleDateString('id-ID', { weekday: 'short' });
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-coffee-50/50';
        tr.innerHTML = `
            <td class="p-3 font-bold">${i + 1}<br><span class="font-normal text-coffee-400">${dayName}</span></td>
            <td class="p-3"><select class="fc-x1 bg-coffee-50 border border-coffee-200 rounded-lg p-1.5 text-xs w-full" onchange="recalcForecast()"><option value="1">☀️ Cerah</option><option value="0">🌧️ Hujan</option></select></td>
            <td class="p-3"><select class="fc-x2 bg-coffee-50 border border-coffee-200 rounded-lg p-1.5 text-xs w-full" onchange="recalcForecast()"><option value="0">💼 Bukan</option><option value="1">💵 Gajian</option></select></td>
            <td class="p-3"><select class="fc-x3 bg-coffee-50 border border-coffee-200 rounded-lg p-1.5 text-xs w-full" onchange="recalcForecast()"><option value="1">📢 Ada</option><option value="0">🔇 Tidak</option></select></td>
            <td class="p-3"><input type="range" class="fc-x4 w-full accent-coffee-700" min="0" max="1" step="0.01" value="0.45" oninput="recalcForecast();this.nextElementSibling.textContent=Math.round(this.value*100)+'%'"><span class="text-[10px] font-bold text-coffee-600">45%</span></td>
            <td class="p-3 text-right font-mono font-bold text-coffee-800 fc-y">—</td>`;
        tbody.appendChild(tr);
    }
    recalcForecast();
}

function recalcForecast() {
    const rows = $$('#forecast-rows-tbody tr');
    if (!rows.length) return;
    let total = 0;
    const labels = [], data = [];

    rows.forEach((row, i) => {
        const x1 = parseInt(row.querySelector('.fc-x1').value);
        const x2 = parseInt(row.querySelector('.fc-x2').value);
        const x3 = parseInt(row.querySelector('.fc-x3').value);
        const x4 = parseFloat(row.querySelector('.fc-x4').value);
        const y = S.coefLoaded ? calcY(x1, x2, x3, x4) : 0;
        total += y;
        labels.push('Hari ' + (i + 1));
        data.push(y);
        row.querySelector('.fc-y').textContent = y > 0 ? fmt(y) : '—';
    });

    const avg = rows.length ? total / rows.length : 0;
    const el1 = $('#forecast-total-cups'), el2 = $('#forecast-avg-cups'), el3 = $('#forecast-total-revenue');
    if (el1) el1.textContent = fmt(total, 1);
    if (el2) el2.textContent = fmt(avg, 1);
    if (el3) el3.textContent = fmtRp(total * HARGA_PER_CUP);
    renderForecastChart(labels, data);
}

function renderForecastChart(labels, data) {
    const ctx = document.getElementById('forecastChart');
    if (!ctx) return;
    destroyChart('forecast');
    S.charts.forecast = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Prediksi Cup', data,
                backgroundColor: data.map(v => v >= 30 ? 'rgba(16,185,129,0.7)' : v >= 20 ? 'rgba(245,158,11,0.7)' : 'rgba(239,68,68,0.7)'),
                borderRadius: 6, borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: TOOLTIP_BG, callbacks: { label: (c) => fmt(c.parsed.y) + ' Cup' } } },
            scales: { x: { ticks: { font: { ...CHART_FONT, size: 10 } }, grid: { display: false } }, y: { ticks: { font: { ...CHART_FONT, size: 10 } }, grid: { color: GRID_COLOR }, beginAtZero: true } }
        }
    });
}

// ===================== VALIDASI =====================
async function loadValidation() {
    const r2 = $('#metric-r2'), mape = $('#metric-mape'), mae = $('#metric-mae');
    if (r2) r2.textContent = 'Memuat...';
    if (mape) mape.textContent = 'Memuat...';
    if (mae) mae.textContent = 'Memuat...';

    try {
        const res = await api('validasi.php?action=read');
        const d = res.data;
        if (r2) r2.textContent = d.r_squared !== undefined ? fmt(d.r_squared, 4) : '—';
        if (mape) mape.textContent = d.mape !== undefined ? fmt(d.mape, 2) + '%' : '—';
        if (mae) mae.textContent = d.mae !== undefined ? fmt(d.mae, 2) : '—';
    } catch (err) {
        if (r2) r2.textContent = 'Error';
        if (mape) mape.textContent = 'Error';
        if (mae) mae.textContent = 'Error';
        toast('Gagal memuat metrik validasi: ' + err.message, 'error');
    }
    runValidationSimulation();
}

function runValidationSimulation() {
    const actual = parseFloat($('#val-actual')?.value);
    const x1 = parseInt($('#val-x1')?.value || 0);
    const x2 = parseInt($('#val-x2')?.value || 0);
    const x3 = parseInt($('#val-x3')?.value || 0);
    const x4 = parseFloat($('#val-x4')?.value || 0);

    const x4Lbl = $('#val-x4-lbl');
    if (x4Lbl) x4Lbl.textContent = Math.round(x4 * 100) + '%';

    const simA = $('#sim-actual'), simP = $('#sim-pred'), simD = $('#sim-deviation'), simAc = $('#sim-accuracy');

    if (isNaN(actual) || actual <= 0) {
        if (simA) simA.textContent = '—';
        if (simP) simP.textContent = S.coefLoaded ? fmt(calcY(x1, x2, x3, x4)) : '—';
        if (simD) { simD.textContent = '—'; simD.className = 'font-bold font-mono text-coffee-400'; }
        if (simAc) { simAc.textContent = '—'; simAc.className = 'font-bold text-coffee-400'; }
        return;
    }

    if (!S.coefLoaded) {
        if (simA) simA.textContent = actual;
        if (simP) simP.textContent = '—';
        if (simD) { simD.textContent = '—'; simD.className = 'font-bold font-mono text-coffee-400'; }
        if (simAc) { simAc.textContent = '—'; simAc.className = 'font-bold text-coffee-400'; }
        return;
    }

    const pred = calcY(x1, x2, x3, x4);
    const dev = actual - pred;
    const acc = Math.max(0, (1 - Math.abs(dev) / actual) * 100);

    if (simA) simA.textContent = actual;
    if (simP) simP.textContent = fmt(pred);
    if (simD) {
        simD.textContent = (dev >= 0 ? '+' : '') + fmt(dev) + ' Cup';
        simD.className = 'font-bold font-mono ' + (Math.abs(dev) <= 3 ? 'text-emerald-600' : 'text-rose-600');
    }
    if (simAc) {
        simAc.textContent = fmt(acc, 1) + '%';
        simAc.className = 'font-bold ' + (acc >= 90 ? 'text-emerald-600' : acc >= 70 ? 'text-amber-600' : 'text-rose-600');
    }
}

function saveValidationToStats() {
    var actual = parseFloat(document.getElementById('val-actual').value);
    if (isNaN(actual) || actual <= 0) {
        showToast('Masukkan nilai penjualan aktual yang valid', 'warning');
        return;
    }
    var btn = document.getElementById('btn-save-val');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    /* Hitung prediksi dari input simulator */
    var x1 = parseInt(document.getElementById('val-x1').value || 0);
    var x2 = parseInt(document.getElementById('val-x2').value || 0);
    var x3 = parseInt(document.getElementById('val-x3').value || 0);
    var x4 = parseFloat(document.getElementById('val-x4').value || 0);
    var pred = Math.max(0, hitungY(x1, x2, x3, x4));

    /* Hitung MAE dan MAPE untuk data point ini */
    var mae = Math.abs(actual - pred);
    var mape = actual !== 0 ? (Math.abs(actual - pred) / actual) * 100 : 0;

    /* Kirim ke validasi.php dengan field yang benar */
    apiFetch('validasi.php?action=create', {
        method: 'POST',
        body: JSON.stringify({
            r2: 0,
            mae: mae.toFixed(2),
            mape: mape.toFixed(2)
        })
    }).then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Simpan Sebagai Validasi Baru';
        if (res.success) {
            showToast('Validasi disimpan: Aktual=' + actual + ', Prediksi=' + pred.toFixed(1) + ', MAPE=' + mape.toFixed(2) + '%', 'success');
            loadValidationMetrics();
        } else {
            showToast('Gagal menyimpan: ' + (res.message || 'Unknown error'), 'error');
        }
    }).catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Simpan Sebagai Validasi Baru';
        showToast('Gagal terhubung ke server saat menyimpan', 'error');
    });
}

// ===================== DATASET CRUD =====================
async function loadDataset(page) {
    if (page === undefined) page = S.currentPage;
    S.currentPage = page;
    const tbody = $('#dataset-tbody');
    if (!tbody) return;
    tbody.innerHTML = htmlLoading('Memuat dataset...');

    try {
        const res = await api(`penjualan.php?action=read&page=${page}&per_page=${PER_PAGE}`);
        const data = res.data || [];
        const total = res.total || data.length;
        S.totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
        S.salesData = data;

        if (!data.length) {
            tbody.innerHTML = htmlEmpty('Belum ada data penjualan');
            $('#dataset-total-info').textContent = 'Tidak ada data';
            $('#dataset-pagination').innerHTML = '';
            return;
        }

        tbody.innerHTML = data.map((d, i) => {
            const no = (page - 1) * PER_PAGE + i + 1;
            let pred = S.coefLoaded ? calcY(d.cuaca, d.hari_gajian, d.promosi, d.daring) : 0;
            const sel = d.penjualan - pred;
            const acc = d.penjualan > 0 ? Math.max(0, (1 - Math.abs(sel) / d.penjualan) * 100) : 0;
            const sc = acc >= 90 ? 'text-emerald-600' : acc >= 70 ? 'text-amber-600' : 'text-rose-600';
            const st = acc >= 90 ? 'Akurat' : acc >= 70 ? 'Cukup' : 'Tidak Akurat';
            return `<tr class="hover:bg-coffee-50/50 transition-colors">
                <td class="p-3 font-bold text-coffee-800">${no}</td>
                <td class="p-3">${d.tanggal}</td>
                <td class="p-3 font-bold text-coffee-900">${d.penjualan}</td>
                <td class="p-3">${d.cuaca == 1 ? '☀️ Cerah' : '🌧️ Hujan'}</td>
                <td class="p-3">${d.hari_gajian == 1 ? '💵 Ya' : '💼 Tidak'}</td>
                <td class="p-3">${d.promosi == 1 ? '📢 Ada' : '🔇 Tidak'}</td>
                <td class="p-3">${Math.round(d.daring * 100)}%</td>
                <td class="p-3 text-center font-mono">${pred > 0 ? fmt(pred) : '—'}</td>
                <td class="p-3 text-center font-mono ${sel >= 0 ? 'text-emerald-600' : 'text-rose-600'}">${pred > 0 ? (sel >= 0 ? '+' : '') + fmt(sel) : '—'}</td>
                <td class="p-3 text-center font-bold ${sc}">${pred > 0 ? st : '—'}</td>
                <td class="p-3 text-right whitespace-nowrap">
                    <button onclick="openEditModal(${d.id})" class="text-coffee-500 hover:text-coffee-800 text-xs font-semibold mr-2" title="Edit">✏️</button>
                    <button onclick="confirmDelete(${d.id})" class="text-rose-400 hover:text-rose-700 text-xs font-semibold" title="Hapus">🗑️</button>
                </td>
            </tr>`;
        }).join('');

        $('#dataset-total-info').textContent = `Menampilkan ${data.length} dari ${total} data`;
        renderPagination();
    } catch (err) {
        tbody.innerHTML = htmlError(err.message);
        toast('Gagal memuat dataset: ' + err.message, 'error');
    }
}

function renderPagination() {
    const c = $('#dataset-pagination');
    if (!c) return;
    let h = '';
    for (let i = 1; i <= S.totalPages; i++) {
        const active = i === S.currentPage;
        h += `<button onclick="loadDataset(${i})" class="px-3 py-1 rounded-lg text-xs font-bold transition ${active ? 'bg-coffee-700 text-white' : 'bg-coffee-100 text-coffee-600 hover:bg-coffee-200'}">${i}</button>`;
    }
    c.innerHTML = h;
}

function openAddModal() {
    if (guestGuard('menambah data')) return;
    $('#modal-title').textContent = 'Tambah Data Penjualan Baru';
    $('#modal-id').value = '';
    $('#modal-tanggal').value = new Date().toISOString().split('T')[0];
    $('#modal-penjualan').value = '';
    $('#modal-cuaca').value = '1';
    $('#modal-gajian').value = '0';
    $('#modal-promo').value = '1';
    $('#modal-daring').value = '0.45';
    $('#modal-daring-lbl').textContent = '45%';
    showEl('crud-modal');
}

function openEditModal(id) {
    if (guestGuard('mengubah data')) return;
    const item = S.salesData.find(d => d.id === id);
    if (!item) { toast('Data tidak ditemukan di cache', 'error'); return; }
    $('#modal-title').textContent = 'Edit Data Penjualan';
    $('#modal-id').value = item.id;
    $('#modal-tanggal').value = item.tanggal;
    $('#modal-penjualan').value = item.penjualan;
    $('#modal-cuaca').value = item.cuaca;
    $('#modal-gajian').value = item.hari_gajian;
    $('#modal-promo').value = item.promosi;
    $('#modal-daring').value = item.daring;
    $('#modal-daring-lbl').textContent = Math.round(item.daring * 100) + '%';
    showEl('crud-modal');
}

function closeModal() { hideEl('crud-modal'); }

async function saveSale() {
    const id = $('#modal-id').value;
    const payload = {
        tanggal: $('#modal-tanggal').value,
        penjualan: parseFloat($('#modal-penjualan').value),
        cuaca: parseInt($('#modal-cuaca').value),
        hari_gajian: parseInt($('#modal-gajian').value),
        promosi: parseInt($('#modal-promo').value),
        daring: parseFloat($('#modal-daring').value),
    };
    if (!payload.tanggal || isNaN(payload.penjualan) || payload.penjualan < 0) {
        toast('Lengkapi semua field dengan benar', 'warning'); return;
    }

    const btn = $('#modal-save-btn');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        if (id) {
            await api('penjualan.php?action=update', { method: 'POST', body: { id: parseInt(id), ...payload } });
            toast('Data berhasil diperbarui');
        } else {
            await api('penjualan.php?action=create', { method: 'POST', body: payload });
            toast('Data berhasil ditambahkan');
        }
        closeModal();
        await loadDataset(S.currentPage);
    } catch (err) {
        toast('Gagal menyimpan: ' + err.message, 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan Data';
    }
}

function confirmDelete(id) {
    if (guestGuard('menghapus data')) return;
    if (!confirm('Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.')) return;
    deleteSale(id);
}

async function deleteSale(id) {
    try {
        await api('penjualan.php?action=delete', { method: 'POST', body: { id } });
        toast('Data berhasil dihapus');
        const needPrevPage = S.salesData.length === 1 && S.currentPage > 1;
        await loadDataset(needPrevPage ? S.currentPage - 1 : S.currentPage);
    } catch (err) {
        toast('Gagal menghapus: ' + err.message, 'error');
    }
}

async function downloadCSV() {
    try {
        const res = await api('penjualan.php?action=read&per_page=9999');
        const data = res.data || [];
        if (!data.length) { toast('Tidak ada data untuk diekspor', 'warning'); return; }

        const hdr = ['No', 'Tanggal', 'Penjualan(Y)', 'Cuaca(X1)', 'Gajian(X2)', 'Promosi(X3)', 'Daring(X4)'];
        const rows = data.map((d, i) => [i + 1, d.tanggal, d.penjualan, d.cuaca, d.hari_gajian, d.promosi, d.daring].join(','));
        const blob = new Blob(['\uFEFF' + [hdr.join(','), ...rows].join('\n')], { type: 'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'dataset_kopitro_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
        toast('CSV berhasil diunduh');
    } catch (err) {
        toast('Gagal ekspor CSV: ' + err.message, 'error');
    }
}

// ===================== LAPORAN =====================
function switchReport(n) {
    S.activeReport = n;
    [1, 2, 3, 4].forEach(i => {
        const btn = $(`#btn-report-${i}`);
        if (btn) btn.className = i === n
            ? 'w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition duration-200 flex items-center gap-2.5 bg-coffee-800 text-white'
            : 'w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold text-coffee-600 hover:bg-coffee-100 transition duration-200 flex items-center gap-2.5';
        const panel = $(`#report-panel-${i}`);
        if (panel) panel.classList.toggle('hidden', i !== n);
    });
    loadReport(n);
}

async function loadReport(n) {
    const jenisMap = { 1: 'penjualan', 2: 'prediksi', 3: 'validasi', 4: 'keuangan' };
    const loadEl = $(`#report-${n}-loading`);
    const contentEl = $(`#report-${n}-content`);

    if (loadEl) { loadEl.classList.remove('hidden'); loadEl.innerHTML = htmlLoading('Memuat laporan...'); }
    if (contentEl) { contentEl.classList.add('hidden'); contentEl.innerHTML = ''; }

    try {
        const res = await api(`laporan.php?jenis=${jenisMap[n]}`);
        if (loadEl) loadEl.classList.add('hidden');
        if (contentEl) contentEl.classList.remove('hidden');
        renderReport(n, res.data);
    } catch (err) {
        if (loadEl) { loadEl.classList.remove('hidden'); loadEl.innerHTML = htmlError('Gagal memuat: ' + err.message); }
        toast('Gagal memuat laporan: ' + err.message, 'error');
    }
}

function renderReport(n, data) {
    const contentEl = $(`#report-${n}-content`);
    if (!contentEl || !data) { if (contentEl) contentEl.innerHTML = htmlEmpty('Data laporan kosong'); return; }

    switch (n) {
        case 1: renderReportPenjualan(contentEl, data); break;
        case 2: renderReportPrediksi(contentEl, data); break;
        case 3: renderReportValidasi(contentEl, data); break;
        case 4: renderReportKeuangan(contentEl, data); break;
    }
}

function renderReportPenjualan(el, d) {
    const cards = [
        { l: 'Total Penjualan', v: (d.total || 0) + ' Cup', c: 'coffee-900' },
        { l: 'Rata-Rata Harian', v: fmt(d.rata_rata || 0) + ' Cup', c: 'coffee-700' },
        { l: 'Tertinggi', v: (d.tertinggi || 0) + ' Cup', c: 'emerald-600' },
        { l: 'Terendah', v: (d.terendah || 0) + ' Cup', c: 'rose-600' },
    ];
    let html = `<div class="grid grid-cols-2 md:grid-cols-4 gap-4">${cards.map(c => `<div class="bg-coffee-50 p-4 rounded-xl border border-coffee-100"><span class="text-[10px] text-coffee-500 font-bold uppercase block">${c.l}</span><span class="text-xl font-black text-${c.c}">${c.v}</span></div>`).join('')}</div>`;

    if (d.chart && d.chart.labels) {
        html += `<div class="relative h-[300px]"><canvas id="rptChart1"></canvas></div>`;
    }
    el.innerHTML = html;

    if (d.chart && d.chart.labels) {
        destroyChart('rpt1');
        const ctx = document.getElementById('rptChart1');
        if (ctx) {
            S.charts.rpt1 = new Chart(ctx, {
                type: 'bar',
                data: { labels: d.chart.labels, datasets: [{ label: 'Penjualan (Cup)', data: d.chart.data, backgroundColor: 'rgba(127,85,57,0.7)', borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: GRID_COLOR } }, x: { grid: { display: false } } } }
            });
        }
    }
}

function renderReportPrediksi(el, d) {
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
    if (d.distribusi) {
        html += `<div class="relative h-[300px]"><canvas id="rptChart2a"></canvas></div>`;
    }
    if (d.tren) {
        html += `<div class="relative h-[300px]"><canvas id="rptChart2b"></canvas></div>`;
    }
    html += '</div>';
    if (d.tabel && d.tabel.length) {
        html += `<div class="overflow-x-auto"><table class="w-full text-left text-xs border-collapse"><thead><tr class="bg-coffee-50 text-coffee-700 font-bold uppercase border-b border-coffee-100">${d.tabelHeader ? d.tabelHeader.map(h => `<th class="p-3">${h}</th>`).join('') : '<th class="p-3">Data</th>'}</tr></thead><tbody class="divide-y divide-coffee-100">${d.tabel.map(r => `<tr class="hover:bg-coffee-50/50">${Array.isArray(r) ? r.map(v => `<td class="p-3">${v}</td>`).join('') : `<td class="p-3">${JSON.stringify(r)}</td>`}</tr>`).join('')}</tbody></table></div>`;
    }
    el.innerHTML = html;

    if (d.distribusi) {
        destroyChart('rpt2a');
        const ctx = document.getElementById('rptChart2a');
        if (ctx) {
            S.charts.rpt2a = new Chart(ctx, {
                type: 'doughnut',
                data: { labels: d.distribusi.labels, datasets: [{ data: d.distribusi.data, backgroundColor: ['#7F5539', '#B08968', '#DDB892', '#E6CCB2', '#9C6644'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { ...CHART_FONT, size: 11 } } } } }
            });
        }
    }
    if (d.tren) {
        destroyChart('rpt2b');
        const ctx = document.getElementById('rptChart2b');
        if (ctx) {
            S.charts.rpt2b = new Chart(ctx, {
                type: 'line',
                data: { labels: d.tren.labels, datasets: [{ label: 'Prediksi', data: d.tren.data, borderColor: '#7F5539', backgroundColor: 'rgba(127,85,57,0.1)', fill: true, tension: 0.4, borderWidth: 2 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: GRID_COLOR } }, x: { grid: { display: false } } } }
            });
        }
    }
}

function renderReportValidasi(el, d) {
    const metrics = [
        { l: 'R²', v: d.r_squared !== undefined ? fmt(d.r_squared, 4) : '—', c: 'emerald-600' },
        { l: 'MAPE', v: d.mape !== undefined ? fmt(d.mape, 2) + '%' : '—', c: 'blue-600' },
        { l: 'MAE', v: d.mae !== undefined ? fmt(d.mae, 2) : '—', c: 'orange-600' },
    ];
    let html = `<div class="grid grid-cols-3 gap-4 mb-6">${metrics.map(m => `<div class="bg-coffee-50 p-4 rounded-xl border border-coffee-100 text-center"><span class="text-[10px] text-coffee-500 font-bold uppercase block">${m.l}</span><span class="text-2xl font-black text-${m.c}">${m.v}</span></div>`).join('')}</div>`;

    if (d.chart && d.chart.labels) {
        html += `<div class="relative h-[300px]"><canvas id="rptChart3"></canvas></div>`;
    }
    el.innerHTML = html;

    if (d.chart && d.chart.labels) {
        destroyChart('rpt3');
        const ctx = document.getElementById('rptChart3');
        if (ctx) {
            const datasets = [];
            if (d.chart.data_aktual) datasets.push({ label: 'Aktual', data: d.chart.data_aktual, borderColor: '#7F5539', backgroundColor: 'rgba(127,85,57,0.1)', tension: 0.3, borderWidth: 2 });
            if (d.chart.data_prediksi) datasets.push({ label: 'Prediksi', data: d.chart.data_prediksi, borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.3, borderWidth: 2, borderDash: [5, 3] });
            if (d.chart.data) datasets.push({ label: 'Deviasi', data: d.chart.data, type: 'bar', backgroundColor: 'rgba(239,68,68,0.5)', borderRadius: 3 });
            S.charts.rpt3 = new Chart(ctx, {
                type: 'line',
                data: { labels: d.chart.labels, datasets },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { font: { ...CHART_FONT, size: 11 } } } }, scales: { y: { grid: { color: GRID_COLOR } }, x: { grid: { display: false } } } }
            });
        }
    }
}

function renderReportKeuangan(el, d) {
    const cards = [
        { l: 'Total Pendapatan', v: 'Rp ' + fmtRp(d.total_pendapatan || 0), c: 'emerald-600' },
        { l: 'Rata-Rata Harian', v: 'Rp ' + fmtRp(d.rata_harian || 0), c: 'coffee-800' },
        { l: 'Potensi Maksimal', v: 'Rp ' + fmtRp(d.potensi_maksimal || 0), c: 'amber-600' },
        { l: 'Efisiensi Model', v: (d.efisiensi !== undefined ? fmt(d.efisiensi, 1) : '—') + '%', c: 'blue-600' },
    ];
    let html = `<div class="grid grid-cols-2 md:grid-cols-4 gap-4">${cards.map(c => `<div class="bg-coffee-50 p-4 rounded-xl border border-coffee-100"><span class="text-[10px] text-coffee-500 font-bold uppercase block">${c.l}</span><span class="text-lg font-black text-${c.c}">${c.v}</span></div>`).join('')}</div>`;

    if (d.chart && d.chart.labels) {
        html += `<div class="relative h-[300px]"><canvas id="rptChart4"></canvas></div>`;
    }
    el.innerHTML = html;

    if (d.chart && d.chart.labels) {
        destroyChart('rpt4');
        const ctx = document.getElementById('rptChart4');
        if (ctx) {
            const datasets = [];
            if (d.chart.data_aktual) datasets.push({ label: 'Pendapatan Aktual', data: d.chart.data_aktual, backgroundColor: 'rgba(127,85,57,0.7)', borderRadius: 4 });
            if (d.chart.data_prediksi) datasets.push({ label: 'Proyeksi', data: d.chart.data_prediksi, backgroundColor: 'rgba(16,185,129,0.5)', borderRadius: 4 });
            if (d.chart.data) datasets.push({ label: 'Pendapatan', data: d.chart.data, backgroundColor: 'rgba(127,85,57,0.7)', borderRadius: 4 });
            S.charts.rpt4 = new Chart(ctx, {
                type: 'bar',
                data: { labels: d.chart.labels, datasets },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { font: { ...CHART_FONT, size: 11 } } } }, scales: { y: { grid: { color: GRID_COLOR }, beginAtZero: true }, x: { grid: { display: false } } } }
            });
        }
    }
}

// ===================== PRINT =====================
function printCurrentReport() {
    const panel = $(`#report-panel-${S.activeReport}`);
    if (!panel) { toast('Tidak ada laporan untuk dicetak', 'warning'); return; }
    const printArea = $('#print-area');
    printArea.innerHTML = `<div style="padding:20px;font-family:Inter,sans-serif;">
        <h2 style="font-size:20px;font-weight:800;margin-bottom:4px;">Kopitro Prediction Portal</h2>
        <p style="font-size:12px;color:#666;margin-bottom:20px;">Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
        <hr style="margin-bottom:20px;">
        ${panel.innerHTML}
    </div>`;
    window.print();
}

// ===================== EVENT LISTENER TAMBAHAN =====================
function setupListeners() {
    // Validasi: input aktual juga trigger simulasi
    const valActual = $('#val-actual');
    if (valActual) valActual.addEventListener('input', runValidationSimulation);

    // Tutup modal dengan klik backdrop
    const modal = $('#crud-modal');
    if (modal) {
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    }

    // Keyboard: Escape tutup modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
}

// ===================== INIT =====================
document.addEventListener('DOMContentLoaded', () => {
    setupListeners();
    checkSession();
});