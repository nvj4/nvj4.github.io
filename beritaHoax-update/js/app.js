/**
 * FaktaKu - app.js
 * Platform Deteksi Hoax
 * Terintegrasi dengan backend PHP + Python ML
 */

(function () {
    'use strict';

    // ════════════════════════════════════════
    // KONFIGURASI ENDPOINT
    // ════════════════════════════════════════
   const API = {
    LOGIN:          'api/login.php',
    REGISTER:       'api/register.php',
    DETECT:         'api/detect_search.php',
    GET_HISTORY:    'api/get_history.php',
    DELETE_HISTORY: 'api/get_history.php?action=delete',
    CLEAR_HISTORY:  'api/get_history.php?action=clear',
    STATISTICS:     'api/get_statistics.php'
};

    const STORAGE_KEYS = {
        USER:   'faktaku_user',
        THEME:  'faktaku_theme',
        WELCOME:'faktaku_welcome_seen'
    };

    // ════════════════════════════════════════
    // STATE APLIKASI
    // ════════════════════════════════════════
    const state = {
        user: null,
        theme: 'light',
        detecting: false
    };

    // ════════════════════════════════════════
    // HELPER
    // ════════════════════════════════════════
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    async function apiJSON(url, opts = {}) {
        try {
            const res = await fetch(url, { headers: { 'Content-Type': 'application/json', ...opts.headers }, ...opts });
            return await res.json();
        } catch (err) {
            console.error('[API Error]', url, err);
            return { success: false, message: 'Gagal terhubung ke server' };
        }
    }

    async function apiForm(url, data) {
        try {
            const fd = new FormData();
            Object.entries(data).forEach(([k, v]) => fd.append(k, v));
            const res = await fetch(url, { method: 'POST', body: fd });
            return await res.json();
        } catch (err) {
            console.error('[API Error]', url, err);
            return { success: false, message: 'Gagal terhubung ke server' };
        }
    }

    function escapeHTML(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function formatDate(iso) {
        try { return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); }
        catch { return iso || '-'; }
    }

    function getInitials(name) {
        return (name || 'U').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
    }

    // ════════════════════════════════════════
    // TOAST NOTIFICATION
    // ════════════════════════════════════════
    function toast(msg, type = 'info') {
        $('.toast-notif')?.remove();
        const c = { success:'#10b981', error:'#ef4444', warning:'#f59e0b', info:'#6366f1' }[type] || '#6366f1';
        const icons = { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation', info:'fa-circle-info' }[type] || 'fa-circle-info';
        const el = document.createElement('div');
        el.className = 'toast-notif';
        Object.assign(el.style, { position:'fixed', top:'100px', right:'24px', zIndex:'11000', background:c, color:'#fff', padding:'16px 26px', borderRadius:'14px', display:'flex', alignItems:'center', gap:'12px', fontSize:'14px', fontWeight:'600', fontFamily:"'Inter',sans-serif", boxShadow:'0 12px 36px rgba(0,0,0,0.22)', transform:'translateX(140%)', transition:'transform 0.45s cubic-bezier(.34,1.56,.64,1)', maxWidth:'420px', lineHeight:'1.4' });
        el.innerHTML = `<i class="fas ${icons}" style="font-size:18px"></i><span>${msg}</span>`;
        document.body.appendChild(el);
        requestAnimationFrame(() => el.style.transform = 'translateX(0)');
        setTimeout(() => { el.style.transform = 'translateX(140%)'; setTimeout(() => el.remove(), 500); }, 3800);
    }

    // ════════════════════════════════════════
    // TEMA DARK / LIGHT
    // ════════════════════════════════════════
    function initTheme() {
        state.theme = localStorage.getItem(STORAGE_KEYS.THEME) || 'light';
        renderTheme();
    }

    function renderTheme() {
        document.documentElement.setAttribute('data-theme', state.theme);
        const icon = $('.theme-toggle-btn i');
        if (icon) icon.className = state.theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    function toggleTheme() {
        state.theme = state.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem(STORAGE_KEYS.THEME, state.theme);
        renderTheme();
    }

    // ════════════════════════════════════════
    // WELCOME SPLASH SCREEN
    // ════════════════════════════════════════
    function initWelcome() {
        const modal = $('#welcomeModal');
        if (!modal) return;
        if (!localStorage.getItem(STORAGE_KEYS.WELCOME)) setTimeout(() => modal.classList.add('active'), 400);
    }

    function closeWelcome() {
        const m = $('#welcomeModal');
        if (m) { m.classList.remove('active'); localStorage.setItem(STORAGE_KEYS.WELCOME, '1'); }
    }

    // ════════════════════════════════════════
    // NAVIGASI SECTION
    // ════════════════════════════════════════
    function showSection(section, event) {
        if (event) event.preventDefault();

        // Update active state di nav
        $$('.nav-link').forEach(l => {
            l.classList.remove('active');
            const text = l.textContent.trim().toLowerCase();
            if (text === section || (text === 'beranda' && section === 'beranda') || (text === 'berita' && section === 'berita') || (text === 'laporan' && section === 'laporan')) {
                l.classList.add('active');
            }
        });

        // Sembunyikan semua section yang bisa di-switch
        const ids = ['homeSection', 'newsSection', 'reportSection', 'aboutSection'];
        ids.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
        $$('.about-section').forEach(s => { s.style.display = 'none'; s.classList.remove('active'); });

        // Tampilkan yang diminta
        if (section === 'beranda') {
            const home = document.getElementById('homeSection') || $('.home-section');
            if (home) home.style.display = 'block';
        } else if (section === 'berita') {
            const news = document.getElementById('newsSection') || $('.news-section');
            if (news) { news.style.display = 'block'; loadNewsFeed(); }
        } else if (section === 'laporan') {
            const report = document.getElementById('reportSection') || $('.report-section');
            if (report) report.style.display = 'block';
        } else if (section === 'tentang') {
            const about = document.getElementById('aboutSection') || $('.about-section');
            if (about) { about.style.display = 'block'; about.classList.add('active'); }
        }

        closeMobileMenu();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ════════════════════════════════════════
    // MODAL BUKA / TUTUP
    // ════════════════════════════════════════
    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            if (id === 'historyModal') loadHistoryInside();
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    }

    function closeAllModals() {
        $$('.modal.active').forEach(m => m.classList.remove('active'));
    }

    // ════════════════════════════════════════
    // AKUN & AUTENTIKASI
    // ════════════════════════════════════════
    function openAccountEntry(e) {
        if (e) e.preventDefault();
        openAuth('login');
    }

function openProfileSettings(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    
    /* Tutup dropdown kalau kebuka */
    const profileEl = document.getElementById('userProfile');
    if (profileEl) profileEl.classList.remove('menu-open');

    /* Pastikan elemen profile page ada */
    const pp = document.getElementById('profilePage');
    if (!pp) return;

    /* Isi data user */
    const nameLg  = document.getElementById('profNameLg');
    const emailLg = document.getElementById('profEmailLg');
    const avatarLg = document.getElementById('profAvatarLg');
    const inputName = document.getElementById('profEditName');
    const inputPass = document.getElementById('profEditPass');

    if (state.user) {
        const nm = state.user.name || 'User';
        const em = state.user.email || '';
        const ini = (nm.trim().split(/\s+/).map(w=>w[0]).join('')).substring(0,2).toUpperCase();

        if (nameLg)   nameLg.textContent   = nm;
        if (emailLg)  emailLg.textContent  = em || 'Email tidak tersedia';
        if (avatarLg) avatarLg.textContent = ini;
        if (inputName) inputName.value     = nm;
    }
    if (inputPass) inputPass.value = '';

    /* Pasang event handler sekali saja */
    if (!pp._bound) {
        pp._bound = true;

        document.getElementById('btnProfBack')?.addEventListener('click', closeProfilePage);
        document.getElementById('btnProfCancel')?.addEventListener('click', closeProfilePage);

        document.getElementById('btnProfLogout')?.addEventListener('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            closeProfilePage();
            setTimeout(logout, 300);
        });

        document.getElementById('btnProfSave')?.addEventListener('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();

            const newName = document.getElementById('profEditName').value.trim();
            const newPass = document.getElementById('profEditPass').value.trim();

            if (!newName) {
                showProfileToast('Nama tidak boleh kosong', 'error');
                return;
            }

            /* Update state & localStorage */
            if (state.user) {
                state.user.name = newName;
                if (newPass) state.user.password = newPass;
            }
            try { localStorage.setItem('faktaKu_user', JSON.stringify(state.user)); } catch(err) {}

            /* Update navbar langsung */
            const navName   = document.querySelector('.user-name');
            const navAvatar = document.querySelector('.user-avatar');
            const ini = (newName.trim().split(/\s+/).map(w=>w[0]).join('')).substring(0,2).toUpperCase();
            if (navName)   navName.textContent   = newName;
            if (navAvatar) navAvatar.textContent = ini;

            /* Update juga di halaman profil */
            const nameLg2  = document.getElementById('profNameLg');
            const avatarLg2 = document.getElementById('profAvatarLg');
            if (nameLg2)   nameLg2.textContent   = newName;
            if (avatarLg2) avatarLg2.textContent = ini;
            document.getElementById('profEditPass').value = '';

            /* Kirim ke API kalau ada token */
            try {
                const token = localStorage.getItem('faktaKu_token') || '';
                if (token) {
                    const fd = new FormData();
                    fd.append('name', newName);
                    if (newPass) fd.append('password', newPass);
                    fetch('api/auth.php?action=update_profile', {
                        method: 'POST',
                        headers: { 'Authorization': 'Bearer ' + token },
                        body: fd
                    }).catch(function(){});
                }
            } catch(err) {}

            showProfileToast('Profil berhasil disimpan', 'success');
        });

        /* Tutup kalau klik luar card */
        pp.addEventListener('click', function(ev) {
            if (ev.target === pp) closeProfilePage();
        });
    }

    /* Buka */
    pp.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProfilePage() {
    const pp = document.getElementById('profilePage');
    if (pp) {
        pp.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function showProfileToast(msg, type) {
    let toast = document.getElementById('profToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'profToast';
        document.body.appendChild(toast);
    }
    toast.className = 'profile-toast ' + type;
    toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i> ' + msg;
    requestAnimationFrame(function() { toast.classList.add('show'); });
    setTimeout(function() { toast.classList.remove('show'); }, 3000);
}
    function openAuth(mode = 'login') {
        const modal = $('#authModal');
        if (!modal) return;
        const loginTab  = $('#loginTab');
        const regTab    = $('#registerTab');
        const loginForm = $('#loginForm');
        const regForm   = $('#registerForm');
        if (mode === 'login') {
            loginTab?.classList.add('active');
            regTab?.classList.remove('active');
            if (loginForm) loginForm.style.display = '';
            if (regForm)   regForm.style.display   = 'none';
        } else {
            regTab?.classList.add('active');
            loginTab?.classList.remove('active');
            if (regForm)   regForm.style.display   = '';
            if (loginForm) loginForm.style.display = 'none';
        }
        modal.classList.add('active');
    }

    function closeAuth() { $('#authModal')?.classList.remove('active'); }

       // FUNGSI LOGIN PINTAR (mencari form dari tombol yang diklik)
    function handleLogin(e) {
        if (e) e.preventDefault();
        
        // Cari form paling dekat dari tombol yang diklik, atau cari ID loginForm
        const form = e?.target?.closest('form') || document.getElementById('loginForm');
        if (!form) { toast('Form login tidak ditemukan', 'error'); return; }

        const inputs = [...form.querySelectorAll('input')];
        const email    = (inputs.find(i => i.type === 'email') || inputs[0])?.value.trim();
        const password = (inputs.find(i => i.type === 'password') || inputs[1])?.value;

        if (!email || !password) { toast('Email dan password wajib diisi', 'warning'); return; }

        const btn = form.querySelector('[type="submit"]') || e?.target?.closest('button');
        const orig = btn?.innerHTML;
        if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'; btn.disabled = true; }

        apiForm(API.LOGIN, { email, password }).then(res => {
            if (btn) { btn.innerHTML = orig || 'Masuk'; btn.disabled = false; }
            if (res.success) {
                state.user = res.user;
                localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(res.user));
                syncAuthUI();
                closeAuth();
                form.reset();
                toast(`Selamat datang, ${res.user.name}!`, 'success');
            } else {
                toast(res.message || 'Login gagal', 'error');
            }
        });
    }

    // FUNGSI REGISTER PINTAR (mencari form dari tombol yang diklik)
    function handleRegister(e) {
        if (e) e.preventDefault();
        
        const form = e?.target?.closest('form') || document.getElementById('registerForm');
        if (!form) { toast('Form registrasi tidak ditemukan', 'error'); return; }

        const inputs = [...form.querySelectorAll('input')];
        const name     = (inputs.find(i => i.type === 'text') || inputs[0])?.value.trim();
        const email    = (inputs.find(i => i.type === 'email') || inputs[1])?.value.trim();
        const password = (inputs.find(i => i.type === 'password') || inputs[2])?.value;

        if (!name || !email || !password) { toast('Semua field wajib diisi', 'warning'); return; }
        if (password.length < 6) { toast('Password minimal 6 karakter', 'warning'); return; }

        const btn = form.querySelector('[type="submit"]') || e?.target?.closest('button');
        const orig = btn?.innerHTML;
        if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'; btn.disabled = true; }

        apiForm(API.REGISTER, { name, email, password }).then(res => {
            if (btn) { btn.innerHTML = orig || 'Daftar'; btn.disabled = false; }
            if (res.success) {
                state.user = res.user;
                localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(res.user));
                syncAuthUI();
                closeAuth();
                form.reset();
                toast('Registrasi berhasil! Selamat datang!', 'success');
            } else {
                toast(res.message || 'Registrasi gagal', 'error');
            }
        });
    }

    function logout() {
        state.user = null;
        localStorage.removeItem(STORAGE_KEYS.USER);
        syncAuthUI();
        closeProfileMenu();
        toast('Berhasil logout', 'info');
    }

    // Sinkronkan tampilan header berdasarkan ID dari HTML kamu
    function syncAuthUI() {
        const authBtn  = document.getElementById('Akun');
        const profileEl = document.getElementById('userProfile');
        
        if (state.user) {
            if (authBtn)  authBtn.style.display  = 'none';
            if (profileEl) profileEl.style.display = 'block';
            
            const initials = getInitials(state.user.name);
            const nameEl   = document.getElementById('userName');
            const avatarEl = document.getElementById('userAvatar');
            
            if (nameEl)   nameEl.textContent   = state.user.name;
            if (avatarEl) avatarEl.textContent  = initials;
            
            const menuName = $('.profile-menu-name', profileEl);
            if (menuName) menuName.textContent = state.user.name;
        } else {
            if (authBtn)  authBtn.style.display  = 'inline-flex';
            if (profileEl) profileEl.style.display = 'none';
        }
    }

    // ════════════════════════════════════════
    // PROFILE DROPDOWN MENU
    // ════════════════════════════════════════
    function toggleProfileMenu() {
        document.getElementById('userProfile')?.classList.toggle('menu-open');
    }

    function closeProfileMenu() {
        document.getElementById('userProfile')?.classList.remove('menu-open');
    }

    // ════════════════════════════════════════
    // MOBILE MENU
    // ════════════════════════════════════════
    function toggleMobileMenu() {
        const btn = document.getElementById('mobileMenuToggle') || $('.mobile-menu-toggle');
        const nav = document.getElementById('navSection') || $('.nav-section');
        if (!btn || !nav) return;
        const open = nav.classList.toggle('mobile-open');
        const icon = btn.querySelector('i');
        if (icon) icon.className = open ? 'fas fa-times' : 'fas fa-bars';
    }

    function closeMobileMenu() {
        const btn = document.getElementById('mobileMenuToggle') || $('.mobile-menu-toggle');
        const nav = document.getElementById('navSection') || $('.nav-section');
        if (nav) nav.classList.remove('mobile-open');
        if (btn) { const i = btn.querySelector('i'); if (i) i.className = 'fas fa-bars'; }
    }

    // ════════════════════════════════════════
    // STATISTIK
    // ════════════════════════════════════════
    function animateNum(el, target, dur = 2000, suffix = '') {
        const t0 = performance.now();
        (function tick(now) {
            const p = Math.min((now - t0) / dur, 1);
            const ease = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * ease).toLocaleString('id-ID') + suffix;
            if (p < 1) requestAnimationFrame(tick);
        })(t0);
    }

    function animateFloat(el, target, dur = 2000, suffix = '') {
        const t0 = performance.now();
        (function tick(now) {
            const p = Math.min((now - t0) / dur, 1);
            const ease = 1 - Math.pow(1 - p, 3);
            el.textContent = (target * ease).toFixed(1) + suffix;
            if (p < 1) requestAnimationFrame(tick);
        })(t0);
    }

    async function loadStatistics() {
        const nums = $$('.stat-number');
        if (!nums.length) return;
        const res = await apiJSON(API.STATISTICS);
        let d = (res.success && res.data) ? res.data : null;
        const totalNews = d ? parseInt(d.total_news_analyzed) || 0 : 1250;
        const totalUser = d ? parseInt(d.total_users)         || 0 : 340;
        const accuracy  = d ? parseFloat(d.accuracy_rate)     || 0 : 94;
        const avgTime   = d ? parseFloat(d.avg_detection_time)|| 0 : 2.4;
        if (nums[0]) animateNum(nums[0], totalNews, 2200, '+');
        if (nums[1]) animateNum(nums[1], totalUser, 2200, '+');
        if (nums[2]) animateNum(nums[2], accuracy, 2200, '%');
        if (nums[3]) animateFloat(nums[3], avgTime, 2200, ' dtk');
    }

    // ════════════════════════════════════════
    // DETEKSI HOAX
    // ════════════════════════════════════════
       async function handleDetection() {
        if (state.detecting) return;
        
        const form = document.getElementById('detectionForm');
        if (!form) { toast('Form tidak ditemukan', 'error'); return; }

        // Cari semua textarea & input text di dalam form, ambil yang tidak kosong
        const allFields = [...form.querySelectorAll('textarea, input[type="text"]')];
        let finalTxt = '';
        for (const field of allFields) {
            const val = field.value.trim();
            if (val) { finalTxt = val; break; }
        }

        if (!finalTxt) { toast('Masukkan teks berita yang ingin dianalisis', 'warning'); return; }

        state.detecting = true;
        // Cari tombol "Cek Sekarang" berdasarkan teksnya
        const btn = form ? [...form.querySelectorAll('.btn')].find(b => b.textContent.includes('Cek Sekarang')) : null;
        const orig = btn?.innerHTML;
        if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menganalisis...'; btn.disabled = true; }
        
        // Sembunyikan hasil sebelumnya (pakai ID #detectionResult)
        const rBox = document.getElementById('detectionResult');
        if (rBox) rBox.style.display = 'none';

        // PERBAIKAN BUG: Variabel content dan source tidak ada, diganti finalTxt
                const res = await apiJSON(API.DETECT, { method: 'POST', body: JSON.stringify({ content: finalTxt, source: '', user_email: state.user?.email, user_id: state.user?.id }) });
        
        state.detecting = false;
        if (btn) { btn.innerHTML = orig || '<i class="fas fa-robot"></i> Cek Sekarang'; btn.disabled = false; }

        if (res.success && res.data) {
            renderResult(res.data, finalTxt);
        } else {
            toast(res.message || 'Gagal mendeteksi berita', 'error');
            if (res.debug) console.error('[Debug]', res.debug);
        }
    }

    function resetForm() {
        const form = document.getElementById('detectionForm');
        if (form) form.reset();
        const rBox = document.getElementById('detectionResult');
        if (rBox) rBox.style.display = 'none';
    }

    // ── Format tanggal terbit berita (ID) ──
    function fmtTanggalTerbit(ds) {
        if (!ds) return '';
        try {
            const d = new Date(ds);
            if (isNaN(d.getTime())) return String(ds);
            const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
        } catch (e) { return String(ds); }
    }

    // ── Inject CSS grid hasil deteksi (sekali saja) ──
    function ensureResultStyles() {
        if (document.getElementById('faktaku-result-inline-style')) return;
        const style = document.createElement('style');
        style.id = 'faktaku-result-inline-style';
        style.textContent = `
            .fk-result-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}
            @media (max-width:768px){.fk-result-grid{grid-template-columns:1fr}}
        `;
        document.head.appendChild(style);
    }

    function renderResult(data, text) {
        const box = document.getElementById('detectionResult');
        if (!box) return;

        ensureResultStyles();

        const isHoax = data.result === 'hoax';
        const refs = data.references || [];
        const confidence = data.confidence != null ? data.confidence : 0;

        // Ambil judul / ringkasan / tanggal terbit dengan beberapa fallback nama field
        const judul     = data.title || data.judul || (refs[0] && refs[0].title) || (isHoax ? '[HOAKS] ' + text : text);
        const ringkasan = data.summary || data.ringkasan || data.message || text;
        const tanggalRaw = data.published_date || data.tanggal_terbit || data.publishedAt || (refs[0] && refs[0].publishedAt) || null;
        const tanggal    = tanggalRaw ? fmtTanggalTerbit(tanggalRaw) : '-';

        // ── Bangun daftar sumber berita terkait ──
        let refsHTML = '';
        if (refs.length > 0) {
            refs.forEach((ref, i) => {
                const no = String(i + 1).padStart(2, '0');
                refsHTML += `
                <div style="display:flex;gap:14px;align-items:flex-start;padding:14px 16px;border-radius:16px;background:rgba(255,255,255,0.82);border:1px solid rgba(148,163,184,0.14);box-shadow:0 8px 18px rgba(15,23,42,0.03)">
                    <div style="flex:0 0 auto;width:32px;height:32px;border-radius:50%;background:rgba(99,102,241,0.14);color:#4338ca;font-weight:800;font-size:12.5px;display:flex;align-items:center;justify-content:center">${no}</div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;color:#0f172a;font-size:14px;line-height:1.45;margin-bottom:3px">${escapeHTML(ref.title || '-')}</div>
                        <div style="color:#94a3b8;font-size:12.5px;margin-bottom:6px">${escapeHTML(ref.source || '')}</div>
                        ${ref.link ? `<a href="${ref.link}" target="_blank" rel="noopener" style="font-size:13px;color:#6366f1;font-weight:700;text-decoration:none">Buka sumber berita</a>` : ''}
                    </div>
                </div>`;
            });
        } else {
            refsHTML = `
                <div style="padding:14px;border-radius:14px;border:1px dashed rgba(99,102,241,0.25);text-align:center;color:#94a3b8;font-size:13px">
                    <i class="fas fa-info-circle" style="margin-right:6px;opacity:.6"></i>
                    Tidak ada referensi tambahan dari sumber berita terpercaya.
                </div>`;
        }

        // ── Badge FAKTA / HOAKS ──
        const badgeHTML = isHoax
            ? `<span style="display:inline-flex;align-items:center;gap:8px;padding:9px 22px;border-radius:12px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-weight:800;font-size:15px;box-shadow:0 10px 22px rgba(239,68,68,0.3)"><i class="fas fa-shield-halved"></i> HOAKS</span>`
            : `<span style="display:inline-flex;align-items:center;gap:8px;color:#10b981;font-weight:800;font-size:24px"><i class="fas fa-shield-halved"></i> FAKTA</span>`;

        const iconHead = isHoax
            ? `<i class="fas fa-triangle-exclamation" style="color:#ef4444"></i>`
            : `<i class="fas fa-circle-check" style="color:#10b981"></i>`;

        const bgOuter = isHoax
            ? 'radial-gradient(circle at top right, rgba(239,68,68,0.12), transparent 30%), linear-gradient(180deg, rgba(255,243,243,0.98), rgba(255,255,255,0.98))'
            : 'radial-gradient(circle at top right, rgba(16,185,129,0.12), transparent 30%), linear-gradient(180deg, rgba(241,255,249,0.98), rgba(255,255,255,0.98))';
        const borderOuter = isHoax ? 'rgba(239,68,68,0.16)' : 'rgba(16,185,129,0.16)';

        box.innerHTML = `
        <div style="border-radius:28px;padding:24px;margin-top:20px;background:${bgOuter};border:1px solid ${borderOuter};box-shadow:0 24px 60px rgba(15,23,42,0.08)">

            <!-- HEADER: Hasil Deteksi + Akurasi | Badge -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:20px">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;font-size:19px;font-weight:800;color:#0f172a">
                        ${iconHead} Hasil Deteksi
                    </div>
                    <div style="font-size:13px;color:#64748b;margin-top:5px">Akurasi prediksi: ${confidence}%</div>
                </div>
                <div>${badgeHTML}</div>
            </div>

            <!-- 3 KOLOM: JUDUL / RINGKASAN / TANGGAL TERBIT -->
            <div class="fk-result-grid">
                <div style="padding:16px;border-radius:16px;background:rgba(248,250,252,0.92);border:1px solid rgba(148,163,184,0.16)">
                    <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:#94a3b8;margin-bottom:8px">Judul</div>
                    <div style="font-size:14px;font-weight:700;color:#0f172a;line-height:1.45">${escapeHTML(judul)}</div>
                </div>
                <div style="padding:16px;border-radius:16px;background:rgba(248,250,252,0.92);border:1px solid rgba(148,163,184,0.16)">
                    <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:#94a3b8;margin-bottom:8px">Ringkasan</div>
                    <div style="font-size:13.5px;color:#334155;line-height:1.55">${escapeHTML(ringkasan)}</div>
                </div>
                <div style="padding:16px;border-radius:16px;background:rgba(248,250,252,0.92);border:1px solid rgba(148,163,184,0.16)">
                    <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:#94a3b8;margin-bottom:8px">Tanggal Terbit Berita</div>
                    <div style="font-size:14px;font-weight:700;color:#0f172a">${escapeHTML(tanggal)}</div>
                </div>
            </div>

            <!-- SUMBER BERITA TERKAIT -->
            <div style="padding:18px;border-radius:20px;border:1px solid rgba(99,102,241,0.16);background:rgba(99,102,241,0.04)">
                <div style="display:flex;align-items:center;gap:9px;font-weight:800;color:#4338ca;margin-bottom:14px;font-size:14.5px">
                    <i class="fas fa-bookmark"></i> Sumber berita terkait
                </div>
                <div style="display:grid;gap:12px">
                    ${refsHTML}
                </div>
            </div>
        </div>`;

        box.style.display = 'block';
        setTimeout(() => box.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 150);
    }

    // ════════════════════════════════════════
    // EXPORT PDF
    // ════════════════════════════════════════
    function exportPDF() {
        const shell = $('#detectionModal .result-shell') || $('.result-shell');
        if (!shell) { toast('Tidak ada hasil untuk diekspor', 'warning'); return; }
        toast('Menyiapkan PDF...', 'info');
        html2pdf().set({ margin:[15,15,15,15], filename:'hasil-deteksi-hoax.pdf', image:{type:'jpeg',quality:0.98}, html2canvas:{scale:2,useCORS:true}, jsPDF:{unit:'mm',format:'a4',orientation:'portrait'} }).from(shell).save().then(() => toast('PDF berhasil diunduh!', 'success')).catch(() => toast('Gagal mengunduh PDF', 'error'));
    }

    // ════════════════════════════════════════
    // BERITA TERKINI (dari GNews API via backend api/news_feed.php)
    // ════════════════════════════════════════
    let newsFeedData = [];      // menyimpan artikel yang lagi ditampilkan (untuk modal detail)
    let newsFeedLoading = false;

    function newsMonthAbbr(idx) {
        return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][idx] || '-';
    }

    function formatNewsDate(iso) {
        if (!iso) return '-';
        try {
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '-';
            return `${d.getDate()} ${newsMonthAbbr(d.getMonth())} ${d.getFullYear()}`;
        } catch (e) { return '-'; }
    }

    function formatNewsDateTime(iso) {
        if (!iso) return '-';
        try {
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '-';
            return `${d.getDate()} ${newsMonthAbbr(d.getMonth())} ${d.getFullYear()}, ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')} WIB`;
        } catch (e) { return '-'; }
    }

    function getNewsEls() {
        return {
            grid:   document.getElementById('newsGrid') || $('.news-grid'),
            status: document.getElementById('newsRealtimeStatus') || $('.news-feed-status'),
            refreshBtn: document.getElementById('refreshNewsBtn') || $('.btn-refresh-news')
        };
    }

    function newsCardHTML(article, idx) {
        const img = article.image || 'https://images.unsplash.com/photo-1495020689067-958852a7765e?w=400&h=200&fit=crop';
        const excerpt = article.description || article.content || '';
        return `<article class="news-card" data-id="${idx}">
            <img src="${img}" alt="${escapeHTML(article.title)}" class="news-image" onerror="this.src='https://images.unsplash.com/photo-1495020689067-958852a7765e?w=400&h=200&fit=crop'">
            <div class="news-content">
                <div class="news-meta">
                    <span class="news-date"><i class="fas fa-calendar"></i> ${formatNewsDate(article.publishedAt)}</span>
                    <span class="news-source"><i class="fas fa-check-circle"></i> ${escapeHTML(article.source)}</span>
                </div>
                <h3 class="news-title">${escapeHTML(article.title)}</h3>
                <p class="news-excerpt">${escapeHTML(excerpt)}</p>
                <button class="btn btn-detail" onclick="showNewsDetail(${idx})">
                    <i class="fas fa-eye"></i> Lihat Detail
                </button>
            </div>
        </article>`;
    }

    async function loadNewsFeed(forceRefresh) {
        const { grid, status, refreshBtn } = getNewsEls();
        if (!grid || newsFeedLoading) return;

        newsFeedLoading = true;
        if (status) { status.textContent = 'Status feed: memuat berita terkini...'; status.classList.remove('error'); }
        if (refreshBtn) refreshBtn.disabled = true;

        const res = await apiJSON('api/news_feed.php' + (forceRefresh ? '?refresh=1' : ''));

        newsFeedLoading = false;
        if (refreshBtn) refreshBtn.disabled = false;

        if (res.success && Array.isArray(res.data) && res.data.length) {
            newsFeedData = res.data;
            grid.innerHTML = newsFeedData.map(newsCardHTML).join('');
            if (status) {
                status.classList.remove('error');
                status.textContent = `Menampilkan ${newsFeedData.length} berita terkini${res.fetched_at ? ' • diperbarui ' + formatNewsDateTime(res.fetched_at) : ''}`;
            }
        } else {
            if (status) {
                status.classList.add('error');
                status.textContent = res.message || 'Gagal memuat berita terkini. Coba klik Refresh Berita.';
            }
            if (!newsFeedData.length) {
                grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:70px 20px"><div style="width:84px;height:84px;margin:0 auto 22px;border-radius:50%;background:linear-gradient(135deg,rgba(99,102,241,.12),rgba(236,72,153,.12));display:flex;align-items:center;justify-content:center"><i class="fas fa-triangle-exclamation" style="font-size:34px;color:var(--text-muted)"></i></div><h3 style="color:var(--text-primary);margin-bottom:10px;font-size:20px">Berita Belum Bisa Dimuat</h3><p style="color:var(--text-secondary);margin-bottom:24px">${escapeHTML(res.message || 'Terjadi kendala saat mengambil berita dari server.')}</p><button class="btn btn-primary" onclick="loadNewsFeed(true)"><i class="fas fa-arrows-rotate"></i> Coba Lagi</button></div>`;
            }
        }
    }

    function relocateNewsModal() {
        // BUG FIX: #newsModal awalnya berada di dalam <section data-aos="...">.
        // Ancestor dengan transform (dari animasi AOS) membuat position:fixed
        // pada modal jadi mengikuti posisi section, bukan viewport — akibatnya
        // modal terpotong & tidak bisa discroll. Pindahkan ke <body> supaya
        // fixed positioning-nya bekerja normal menutupi seluruh layar.
        const modal = document.getElementById('newsModal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        return modal;
    }

    function cleanArticleText(txt) {
        if (!txt) return '';
        // GNews (free tier) suka nambahin artefak seperti "... [3270 chars]" di akhir teks
        return txt.replace(/\s*\[\d+\s*chars\]\s*$/i, '').trim();
    }

    function showNewsDetail(idx) {
        const article = newsFeedData[idx];
        if (!article) return;
        const modal = relocateNewsModal();
        if (!modal) return;

        const imgEl     = document.getElementById('modalImage');
        const metaEl    = document.getElementById('modalMeta');
        const titleEl   = document.getElementById('modalTitle');
        const textEl    = document.getElementById('modalFullText');
        const linkEl    = document.getElementById('modalSourceLink');
        const contentEl = modal.querySelector('.modal-content');

        if (imgEl)   { imgEl.src = article.image || 'https://images.unsplash.com/photo-1495020689067-958852a7765e?w=800&h=400&fit=crop'; imgEl.alt = article.title || ''; }
        if (metaEl)  metaEl.innerHTML = `<i class="fas fa-check-circle"></i> ${escapeHTML(article.source)} &nbsp;•&nbsp; <i class="far fa-clock"></i> ${formatNewsDateTime(article.publishedAt)}`;
        if (titleEl) titleEl.textContent = article.title || '-';

        const desc = cleanArticleText(article.description);
        const full = cleanArticleText(article.content);
        const bestText = (full && full.length > desc.length ? full : desc) || 'Ringkasan tidak tersedia. Silakan baca artikel lengkap di sumber asli.';
        if (textEl) textEl.textContent = bestText;

        if (linkEl)  linkEl.href = article.url || '#';

        // Pastikan modal & isinya bisa discroll walau tinggi konten melebihi layar
        modal.style.cssText = 'display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;justify-content:center;align-items:flex-start;padding:20px;overflow-y:auto;';
        if (contentEl) {
            contentEl.style.maxHeight = 'none';
            contentEl.style.margin = '20px auto';
        }
        document.body.style.overflow = 'hidden';
        modal.scrollTop = 0;
    }

    function closeNewsModal() {
        const modal = document.getElementById('newsModal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // ════════════════════════════════════════
    // RIWAYAT DI DALAM MODAL
    // ════════════════════════════════════════
    async function loadHistoryInside() {
        const container = document.getElementById('historyContent') || $('#historyModal .modal-body');
        if (!container) return;
        if (!state.user) { container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:40px">Silakan login terlebih dahulu.</p>'; return; }
        container.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-secondary)"><i class="fas fa-spinner fa-spin"></i> Memuat...</p>';
        const res = await apiJSON(API.GET_HISTORY);
        if (res.success && res.data && res.data.length) {
            let html = '<div style="display:grid;gap:14px">';
            res.data.forEach(item => {
                const hoax = item.detection_result === 'hoax';
                const bgColor = hoax ? 'rgba(239,68,68,0.08)' : 'rgba(16,185,129,0.08)';
                const borderColor = hoax ? 'rgba(239,68,68,0.2)' : 'rgba(16,185,129,0.2)';
                const labelColor = hoax ? '#ef4444' : '#10b981';
                html += `<div style="padding:16px;border-radius:14px;background:${bgColor};border:1px solid ${borderColor};display:flex;justify-content:space-between;align-items:flex-start;gap:14px"><div style="flex:1;min-width:0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:8px"><span style="padding:3px 10px;border-radius:999px;background:${bgColor};color:${labelColor};font-size:11px;font-weight:700;border:1px solid ${borderColor}">${hoax ? 'HOAX' : 'FAKTA'}</span><span style="font-size:12px;color:var(--text-muted)">${formatDate(item.date)}</span></div><p style="color:var(--text-primary);font-size:13px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${escapeHTML(item.content)}</p></div><button onclick="App.deleteHistory(${item.id})" style="flex-shrink:0;width:34px;height:34px;border-radius:10px;border:1px solid rgba(239,68,68,0.2);background:rgba(239,68,68,0.06);color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;transition:all .2s" onmouseover="this.style.background='rgba(239,68,68,0.15)'" onmouseout="this.style.background='rgba(239,68,68,0.06)'"><i class="fas fa-trash-alt"></i></button></div>`;
            });
            html += '</div>';
            html += `<button onclick="App.clearHistory()" style="width:100%;margin-top:18px;padding:12px;border-radius:12px;border:1px solid rgba(239,68,68,0.2);background:rgba(239,68,68,0.06);color:#ef4444;font-weight:600;font-size:13px;cursor:pointer;transition:all .2s" onmouseover="this.style.background='rgba(239,68,68,0.15)'" onmouseout="this.style.background='rgba(239,68,68,0.06)'"><i class="fas fa-trash"></i> Hapus Semua Riwayat</button>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div style="text-align:center;padding:50px 20px"><i class="fas fa-inbox" style="font-size:40px;color:var(--text-muted);margin-bottom:16px;display:block"></i><p style="color:var(--text-secondary)">Belum ada riwayat deteksi</p></div>`;
        }
    }

    // ════════════════════════════════════════
    // HISTORY ACTIONS
    // ════════════════════════════════════════
    async function deleteHistory(id) {
        const res = await apiJSON(API.DELETE_HISTORY, { method:'POST', body:JSON.stringify({id}) });
        if (res.success) { toast('Riwayat dihapus', 'success'); loadHistoryInside(); }
        else { toast(res.message || 'Gagal menghapus', 'error'); }
    }

    async function clearHistory() {
        const res = await apiJSON(API.CLEAR_HISTORY, { method:'POST', body:JSON.stringify({}) });
        if (res.success) { toast('Semua riwayat dihapus', 'success'); loadHistoryInside(); }
        else { toast(res.message || 'Gagal menghapus', 'error'); }
    }

    function viewDetail(id) { toast('Fitur detail riwayat sedang dikembangkan', 'info'); }

    // ════════════════════════════════════════
    // HEADER SCROLL & OUTSIDE CLICK
    // ════════════════════════════════════════
    function initScrollHeader() {
        const hdr = $('header');
        if (!hdr) return;
        const fn = () => hdr.classList.toggle('scrolled', window.scrollY > 50);
        window.addEventListener('scroll', fn, { passive: true }); fn();
    }

    function initOutsideClick() {
        document.addEventListener('click', e => {
            const prof = document.getElementById('userProfile');
            if (prof?.classList.contains('menu-open') && !prof.contains(e.target)) closeProfileMenu();
        });
    }

    // ════════════════════════════════════════
    // INTERNAL EVENT LISTENERS
    // ════════════════════════════════════════
      function bindAll() {
        $('.theme-toggle')?.addEventListener('click', toggleTheme);
        $('.welcome-btn')?.addEventListener('click', closeWelcome);
        $('#loginTab')?.addEventListener('click', () => openAuth('login'));
        $('#registerTab')?.addEventListener('click', () => openAuth('register'));
        $('#authModal .modal-close, .auth-close')?.addEventListener('click', closeAuth);
        $('.btn-pdf')?.addEventListener('click', exportPDF);
        $('.profile-menu-item.logout')?.addEventListener('click', logout);
        (document.getElementById('refreshNewsBtn') || $('.btn-refresh-news'))?.addEventListener('click', () => loadNewsFeed(true));
         document.getElementById('reportForm')?.addEventListener('submit', handleReportSubmit);

        // Muat berita terkini setiap kali nav "Berita" diklik (terlepas dari implementasi showSection yang aktif)
        $$('.nav-link').forEach(l => {
            if (l.textContent.trim().toLowerCase().includes('berita')) {
                l.addEventListener('click', () => loadNewsFeed());
            }
        });
        
        // TAMBAHKAN INI: Cari otomatis tombol Masuk/Daftar berdasarkan TEKSnya
        $$('#authModal button').forEach(btn => {
            const text = btn.textContent.trim().toLowerCase();
            if (text.includes('masuk') || text.includes('login')) {
                btn.addEventListener('click', handleLogin);
            } else if (text.includes('daftar') || text.includes('register')) {
                btn.addEventListener('click', handleRegister);
            }
        });


        // TAMBAHKAN INI: Cari otomatis tombol Logout di dalam dropdown profil
        const profileMenu = document.getElementById('userProfile');
        if (profileMenu) {
            profileMenu.querySelectorAll('button, a, div[role="button"]').forEach(item => {
                const text = item.textContent.trim().toLowerCase();
                if (text.includes('logout') || text.includes('keluar')) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); // Mencegah menutup menu terlebih dahulu
                        logout();
                    });
                }
            });
        }

        
        $$('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); }));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeAllModals(); closeProfileMenu(); } });
    }

    // ════════════════════════════════════════
    // AOS INIT
    // ════════════════════════════════════════
    function initAOS() {
        if (typeof AOS !== 'undefined') AOS.init({ duration:800, easing:'ease-out-cubic', once:true, offset:80 });
    }


        // ════════════════════════════════════════
    // RIWAYAT PENGECEKAN (FULL PAGE)
    // ════════════════════════════════════════
    (function(){
        var page     = document.getElementById('riwayatPage');
        var body     = document.getElementById('riwayatBody');
        var countEl  = document.getElementById('riwayatCount');
        var infoEl   = document.getElementById('riwayatBottomInfo');
        var btnHapus = document.getElementById('riwayatHapusSemua');
        var confirmBox = document.getElementById('riwayatConfirm');
        var btnYa    = document.getElementById('riwayatConfirmYa');
        var btnTidak = document.getElementById('riwayatConfirmTidak');
        var btnBack  = document.getElementById('riwayatBackBtn');

        function fmtDate(iso) {
            if (!iso) return '-';
            try {
                var d = new Date(iso);
                if (isNaN(d.getTime())) return iso;
                var bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                return d.getDate() + ' ' + bln[d.getMonth()] + ' ' + d.getFullYear() +
                       ', ' + String(d.getHours()).padStart(2,'0') + '.' +
                       String(d.getMinutes()).padStart(2,'0') + '.' +
                       String(d.getSeconds()).padStart(2,'0');
            } catch(e) { return iso; }
        }

        function esc(s) {
            if (!s) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function renderTable(items) {
            countEl.textContent = items.length + ' data';

            if (!items.length) {
                body.innerHTML = '<div class="riwayat-empty"><i class="fas fa-clipboard-list"></i><p>Belum ada riwayat pengecekan</p></div>';
                infoEl.textContent = 'Tidak ada data';
                btnHapus.disabled = true;
                return;
            }

            btnHapus.disabled = false;
            infoEl.textContent = 'Menampilkan ' + items.length + ' data pengecekan';

            items.sort(function(a, b) {
                return new Date(b.date || b.created_at || 0) - new Date(a.date || a.created_at || 0);
            });

            var h = '<div class="riwayat-table-wrap"><table class="riwayat-table"><thead><tr>' +
                    '<th>No</th><th>Tanggal</th><th>Konten</th><th>Hasil</th>' +
                    '</tr></thead><tbody>';

            items.forEach(function(item, i) {
                var hasil = (item.detection_result || item.result || '').toLowerCase();
                var cls = 'pending', txt = 'Pending', ico = 'fa-clock';
                if (hasil === 'hoax' || hasil === 'hoaks') { cls = 'hoax'; txt = 'HOAX'; ico = 'fa-times-circle'; }
                else if (hasil === 'safe' || hasil === 'fakta' || hasil === 'aman') { cls = 'safe'; txt = 'AMAN'; ico = 'fa-check-circle'; }

                h += '<tr>' +
                     '<td class="riwayat-td-no">' + (i+1) + '</td>' +
                     '<td class="riwayat-td-date">' + fmtDate(item.date || item.created_at) + '</td>' +
                     '<td class="riwayat-td-content">' + esc(item.content || item.text || item.query || '-') + '</td>' +
                     '<td class="riwayat-td-result"><span class="riwayat-badge ' + cls + '"><i class="fas ' + ico + '"></i> ' + txt + '</span></td>' +
                     '</tr>';
            });

            h += '</tbody></table></div>';
            body.innerHTML = h;
        }

        async function loadRiwayat() {
            body.innerHTML = '<div class="riwayat-empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat data...</p></div>';

            var items = [];

            /* Prioritas 1: Ambil dari API */
            if (state.user) {
                try {
                    var res = await apiJSON(API.GET_HISTORY);
                    if (res.success && res.data && res.data.length) {
                        items = res.data;
                    }
                } catch(e) {}
            }

            /* Prioritas 2: Fallback localStorage */
            if (!items.length) {
                try {
                    items = JSON.parse(localStorage.getItem('faktaKu_history') || '[]');
                    if (!Array.isArray(items)) items = [];
                } catch(e) { items = []; }
            }

            renderTable(items);
        }

        function openRiwayat(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            confirmBox.classList.remove('show');
            btnHapus.style.display = '';
            loadRiwayat();
            page.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeRiwayat() {
            page.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (btnBack) btnBack.addEventListener('click', closeRiwayat);

        if (btnHapus) btnHapus.addEventListener('click', function() {
            confirmBox.classList.add('show');
            btnHapus.style.display = 'none';
        });

        if (btnYa) btnYa.addEventListener('click', async function() {
            /* Hapus dari API */
            if (state.user) {
                try { await apiJSON(API.CLEAR_HISTORY, { method:'POST', body:JSON.stringify({}) }); } catch(e) {}
            }
            /* Hapus dari localStorage */
            localStorage.removeItem('faktaKu_history');
            confirmBox.classList.remove('show');
            btnHapus.style.display = '';
            renderTable([]);
            toast('Semua riwayat berhasil dihapus', 'success');
        });

        if (btnTidak) btnTidak.addEventListener('click', function() {
            confirmBox.classList.remove('show');
            btnHapus.style.display = '';
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && page && page.classList.contains('active')) closeRiwayat();
        });

        /* Expose ke window agar bisa dipanggil dari onclick HTML */
        window.openRiwayatPage = openRiwayat;
        window.closeRiwayatPage = closeRiwayat;
    })();


    function openReportModal(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    const modal = document.getElementById('reportModal');
    if (!modal) return;

    const locked    = document.getElementById('reportLockedState');
    const formState = document.getElementById('reportFormState');
    const nameEl    = document.getElementById('reportAsName');

    if (state.user) {
        if (locked)    locked.style.display = 'none';
        if (formState) formState.style.display = 'block';
        if (nameEl)    nameEl.textContent = state.user.name || state.user.email || 'Anda';
    } else {
        if (locked)    locked.style.display = 'block';
        if (formState) formState.style.display = 'none';
    }

    modal.classList.add('active');
}

function handleReportSubmit(e) {
    if (e) e.preventDefault();
    if (!state.user) { toast('Silakan masuk akun terlebih dahulu', 'warning'); return; }

    const form   = document.getElementById('reportForm');
    const descEl = document.getElementById('reportDescription');
    const desc   = descEl ? descEl.value.trim() : '';
    if (!desc) { toast('Deskripsi laporan wajib diisi', 'warning'); return; }

    const btn  = document.getElementById('reportSubmitBtn');
    const orig = btn?.innerHTML;
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...'; btn.disabled = true; }

    apiForm('api/report.php?action=create', {
        user_id:        state.user.id || '',
        reporter_name:  state.user.name || 'Anonim',
        reporter_email: state.user.email || '',
        description:    desc
    }).then(res => {
        if (btn) { btn.innerHTML = orig || '<i class="fas fa-paper-plane"></i> Kirim Laporan'; btn.disabled = false; }
        if (res.success) {
            toast('Laporan berhasil dikirim, terima kasih!', 'success');
            form.reset();
            const rBox = document.getElementById('reportResult');
            if (rBox) {
                rBox.className = 'result-box report-result safe';
                rBox.style.display = 'block';
                rBox.innerHTML = `<div class="result-title"><i class="fas fa-check-circle"></i> Laporan Terkirim</div><div class="result-desc">Terima kasih, laporanmu sudah kami terima dan akan segera ditinjau tim kami.</div>`;
            }
        } else {
            toast(res.message || 'Gagal mengirim laporan', 'error');
        }
    });
}


    // ════════════════════════════════════════
    // EXPOSE KE GLOBAL (cocokkan dengan onclick di HTML)
    // ════════════════════════════════════════
    window.showSection         = showSection;
    window.openModal           = openModal;
    window.closeModal          = closeModal;
    window.closeAllModals      = closeAllModals;
    window.closeWelcome        = closeWelcome;
    window.openAuth            = openAuth;
    window.closeAuth           = closeAuth;
    window.toggleTheme         = toggleTheme;
    window.toggleMobileMenu    = toggleMobileMenu;
    window.openAccountEntry    = openAccountEntry;
    window.openReportModal     = openReportModal;
    window.openProfileSettings = openProfileSettings;
    window.handleDetection     = handleDetection;
    window.resetForm           = resetForm;
    window.handleLogin         = handleLogin;
    window.handleRegister      = handleRegister;
    window.logout              = logout;
    window.openRiwayatPage   = openRiwayatPage;
    window.closeRiwayatPage  = closeRiwayatPage;
    window.loadNewsFeed      = loadNewsFeed;
    window.showNewsDetail    = showNewsDetail;
    window.closeNewsModal    = closeNewsModal;

    window.App = {
        openDetection: () => openModal('detectionModal'),
        viewDetail,
        deleteHistory,
        clearHistory,
        exportPDF
    };

    // ════════════════════════════════════════
    // BOOT
    // ════════════════════════════════════════
    function boot() {
        try { const s = localStorage.getItem(STORAGE_KEYS.USER); if (s) state.user = JSON.parse(s); } 
        catch { localStorage.removeItem(STORAGE_KEYS.USER); }
        initTheme();
        initWelcome();
        initScrollHeader();
        initOutsideClick();
        bindAll();
        syncAuthUI();
        initAOS();
        relocateNewsModal();
        loadStatistics();
        loadNewsFeed();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();

})();