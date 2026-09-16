/**
 * SCRIPT.JS - REVISED VERSION
 * Layout Prediksi disesuaikan: Layer Bertahap (Transaksi & Prediksi) -> Tabel Hasil.
 */

// ================= GLOBAL VARIABLES =================
window.transactions = [];
window.predictions = [];
window.meatTypes = [];
window.charts = {};

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
    console.log("System Initializing...");

    updateCurrentDate();
    setTodayDate();
    initDarkMode();
    bindMenuEvents();

    try {
        showLoading();
        if (typeof API !== 'undefined') {
            await loadMeatTypes();
            await refreshTransactionsFromDB();
            window.predictions = await API.getPredictions() || [];
        }
    } catch (err) {
        console.error("Initialization Failed:", err);
    } finally {
        hideLoading();
    }

    showPage('dashboard'); // default page
});

// ================= MENU NAVIGATION =================
function bindMenuEvents() {
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const page = item.dataset.page;
            if (page) showPage(page);
        });
    });
}

// ================= SHOW PAGE =================
window.showPage = function(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
    const target = document.getElementById(page + 'Page');
    if (target) target.classList.remove('hidden');

    // Fix active menu highlighting
    document.querySelectorAll('.menu-item').forEach(item => {
        const onclickAttr = item.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes(`'${page}'`)) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    const titles = {
        'dashboard': 'Dashboard Utama',
        'input': 'Input Transaksi',
        'upload': 'Upload Excel',
        'report': 'Laporan Penjualan',
        'prediction': 'Prediksi Penjualan'
    };
    const titleEl = document.getElementById('pageTitle');
    if (titleEl) titleEl.textContent = titles[page] || 'Manajemen Daging';

    if (page === 'dashboard') loadDashboard();
    if (page === 'report') generateReport();
    
    // FIX: Ketika buka menu Prediksi, tampilkan defaultTab (ringkasan & chart), BUKAN langsung prediksiTab
    if (page === 'prediction') {
        switchTab('default');
    }

    if (window.innerWidth <= 768) closeSidebarOnMobile();
};

// ================= SWITCH TAB =================
window.switchTab = function(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
    const targetTab = document.getElementById(tab + 'Tab');
    if (targetTab) targetTab.classList.remove('hidden');

    if (tab === 'transaksi') loadTransactionTable();

    // Ketika buka defaultTab (ringkasan awal), render chart-chart dari data DB
    if (tab === 'default') {
        renderPredictionOverview();
    }
    
    // FIX: Ketika klik button Prediksi, hanya siapkan tabel, JANGAN inject grafik
    if (tab === 'prediksi') {
        renderPredictionTable();
    }
};

// ================= CRUD OPERATIONS =================
window.loadMeatTypes = async function() {
    if (typeof API === 'undefined') return;
    try {
        window.meatTypes = await API.getMeatTypes() || [];
        ['inputMeatType', 'editType'].forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;
            select.innerHTML = '<option value="">Pilih jenis daging</option>';
            window.meatTypes.forEach(t => {
                const option = document.createElement('option');
                option.value = t.id;
                option.textContent = t.nama_daging;
                select.appendChild(option);
            });
        });
    } catch (err) { console.error("Gagal memuat jenis daging:", err); }
};

window.refreshTransactionsFromDB = async function() {
    if (typeof API !== 'undefined') {
        window.transactions = await API.getTransactions() || [];
    }
};

window.handleInputSubmit = async function(e) {
    e.preventDefault();
    var tgl = document.getElementById('inputDate').value;
    var meat = document.getElementById('inputMeatType').value;
    var kg = document.getElementById('inputAmount').value;
    var kualitas = document.getElementById('inputKualitas').value;
    var statusHari = document.getElementById('inputStatusHari').value;
    var harga = document.getElementById('inputHarga').value;
    var promo = document.getElementById('inputPromo').value;
    var metode = document.getElementById('inputMetode').value;

    try {
        showLoading();
        var response = await API.insertTransaction(tgl, meat, kg, kualitas, statusHari, harga, promo, metode);
        
        if (response.trim() !== "success") {
            hideLoading();
            alert("Gagal menyimpan: " + response);
            return;
        }
        
        await refreshTransactionsFromDB();
        e.target.reset();
        setTodayDate();
        hideLoading();
        alert("Data berhasil disimpan!");
        showPage('dashboard');
    } catch (err) {
        hideLoading();
        alert("Gagal menyimpan data: " + err.message);
    }
};
window.loadTransactionTable = function() {
    var tbody = document.getElementById('transactionTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!window.transactions.length) {
        document.getElementById('noTransactionData')?.classList.remove('hidden');
        return;
    }
    document.getElementById('noTransactionData')?.classList.add('hidden');

    window.transactions.forEach(function(t, idx) {
        var kualitasBadge = t.kualitas_daging === 'premium'
            ? '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;">Premium</span>'
            : '<span style="background:#f3f4f6;color:#4b5563;padding:2px 8px;border-radius:99px;font-size:11px;">Standar</span>';
        var promoBadge = t.promo === 'ada'
            ? '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:99px;font-size:11px;">Ada</span>'
            : '<span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:99px;font-size:11px;">Tidak</span>';
        var metodeBadge = t.metode_penjualan === 'online'
            ? '<span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:99px;font-size:11px;">Online</span>'
            : '<span style="background:#f3f4f6;color:#4b5563;padding:2px 8px;border-radius:99px;font-size:11px;">Offline</span>';

        tbody.innerHTML +=
            '<tr class="border-b hover:bg-gray-50 transition-colors text-sm">' +
                '<td class="p-3 text-center">' + (idx + 1) + '</td>' +
                '<td class="p-3">' + t.tanggal + '</td>' +
                '<td class="p-3 font-medium">' + t.nama_daging + '</td>' +
                '<td class="p-3 text-center">' + kualitasBadge + '</td>' +
                '<td class="p-3 text-center" style="font-size:12px;">' + (t.status_hari || '-') + '</td>' +
                '<td class="p-3 text-red-600 font-bold">' + parseFloat(t.jumlah_kg).toFixed(1) + ' kg</td>' +
                '<td class="p-3 text-right">Rp ' + parseInt(t.harga).toLocaleString('id-ID') + '</td>' +
                '<td class="p-3 text-center">' + promoBadge + '</td>' +
                '<td class="p-3 text-center">' + metodeBadge + '</td>' +
                '<td class="p-3 text-center">' +
                    '<div class="flex justify-center gap-1">' +
                        '<button onclick="openEditModal(' +
                            t.id + ",'" + t.tanggal + "'," +
                            t.meat_type_id + "'," +
                            t.jumlah_kg + "'," +
                            t.kualitas_daging + "'," +
                            t.status_hari + "'," +
                            t.harga + "'," +
                            t.promo + "'," +
                            t.metode_penjualan +
                        ')" class="text-blue-600 p-2 hover:bg-blue-100 rounded"><i class="fas fa-edit"></i></button>' +
                        '<button onclick="deleteTransaction(' + t.id + ')" class="text-red-600 p-2 hover:bg-red-100 rounded"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                '</td>' +
            '</tr>';
    });
};



/**
 * FIX: Fitur Edit Transaksi (tombol pensil)
 * ------------------------------------------------
 * Ada 2 perubahan yang perlu diterapkan ke script.js:
 *
 * A) Ganti isi tombol pensil di dalam loadTransactionTable()
 * B) Tambahkan 3 fungsi baru: openEditModal, closeEditModal, saveEditedTransaction
 */

// ================================================================
// A) GANTI baris tombol edit di dalam loadTransactionTable()
// ================================================================
// SEBELUM (rusak, kutip tidak seimbang):
//
//   '<button onclick="openEditModal(' +
//       t.id + ",'" + t.tanggal + "'," +
//       t.meat_type_id + "'," +
//       t.jumlah_kg + "'," +
//       t.kualitas_daging + "'," +
//       t.status_hari + "'," +
//       t.harga + "'," +
//       t.promo + "'," +
//       t.metode_penjualan +
//   ')" class="text-blue-600 p-2 hover:bg-blue-100 rounded"><i class="fas fa-edit"></i></button>' +
//
// SESUDAH (cukup kirim id, sisanya diambil dari window.transactions):

// contoh potongan baris di dalam tbody.innerHTML += '<tr>...':
//
//   '<button onclick="openEditModal(' + t.id + ')" class="text-blue-600 p-2 hover:bg-blue-100 rounded"><i class="fas fa-edit"></i></button>' +


// ================================================================
// B) TAMBAHKAN fungsi-fungsi ini (taruh di dekat deleteTransaction)
// ================================================================

window.openEditModal = function (id) {
    const t = window.transactions.find(tx => tx.id == id);
    if (!t) {
        alert('Data transaksi tidak ditemukan.');
        return;
    }

    document.getElementById('editTransactionId').value = t.id;
    document.getElementById('editDate').value = t.tanggal;
    document.getElementById('editType').value = t.meat_type_id;
    document.getElementById('editKualitas').value = t.kualitas_daging;
    document.getElementById('editStatusHari').value = t.status_hari;
    document.getElementById('editAmount').value = t.jumlah_kg;
    document.getElementById('editHarga').value = t.harga;
    document.getElementById('editPromo').value = t.promo;
    document.getElementById('editMetode').value = t.metode_penjualan;

    const modal = document.getElementById('editModal');
    if (modal) modal.style.display = 'flex';
};

window.closeEditModal = function () {
    const modal = document.getElementById('editModal');
    if (modal) modal.style.display = 'none';
};

window.saveEditedTransaction = async function (e) {
    e.preventDefault();

    const id         = document.getElementById('editTransactionId').value;
    const tgl        = document.getElementById('editDate').value;
    const meat       = document.getElementById('editType').value;
    const kualitas   = document.getElementById('editKualitas').value;
    const statusHari = document.getElementById('editStatusHari').value;
    const kg         = document.getElementById('editAmount').value;
    const harga      = document.getElementById('editHarga').value;
    const promo      = document.getElementById('editPromo').value;
    const metode     = document.getElementById('editMetode').value;

    try {
        showLoading();

        // PENTING: sesuaikan nama fungsi API ini dengan yang ada di api.js kamu.
        // Kalau di api.js namanya bukan "updateTransaction" (misalnya "editTransaction"
        // atau "updateTrx"), ganti nama pemanggilan di baris berikut.
        const response = await API.updateTransaction(
            id, tgl, meat, kg, kualitas, statusHari, harga, promo, metode
        );

        if (response.trim() !== "success") {
            hideLoading();
            alert("Gagal menyimpan perubahan: " + response);
            return;
        }

        await refreshTransactionsFromDB();
        loadTransactionTable();
        closeEditModal();
        hideLoading();
        alert("Perubahan berhasil disimpan!");
    } catch (err) {
        hideLoading();
        alert("Gagal menyimpan perubahan: " + err.message);
    }
};



window.deleteTransaction = function(id) {
    openModal(
        'Hapus Transaksi',
        'Apakah Anda yakin ingin menghapus transaksi ini? Data yang sudah dihapus tidak dapat dikembalikan.',
        async function() {
            showLoading();
            await API.deleteTransaction(id);
            await refreshTransactionsFromDB();
            loadTransactionTable();
            hideLoading();
        },
        'Hapus'
    );
};

// ================= MODAL SYSTEM =================
window.calculatePredictions = async function() {
    if (window.transactions.length < 5) {
        alert("Minimal 5 transaksi diperlukan untuk prediksi.");
        return;
    }

    showLoading();
    const btn = document.getElementById('btnRunPrediction');
    if (btn) btn.disabled = true;

    try {
        const data = await API.runPrediction();

        if (data.status === "success") {
            const fc = data.forecast_kg || {};
            let summary =
                'Prediksi selesai!\n\n' +
                '[Forecast KG per Jenis Daging]\n' +
                'Fitur: jumlah_kg + harga_per_kg\n' +
                'Akurasi: ' + (fc.akurasi !== null ? (fc.akurasi * 100).toFixed(1) + '%' : '-') + '\n' +
                'Total data: ' + fc.total_data + ' (DB: ' + fc.data_dari_transaksi_db + ', Historis: ' + fc.data_dari_historis_jsonl + ')\n\n' +
                (data.klasifikasi_kategori
                    ? '[Klasifikasi Kategori]\nAkurasi: ' + (data.klasifikasi_kategori.akurasi_keseluruhan * 100).toFixed(1) + '%\nLihat detail confusion matrix di modal berikutnya.'
                    : '[Klasifikasi Kategori]\nDilewati (data kategori_dataset belum tersedia)');

            if (data.warnings && data.warnings.length > 0) {
                summary += '\n\n⚠️ PERINGATAN:\n' + data.warnings.join('\n');
            }

            alert(summary);

            if (data.klasifikasi_kategori) {
                showConfusionMatrix(data.klasifikasi_kategori);
            }
            
            window.predictions = await API.getPredictions() || [];
            renderPredictionTable();
            renderPredictionTomorrowCard();
            renderPredictionOverview();
            
            const lastUpd = document.getElementById('lastUpdated');
            if (lastUpd) lastUpd.innerHTML = '<strong>Terakhir Diperbarui:</strong> ' + new Date().toLocaleString('id-ID');
        } else {
            alert("Gagal menghitung prediksi: " + data.message);
        }
    } catch (err) {
        console.error(err);
        alert("Gagal menjalankan prediksi: " + err.message);
    } finally {
        if (btn) btn.disabled = false;
        hideLoading();
    }
};
// ================= CONFUSION MATRIX MODAL =================
function showConfusionMatrix(kategoriResult) {
    const modal = document.getElementById('confusionModal');
    const content = document.getElementById('confusionModalContent');
    if (!modal || !content) return;

    const labels = ['Laku', 'Cukup Laku', 'Tidak Laku'];
    const matrix = kategoriResult.confusion_matrix;
    const metrics = kategoriResult.metrics_per_kategori;

    let html = '';

    html += '<p style="font-size:13px;color:#57534e;margin-bottom:14px;">';
    html += 'Akurasi keseluruhan: <strong>' + (kategoriResult.akurasi_keseluruhan * 100).toFixed(1) + '%</strong> ';
    html += '(dihitung dari ' + kategoriResult.data_testing_20pct + ' data testing, dari total ' + kategoriResult.total_data + ' data berlabel)';
    html += '</p>';

    // ----- Tabel Confusion Matrix -----
    html += '<div style="overflow-x:auto;margin-bottom:18px;">';
    html += '<table style="border-collapse:collapse;width:100%;font-size:12px;">';
    html += '<thead><tr>';
    html += '<th style="padding:8px;border:1px solid #e5e7eb;background:#f9fafb;"></th>';
    labels.forEach(l => html += '<th style="padding:8px;border:1px solid #e5e7eb;background:#f9fafb;">Prediksi: ' + l + '</th>');
    html += '</tr></thead><tbody>';
    labels.forEach(actual => {
        html += '<tr>';
        html += '<th style="padding:8px;border:1px solid #e5e7eb;background:#f9fafb;text-align:left;">Aktual: ' + actual + '</th>';
        labels.forEach(pred => {
            const val = (matrix[actual] && matrix[actual][pred] !== undefined) ? matrix[actual][pred] : 0;
            const isDiagonal = actual === pred;
            html += '<td style="padding:8px;border:1px solid #e5e7eb;text-align:center;' +
                (isDiagonal ? 'background:#dcfce7;font-weight:700;color:#166534;' : '') + '">' + val + '</td>';
        });
        html += '</tr>';
    });
    html += '</tbody></table></div>';

    // ----- Metrik per kategori -----
    html += '<div style="font-size:13px;font-weight:700;color:#1c1917;margin-bottom:8px;">Persentase per Kategori</div>';
    html += '<div style="display:flex;flex-direction:column;gap:8px;">';
    labels.forEach(l => {
        const m = metrics[l] || { precision: 0, recall: 0, f1_score: 0, jumlah_data_aktual: 0 };
        html += '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;">';
        html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">';
        html += '<span style="font-weight:700;font-size:13px;">' + l + '</span>';
        html += '<span style="font-size:11px;color:#78716c;">n=' + m.jumlah_data_aktual + '</span>';
        html += '</div>';
        html += '<div style="display:flex;gap:16px;font-size:12px;color:#44403c;">';
        html += '<span>Recall (akurasi terhadap aktual): <strong>' + (m.recall * 100).toFixed(1) + '%</strong></span>';
        html += '<span>Precision: <strong>' + (m.precision * 100).toFixed(1) + '%</strong></span>';
        html += '</div></div>';
    });
    html += '</div>';

    content.innerHTML = html;
    modal.style.display = 'flex';
}

// ================= RENDER PREDICTION TABLE =================
function renderPredictionTable() {
    const tbody = document.getElementById('predictionTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!window.predictions || window.predictions.length === 0) {
        const noData = document.getElementById('noPredictionData');
        if (noData) noData.classList.remove('hidden');
        return;
    }

    const noData = document.getElementById('noPredictionData');
    if (noData) noData.classList.add('hidden');

    window.predictions.forEach(p => {
        // Sesuaikan key ini dengan yang dikembalikan oleh API kamu
        const tgl = p.tanggal || '-';
        const daging = p.nama_daging || p.jenis_daging || '-';
        const kg = p.prediksi_kg || p.jumlah_kg || '-';
        const akurasi = p.akurasi ? (parseFloat(p.akurasi) * 100).toFixed(1) + '%' : '-';
        const kategori = p.kategori || '-';

        const kategoriBadge = kategori === 'Laku'
            ? '<span style="background:#dcfce7;color:#166534;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;">Laku</span>'
            : kategori === 'Cukup Laku'
                ? '<span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;">Cukup Laku</span>'
                : kategori === 'Tidak Laku'
                    ? '<span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;">Tidak Laku</span>'
                    : '<span style="color:#a8a29e;font-size:11px;">-</span>';

        tbody.innerHTML += `
        <tr class="border-b hover:bg-gray-50 transition-colors text-sm md:text-base">
            <td class="p-3">${tgl}</td>
            <td class="p-3 font-medium">${daging}</td>
            <td class="p-3 text-red-600 font-bold">${kg} kg</td>
            <td class="p-3 text-center">${kategoriBadge}</td>
            <td class="p-3 text-center">${akurasi}</td>
        </tr>`;
    });
}

// ================= RENDER PREDICTION OVERVIEW (defaultTab) =================
// Ditampilkan begitu user membuka menu "Prediksi" (sebelum klik Transaksi/Prediksi).
// Semua data diambil dari window.transactions & window.predictions, yang sudah
// disinkronkan dari database lewat API.getTransactions() / API.getPredictions().
function renderPredictionOverview() {
    const trx = window.transactions || [];
    const preds = window.predictions || [];

    // ---------- Chart 1: Volume Penjualan Harian (14 Hari Terakhir) ----------
    // Beda dari chart Dashboard (line chart) -> di sini pakai bar chart
    const trendCanvas = document.getElementById('predOverviewTrendChart');
    if (trendCanvas) {
        const dateLabels = [...new Set(trx.map(t => t.tanggal))].sort().slice(-14);
        const dateData = dateLabels.map(date =>
            trx.filter(t => t.tanggal === date).reduce((sum, t) => sum + parseFloat(t.jumlah_kg || 0), 0)
        );

        if (window.charts.predOverviewTrend) window.charts.predOverviewTrend.destroy();
        window.charts.predOverviewTrend = new Chart(trendCanvas, {
            type: 'bar',
            data: {
                labels: dateLabels.length ? dateLabels : ['Belum ada data'],
                datasets: [{
                    label: 'Total Penjualan (kg)',
                    data: dateLabels.length ? dateData : [0],
                    backgroundColor: '#0d9488',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // ---------- Chart 2: Sebaran Jenis Daging ----------
    // Beda dari chart Dashboard (doughnut) -> di sini pakai polar area
    const meatCanvas = document.getElementById('predOverviewMeatChart');
    if (meatCanvas) {
        const meatTotals = {};
        trx.forEach(t => {
            const n = t.nama_daging || 'Lainnya';
            meatTotals[n] = (meatTotals[n] || 0) + parseFloat(t.jumlah_kg || 0);
        });
        const meatLabels = Object.keys(meatTotals);
        const meatData = Object.values(meatTotals);
        const palette = ['#7c3aed', '#DC143C', '#d97706', '#2563eb', '#db2777', '#0d9488', '#65a30d'];

        if (window.charts.predOverviewMeat) window.charts.predOverviewMeat.destroy();
        window.charts.predOverviewMeat = new Chart(meatCanvas, {
            type: 'polarArea',
            data: {
                labels: meatLabels.length ? meatLabels : ['Belum ada data'],
                datasets: [{
                    data: meatLabels.length ? meatData : [1],
                    backgroundColor: meatLabels.length
                        ? meatLabels.map((_, i) => palette[i % palette.length])
                        : ['#e5e7eb']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // ---------- Chart 3: Ringkasan Prediksi Tersimpan per Jenis Daging ----------
    // Bar horizontal, biar beda tampilan dari chart-chart lain
    const predCanvas = document.getElementById('predOverviewPredictionChart');
    const noPredEl = document.getElementById('predOverviewNoPrediction');
    if (predCanvas) {
        if (!preds.length) {
            predCanvas.classList.add('hidden');
            if (noPredEl) noPredEl.classList.remove('hidden');
        } else {
            predCanvas.classList.remove('hidden');
            if (noPredEl) noPredEl.classList.add('hidden');

            const predTotals = {};
            preds.forEach(p => {
                const n = p.nama_daging || p.jenis_daging || 'Lainnya';
                const kg = parseFloat(p.prediksi_kg || p.jumlah_kg || 0);
                predTotals[n] = (predTotals[n] || 0) + kg;
            });
            const predLabels = Object.keys(predTotals);
            const predData = Object.values(predTotals);

            if (window.charts.predOverviewPrediction) window.charts.predOverviewPrediction.destroy();
            window.charts.predOverviewPrediction = new Chart(predCanvas, {
                type: 'bar',
                data: {
                    labels: predLabels,
                    datasets: [{
                        label: 'Total Prediksi (kg)',
                        data: predData,
                        backgroundColor: '#DC143C',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        }
    }
}


// ================= DASHBOARD & CHARTS =================
async function loadDashboard() {
    const totalTrxDB = window.transactions.length;
    const totalKgDB = window.transactions.reduce((a,b)=>a+parseFloat(b.jumlah_kg),0);

    // Ambil statistik data historis dari file .jsonl (TIDAK ada di tabel transactions)
    let totalTrxHistoris = 0;
    let totalKgHistoris = 0;
    try {
        if (typeof API !== 'undefined' && API.getHistoricalStats) {
            const hist = await API.getHistoricalStats();
            if (hist && hist.status === 'success') {
                totalTrxHistoris = hist.total_transaksi || 0;
                totalKgHistoris = hist.total_kg || 0;
            }
        }
    } catch (err) {
        console.warn('Gagal mengambil statistik historis:', err);
    }

    // Gabungkan manual: data DB + data historis (file)
    const totalTrx = totalTrxDB + totalTrxHistoris;
    const totalKg = totalKgDB + totalKgHistoris;
    const avg = totalTrx > 0 ? (totalKg/totalTrx).toFixed(1) : 0;

    document.getElementById('totalTransactions').textContent = totalTrx;
    document.getElementById('totalSales').textContent = totalKg.toFixed(1);
    document.getElementById('avgDaily').textContent = avg;

    renderPredictionTomorrowCard();
    renderCharts();
}

function renderPredictionTomorrowCard() {
    const kgEl = document.getElementById('predictionTomorrow');
    const accEl = document.getElementById('predictionAccuracy');
    if (!kgEl) return;

    const preds = window.predictions || [];

    if (preds.length === 0) {
        kgEl.textContent = '0';
        if (accEl) {
            accEl.className = 'accuracy-indicator';
            accEl.innerHTML = '<i class="fas fa-info-circle"></i><span>Belum ada prediksi</span>';
        }
        return;
    }

    // Tanggal besok, format YYYY-MM-DD (samakan dengan format kolom `tanggal` dari DB)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];

    const predTomorrow = preds.filter(p => (p.tanggal || '').slice(0, 10) === tomorrowStr);

    if (predTomorrow.length === 0) {
        kgEl.textContent = '0';
        if (accEl) {
            accEl.className = 'accuracy-indicator';
            accEl.innerHTML = '<i class="fas fa-info-circle"></i><span>Tidak ada data besok</span>';
        }
        return;
    }

    const totalKgBesok = predTomorrow.reduce((sum, p) => sum + parseFloat(p.prediksi_kg || 0), 0);
    const avgAkurasi = predTomorrow.reduce((sum, p) => sum + parseFloat(p.akurasi || 0), 0) / predTomorrow.length;

    kgEl.textContent = totalKgBesok.toFixed(1);

    if (accEl) {
        const pct = (avgAkurasi * 100).toFixed(0);
        if (avgAkurasi >= 0.8) {
            accEl.className = 'accuracy-indicator accuracy-high';
            accEl.innerHTML = '<i class="fas fa-check-circle"></i><span>Akurasi Tinggi (' + pct + '%)</span>';
        } else if (avgAkurasi >= 0.6) {
            accEl.className = 'accuracy-indicator accuracy-medium';
            accEl.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Akurasi Sedang (' + pct + '%)</span>';
        } else {
            accEl.className = 'accuracy-indicator accuracy-low';
            accEl.innerHTML = '<i class="fas fa-times-circle"></i><span>Akurasi Rendah (' + pct + '%)</span>';
        }
    }
}

function renderCharts() {
    const trx = window.transactions || [];
    const preds = window.predictions || [];

    // ---------- Chart 1: Tren Penjualan (7 Hari Terakhir) ----------
    const ctxLine = document.getElementById('salesTrendChart');
    if (ctxLine && trx.length) {
        const labels = [...new Set(trx.map(t=>t.tanggal))].sort().slice(-7);
        const dataPoints = labels.map(date => trx.filter(t=>t.tanggal===date).reduce((sum,t)=>sum+parseFloat(t.jumlah_kg),0));

        if (window.charts.line) window.charts.line.destroy();
        window.charts.line = new Chart(ctxLine, {
            type: 'line',
            data: { labels, datasets: [{ label:'Total Penjualan (kg)', data: dataPoints, borderColor:'#DC143C', backgroundColor:'rgba(220,20,60,0.1)', fill:true, tension:0.4 }]},
            options: { responsive:true, maintainAspectRatio:false }
        });
    }

    // ---------- Chart 2: Proporsi Jenis Daging ----------
    const ctxMeat = document.getElementById('meatTypeChart');
    if (ctxMeat) {
        const meatTotals = {};
        trx.forEach(t => {
            const n = t.nama_daging || 'Lainnya';
            meatTotals[n] = (meatTotals[n] || 0) + parseFloat(t.jumlah_kg || 0);
        });
        const meatLabels = Object.keys(meatTotals);
        const meatData = Object.values(meatTotals);
        const palette = ['#DC143C', '#0d9488', '#7c3aed', '#d97706', '#2563eb', '#db2777', '#65a30d'];

        if (window.charts.meatType) window.charts.meatType.destroy();
        window.charts.meatType = new Chart(ctxMeat, {
            type: 'doughnut',
            data: {
                labels: meatLabels.length ? meatLabels : ['Belum ada data'],
                datasets: [{
                    data: meatLabels.length ? meatData : [1],
                    backgroundColor: meatLabels.length
                        ? meatLabels.map((_, i) => palette[i % palette.length])
                        : ['#e5e7eb']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // ---------- Chart 3: Prediksi Penjualan 7 Hari ke Depan ----------
    const ctxPred = document.getElementById('predictionChart');
    if (ctxPred) {
        const today = new Date();
        const next7 = [];
        for (let i = 1; i <= 7; i++) {
            const d = new Date(today);
            d.setDate(d.getDate() + i);
            next7.push(d.toISOString().split('T')[0]);
        }
        const predData = next7.map(date =>
            preds.filter(p => (p.tanggal || '').slice(0, 10) === date)
                 .reduce((sum, p) => sum + parseFloat(p.prediksi_kg || p.jumlah_kg || 0), 0)
        );

        if (window.charts.prediction) window.charts.prediction.destroy();
        window.charts.prediction = new Chart(ctxPred, {
            type: 'bar',
            data: {
                labels: next7,
                datasets: [{
                    label: 'Prediksi Penjualan (kg)',
                    data: predData,
                    backgroundColor: '#7c3aed',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        const accEl = document.getElementById('predictionAccuracyText');
        if (accEl && preds.length) {
            const avgAkurasi = preds.reduce((sum, p) => sum + parseFloat(p.akurasi || 0), 0) / preds.length;
            accEl.textContent = (avgAkurasi >= 0.8 ? 'Tinggi' : avgAkurasi >= 0.6 ? 'Sedang' : 'Rendah') + ' (' + (avgAkurasi * 100).toFixed(0) + '%)';
        }
    }
}

// ================= UI HELPERS =================
function updateCurrentDate() {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}
function setTodayDate() {
    const el = document.getElementById('inputDate');
    if (el) el.value = new Date().toISOString().split('T')[0];
}
function showLoading() { document.getElementById('loadingOverlay')?.classList.add('show'); }
function hideLoading() { document.getElementById('loadingOverlay')?.classList.remove('show'); }

// ================= MODAL SYSTEM (Konfirmasi) =================
window._modalConfirmCallback = null;

(function injectModalStyles() {
    if (document.getElementById('customModalStyles')) return;
    const style = document.createElement('style');
    style.id = 'customModalStyles';
    style.textContent = `
        #modal.modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        #modal.modal .modal-content {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 400px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
    `;
    document.head.appendChild(style);
})();

window.openModal = function(title, message, onConfirm, confirmLabel) {
    const modal = document.getElementById('modal');
    if (!modal) { if (onConfirm) onConfirm(); return; }

    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;

    const confirmBtn = document.getElementById('modalConfirmBtn');
    if (confirmBtn) confirmBtn.textContent = confirmLabel || 'Hapus';

    window._modalConfirmCallback = onConfirm;
    modal.style.display = 'flex';
};

window.closeModal = function() {
    const modal = document.getElementById('modal');
    if (modal) modal.style.display = 'none';
    window._modalConfirmCallback = null;
};

window.confirmModalAction = function() {
    const cb = window._modalConfirmCallback;
    closeModal();
    if (typeof cb === 'function') cb();
};

// ================= LOGOUT =================
window.logout = function() {
    openModal(
        'Konfirmasi Logout',
        'Apakah Anda yakin ingin keluar dari aplikasi?',
        function() {
            // Bersihkan data sesi/login yang mungkin tersimpan di browser
            try {
                sessionStorage.removeItem('loggedIn');
                sessionStorage.removeItem('username');
                localStorage.removeItem('loggedIn');
                localStorage.removeItem('username');
            } catch (e) {
                console.warn('Gagal membersihkan session:', e);
            }

            // Arahkan ke halaman login
            window.location.href = 'login.html';
        },
        'Logout'
    );
};

window.toggleSidebar = () => { document.getElementById('sidebar')?.classList.toggle('show'); document.getElementById('mobileOverlay')?.classList.toggle('show'); };
window.closeSidebarOnMobile = () => { document.getElementById('sidebar')?.classList.remove('show'); document.getElementById('mobileOverlay')?.classList.remove('show'); };

function initDarkMode() {
    if (localStorage.getItem('darkMode')==='true') {
        document.body.classList.add('dark-mode');
        document.getElementById('darkModeIcon')?.classList.replace('fa-moon','fa-sun');
    }
}
window.toggleDarkMode = () => {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode',isDark);
    const icon = document.getElementById('darkModeIcon');
    isDark ? icon.classList.replace('fa-moon','fa-sun') : icon.classList.replace('fa-sun','fa-moon');
};



// ================= KONFIGURASI =================

var REPORT_CONFIG = {
    // GANTI: URL logo bulat JPR
    logo: 'assets/logo.png',
    // GANTI: URL gambar tanda tangan
    tandaTangan: 'assets/ttd.png',
    // GANTI: Nama & jabatan
    namaPimpinan: 'Fathan Ramadavi',
    jabatanPimpinan: 'Pemilik Toko Daging Bekasi',
    kota: 'Bekasi',

   
   
};

// ================= LOAD DATA DARI DATABASE VIA PHP =================

// ================= HELPER =================
function formatTanggal(dateStr) {
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    } catch(e) { return dateStr; }
}

function getTanggalLengkap() {
    var now = new Date();
    var hari  = now.toLocaleDateString('id-ID', { weekday: 'long' });
    var tgl   = now.getDate();
    var bulan = now.toLocaleDateString('id-ID', { month: 'long' });
    var tahun = now.getFullYear();
    return REPORT_CONFIG.kota + ', ' + hari + ', ' + tgl + ' ' + bulan + ' ' + tahun;
}

function getTanggalSaja() {
    var now = new Date();
    var hari  = now.toLocaleDateString('id-ID', { weekday: 'long' });
    var tgl   = now.getDate();
    var bulan = now.toLocaleDateString('id-ID', { month: 'long' });
    var tahun = now.getFullYear();
    return hari + ', ' + tgl + ' ' + bulan + ' ' + tahun;
}

function getTypeName(type) {
    return {
        transaction: 'Laporan Transaksi',
        frequency: 'Laporan Frekuensi Penjualan',
        bestmeat: 'Laporan Jenis Daging Terlaris',
        avgweight: 'Laporan Rata-rata Berat Pembelian'
    }[type] || 'Laporan';
}

// ================= RENDER ON-SCREEN (TIDAK BERUBAH) =================
window.generateReport = function() {
    var type = document.getElementById('reportType').value;
    var container = document.getElementById('reportContainer');
    if (!container) return;
    switch(type) {
        case 'transaction': renderTransactionReport(container); break;
        case 'frequency':   renderFrequencyReport(container);   break;
        case 'bestmeat':    renderBestMeatReport(container);    break;
        case 'avgweight':   renderAvgWeightReport(container);   break;
    }
};

// ---------- 1. LAPORAN TRANSAKSI ----------
function renderTransactionReport(container) {
    var trx = window.transactions || [];
    var totalTrx = trx.length;
    var totalKg = 0; for (var i=0;i<trx.length;i++) totalKg += parseFloat(trx[i].jumlah_kg);
    var grouped = {};
    for (var i=0;i<trx.length;i++) {
        if (!grouped[trx[i].tanggal]) grouped[trx[i].tanggal] = [];
        grouped[trx[i].tanggal].push(trx[i]);
    }
    var dates = Object.keys(grouped).sort(function(a,b){return b.localeCompare(a);});
    var summaryRows = '';
    for (var i=0;i<dates.length;i++) {
        var items = grouped[dates[i]];
        var berat = 0; for (var j=0;j<items.length;j++) berat += parseFloat(items[j].jumlah_kg);
        summaryRows += '<tr><td class="px-4 py-3 text-sm text-gray-700">' + formatTanggal(dates[i]) + '</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + items.length + '</td><td class="px-4 py-3 text-sm text-gray-700 text-right font-medium">' + berat.toFixed(1) + ' kg</td></tr>';
    }
    var sorted = trx.slice().sort(function(a,b){return b.tanggal.localeCompare(a.tanggal);});
    var detailRows = '';
    for (var i=0;i<sorted.length;i++) {
        detailRows += '<tr><td class="px-4 py-3 text-sm text-gray-500">' + (i+1) + '</td><td class="px-4 py-3 text-sm text-gray-700">' + formatTanggal(sorted[i].tanggal) + '</td><td class="px-4 py-3 text-sm text-gray-700 font-medium">' + sorted[i].nama_daging + '</td><td class="px-4 py-3 text-sm text-red-600 font-bold text-center">' + parseFloat(sorted[i].jumlah_kg).toFixed(1) + ' kg</td></tr>';
    }
    container.innerHTML =
        '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center"><i class="fas fa-receipt text-blue-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Total Transaksi</p><p class="text-xl font-bold text-gray-800">' + totalTrx + '</p></div></div></div>' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center"><i class="fas fa-weight-hanging text-green-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Total Berat Terjual</p><p class="text-xl font-bold text-gray-800">' + totalKg.toFixed(1) + ' kg</p></div></div></div>' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center"><i class="fas fa-calendar-day text-amber-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Jumlah Hari</p><p class="text-xl font-bold text-gray-800">' + dates.length + '</p></div></div></div>' +
        '</div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"><div class="px-5 py-4 border-b border-gray-100"><h3 class="text-base font-semibold text-gray-800">Ringkasan Per Tanggal</h3><p class="text-xs text-gray-500 mt-0.5">Transaksi dikelompokkan berdasarkan tanggal</p></div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="bg-gray-50"><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Transaksi</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Berat</th></tr></thead><tbody class="divide-y divide-gray-100">' + (summaryRows || '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data transaksi</td></tr>') + '</tbody></table></div></div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-4"><div class="px-5 py-4 border-b border-gray-100"><h3 class="text-base font-semibold text-gray-800">Detail Semua Transaksi</h3></div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="bg-gray-50"><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Daging</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Berat (kg)</th></tr></thead><tbody class="divide-y divide-gray-100">' + (detailRows || '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data transaksi</td></tr>') + '</tbody></table></div></div>';
}

// ---------- 2. LAPORAN FREKUENSI PENJUALAN ----------
function renderFrequencyReport(container) {
    var trx = window.transactions || [];
    var freq = {};
    for (var i=0;i<trx.length;i++) { freq[trx[i].nama_daging] = (freq[trx[i].nama_daging]||0)+1; }
    var sorted = Object.entries(freq).sort(function(a,b){return b[1]-a[1];});
    var maxFreq = sorted.length>0?sorted[0][1]:1;
    var bars = '';
    for (var i=0;i<sorted.length;i++) {
        var pct = (sorted[i][1]/trx.length*100).toFixed(1);
        var w = (sorted[i][1]/maxFreq*100).toFixed(1);
        bars += '<div class="flex items-center gap-4 mb-4"><div class="w-36 text-sm font-medium text-gray-700 text-right shrink-0">' + sorted[i][0] + '</div><div class="flex-1 bg-gray-100 rounded-full h-8 relative overflow-hidden"><div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-400 flex items-center justify-end pr-3 transition-all duration-500" style="width:' + w + '%"><span class="text-xs font-bold text-white">' + sorted[i][1] + 'x</span></div></div><div class="w-14 text-sm text-gray-500 text-right shrink-0">' + pct + '%</div></div>';
    }
    var tableRows = '';
    for (var i=0;i<sorted.length;i++) {
        var medal = i===0?'bg-amber-100 text-amber-700':i===1?'bg-gray-200 text-gray-600':i===2?'bg-orange-100 text-orange-700':'bg-gray-100 text-gray-500';
        tableRows += '<tr><td class="px-4 py-3 text-sm"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold ' + medal + '">' + (i+1) + '</span></td><td class="px-4 py-3 text-sm font-medium text-gray-700">' + sorted[i][0] + '</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + sorted[i][1] + ' kali</td><td class="px-4 py-3 text-sm text-gray-700 text-right">' + (sorted[i][1]/trx.length*100).toFixed(1) + '%</td></tr>';
    }
    container.innerHTML =
        '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center"><i class="fas fa-chart-bar text-indigo-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Total Penjualan</p><p class="text-xl font-bold text-gray-800">' + trx.length + ' kali</p></div></div></div>' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center"><i class="fas fa-tags text-purple-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Jenis Daging Terjual</p><p class="text-xl font-bold text-gray-800">' + sorted.length + ' jenis</p></div></div></div>' +
        '</div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"><h3 class="text-base font-semibold text-gray-800 mb-1">Frekuensi Penjualan per Jenis Daging</h3><p class="text-xs text-gray-500 mb-6">Semakin panjang bar, semakin sering terjual</p>' + (bars || '<p class="text-sm text-gray-400 text-center py-8">Belum ada data penjualan</p>') + '</div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-4"><div class="px-5 py-4 border-b border-gray-100"><h3 class="text-base font-semibold text-gray-800">Tabel Frekuensi Penjualan</h3></div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="bg-gray-50"><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Peringkat</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Daging</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Frekuensi</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Persentase</th></tr></thead><tbody class="divide-y divide-gray-100">' + (tableRows || '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data</td></tr>') + '</tbody></table></div></div>';
}

// ---------- 3. LAPORAN JENIS DAGING TERLARIS ----------
function renderBestMeatReport(container) {
    var trx = window.transactions || [];
    var stats = {};
    for (var i=0;i<trx.length;i++) {
        var n = trx[i].nama_daging;
        if (!stats[n]) stats[n] = {kg:0,qty:0};
        stats[n].kg += parseFloat(trx[i].jumlah_kg);
        stats[n].qty++;
    }
    var sorted = Object.entries(stats).sort(function(a,b){return b[1].kg-a[1].kg;});
    var totalKg = 0; for (var i=0;i<trx.length;i++) totalKg += parseFloat(trx[i].jumlah_kg);
    var top = sorted[0];
    var cards = '';
    for (var i=0;i<sorted.length;i++) {
        var pct = totalKg>0?(sorted[i][1].kg/totalKg*100).toFixed(1):'0.0';
        var isTop = i===0;
        cards += '<div class="bg-white rounded-xl border-2 ' + (isTop?'border-amber-300 shadow-md':'border-gray-200') + ' p-5 relative">' +
            (isTop?'<div class="absolute -top-3 left-4 bg-amber-500 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1"><i class="fas fa-crown"></i> Terlaris</div>':'') +
            '<div class="flex items-center justify-between mb-3 ' + (isTop?'mt-2':'') + '"><h4 class="text-sm font-semibold text-gray-800">' + sorted[i][0] + '</h4><span class="text-xs font-medium ' + (isTop?'text-amber-600 bg-amber-50':'text-gray-500 bg-gray-50') + ' px-2 py-1 rounded-full">' + pct + '%</span></div>' +
            '<div class="grid grid-cols-2 gap-3"><div><p class="text-xs text-gray-400">Total Berat</p><p class="text-sm font-bold text-gray-800">' + sorted[i][1].kg.toFixed(1) + ' kg</p></div><div><p class="text-xs text-gray-400">Terjual</p><p class="text-sm font-bold text-gray-800">' + sorted[i][1].qty + 'x</p></div></div>' +
            '<div class="mt-3 bg-gray-100 rounded-full h-2"><div class="h-2 rounded-full ' + (isTop?'bg-gradient-to-r from-amber-400 to-amber-500':'bg-gradient-to-r from-gray-300 to-gray-400') + '" style="width:' + pct + '%"></div></div></div>';
    }
    var tableRows = '';
    for (var i=0;i<sorted.length;i++) {
        var pct = totalKg>0?(sorted[i][1].kg/totalKg*100).toFixed(1):'0.0';
        var medal = i===0?'bg-amber-400 text-white':i===1?'bg-gray-300 text-gray-700':i===2?'bg-orange-300 text-white':'bg-gray-200 text-gray-500';
        tableRows += '<tr class="' + (i===0?'bg-amber-50/50':'') + '"><td class="px-4 py-3 text-sm"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold ' + medal + '">' + (i+1) + '</span></td><td class="px-4 py-3 text-sm font-medium text-gray-800">' + sorted[i][0] + '</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + sorted[i][1].qty + 'x</td><td class="px-4 py-3 text-sm text-gray-700 text-center font-medium">' + sorted[i][1].kg.toFixed(1) + ' kg</td><td class="px-4 py-3 text-sm text-gray-700 text-right">' + pct + '%</td></tr>';
    }
    container.innerHTML =
        (top ? '<div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl p-6 mb-6 text-white shadow-lg"><div class="flex items-center gap-4"><div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center"><i class="fas fa-trophy text-2xl"></i></div><div><p class="text-amber-100 text-sm font-medium">Daging Terlaris</p><h2 class="text-2xl font-bold">' + top[0] + '</h2><p class="text-amber-100 text-sm mt-0.5">Total: ' + top[1].kg.toFixed(1) + ' kg &middot; ' + top[1].qty + ' kali terjual</p></div></div></div>' : '') +
        '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">' + (cards || '<p class="text-sm text-gray-400 text-center py-8 col-span-2">Belum ada data</p>') + '</div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"><div class="px-5 py-4 border-b border-gray-100"><h3 class="text-base font-semibold text-gray-800">Peringkat Berdasarkan Total Berat Terjual</h3></div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="bg-gray-50"><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Daging</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Berat</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontribusi</th></tr></thead><tbody class="divide-y divide-gray-100">' + (tableRows || '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data</td></tr>') + '</tbody></table></div></div>';
}

// ---------- 4. LAPORAN RATA-RATA BERAT ----------
function renderAvgWeightReport(container) {
    var trx = window.transactions || [];
    var ws = {};
    for (var i=0;i<trx.length;i++) {
        var n=trx[i].nama_daging, kg=parseFloat(trx[i].jumlah_kg);
        if (!ws[n]) ws[n]={totalKg:0,qty:0,minKg:Infinity,maxKg:-Infinity};
        ws[n].totalKg+=kg; ws[n].qty++;
        if (kg<ws[n].minKg) ws[n].minKg=kg;
        if (kg>ws[n].maxKg) ws[n].maxKg=kg;
    }
    var sorted = [];
    var keys = Object.keys(ws);
    for (var i=0;i<keys.length;i++) {
        var d=ws[keys[i]];
        sorted.push({jenis:keys[i],avgKg:d.totalKg/d.qty,minKg:d.minKg,maxKg:d.maxKg,totalKg:d.totalKg,qty:d.qty});
    }
    sorted.sort(function(a,b){return b.avgKg-a.avgKg;});
    var totalKg=0; for(var i=0;i<trx.length;i++) totalKg+=parseFloat(trx[i].jumlah_kg);
    var overallAvg = trx.length>0?totalKg/trx.length:0;
    var maxAvg = sorted.length>0?sorted[0].avgKg:1;
    var barColors = ['from-emerald-500 to-teal-400','from-blue-500 to-cyan-400','from-violet-500 to-purple-400','from-rose-500 to-pink-400','from-amber-500 to-yellow-400','from-indigo-500 to-blue-400'];
    var bars = '';
    for (var i=0;i<sorted.length;i++) {
        var w = (sorted[i].avgKg/maxAvg*100).toFixed(1);
        bars += '<div class="flex items-center gap-4 mb-4"><div class="w-36 text-sm font-medium text-gray-700 text-right shrink-0">' + sorted[i].jenis + '</div><div class="flex-1 bg-gray-100 rounded-full h-8 relative overflow-hidden"><div class="h-full rounded-full bg-gradient-to-r ' + barColors[i%barColors.length] + ' flex items-center justify-end pr-3 transition-all duration-500" style="width:' + w + '%"><span class="text-xs font-bold text-white">' + sorted[i].avgKg.toFixed(1) + ' kg</span></div></div></div>';
    }
    var tableRows = '';
    for (var i=0;i<sorted.length;i++) {
        tableRows += '<tr class="' + (i===0?'bg-teal-50/50':'') + '"><td class="px-4 py-3 text-sm font-medium text-gray-800">' + sorted[i].jenis + '</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + sorted[i].qty + 'x</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + (sorted[i].minKg===Infinity?'-':sorted[i].minKg.toFixed(1)) + '</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + (sorted[i].maxKg===-Infinity?'-':sorted[i].maxKg.toFixed(1)) + '</td><td class="px-4 py-3 text-sm text-gray-700 text-center">' + sorted[i].totalKg.toFixed(1) + '</td><td class="px-4 py-3 text-center"><span class="inline-block bg-teal-100 text-teal-700 text-sm font-bold px-3 py-1 rounded-full">' + sorted[i].avgKg.toFixed(2) + '</span></td></tr>';
    }
    container.innerHTML =
        '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-teal-100 flex items-center justify-center"><i class="fas fa-balance-scale text-teal-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Rata-rata Keseluruhan</p><p class="text-xl font-bold text-gray-800">' + overallAvg.toFixed(2) + ' kg</p></div></div></div>' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center"><i class="fas fa-arrow-up text-blue-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Rata-rata Tertinggi</p><p class="text-xl font-bold text-gray-800">' + (sorted.length>0?sorted[0].avgKg.toFixed(2)+' kg':'-') + '</p></div></div></div>' +
            '<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center"><i class="fas fa-arrow-down text-rose-600"></i></div><div><p class="text-xs text-gray-500 uppercase tracking-wide">Rata-rata Terendah</p><p class="text-xl font-bold text-gray-800">' + (sorted.length>0?sorted[sorted.length-1].avgKg.toFixed(2)+' kg':'-') + '</p></div></div></div>' +
        '</div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"><h3 class="text-base font-semibold text-gray-800 mb-1">Rata-rata Berat Pembelian per Jenis Daging</h3><p class="text-xs text-gray-500 mb-6">Perbandingan rata-rata berat dalam setiap transaksi</p>' + (bars || '<p class="text-sm text-gray-400 text-center py-8">Belum ada data</p>') + '</div>' +
        '<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-4"><div class="px-5 py-4 border-b border-gray-100"><h3 class="text-base font-semibold text-gray-800">Detail Statistik Berat per Jenis Daging</h3></div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="bg-gray-50"><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Daging</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Transaksi</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Min (kg)</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Max (kg)</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total (kg)</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Rata-rata (kg)</th></tr></thead><tbody class="divide-y divide-gray-100">' + (tableRows || '<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data</td></tr>') + '</tbody></table></div></div>';
}

// ========================================================================
// === KOP SURAT — DIBANGUN DENGAN HTML/CSS BUKAN GAMBAR (RAPI & STABIL) ===
// ========================================================================
function buildKopSuratHTML() {
    return `
    <div style="padding:14px 0 0 0;">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <!-- Logo -->
                <td style="width:80px;padding-left:10px;vertical-align:top;">
                    <img src="${REPORT_CONFIG.logo}"
                        style="width:60px;height:60px;object-fit:contain;display:block;"
                        crossorigin="anonymous"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
                    <div style="display:none;width:60px;height:60px;border:2px solid #292524;
                        border-radius:8px;align-items:center;justify-content:center;
                        font-size:20px;font-weight:bold;">
                        JPR
                    </div>
                </td>

                <!-- Judul + Alamat -->
                <td style="text-align:center;padding-right:80px;">

                    <div style="
                        font-size:22px;
                        font-weight:900;
                        letter-spacing:2px;
                        color:#111827;">
                        TOKO DAGING BEKASI
                    </div>

                    <div style="
                        font-size:11px;
                        color:#555;
                        margin-top:2px;">
                        ( PT. JAGAD PANGAN RIPAH )
                    </div>

                    <div style="
                        margin-top:8px;
                        font-size:10px;
                        line-height:1.6;
                        color:#444;">
                        Gang Wareng Jl. Swadaya Ujung No.6<br>
                        Kec. Tambun Selatan<br>
                        Kab. Bekasi, Jawa Barat 17510
                    </div>

                </td>
            </tr>
        </table>

        <div style="margin:10px 10px 0;border-top:3px double #292524;"></div>
    </div>
    `;
}

// ========================================================================
// === TANDA TANGAN PDF ==========================
// ========================================================================
function buildTandaTanganHTML() {
    return '<div style="padding:20px 28px 32px;display:flex;justify-content:flex-end;">' +
        '<div style="text-align:center;min-width:200px;">' +
            '<div style="font-size:10.5px;color:#292524;font-weight:500;">' + REPORT_CONFIG.kota + ', ' + getTanggalSaja() + '</div>' +
            '<div style="font-size:10.5px;font-weight:700;color:#1c1917;margin-top:1px;">' + REPORT_CONFIG.jabatanPimpinan + '</div>' +
            '<div style="height:60px;display:flex;align-items:center;justify-content:center;">' +
                '<img src="' + REPORT_CONFIG.tandaTangan + '" style="height:48px;display:block;" crossorigin="anonymous" onerror="this.style.display=\'none\'" />' +
            '</div>' +
            '<div style="font-size:10.5px;font-weight:700;color:#1c1917;text-decoration:underline;margin-top:-2px;">' + REPORT_CONFIG.namaPimpinan + '</div>' +
            '<div style="font-size:9.5px;color:#78716c;margin-top:1px;">' + REPORT_CONFIG.jabatanPimpinan + '</div>' +
        '</div>' +
    '</div>';
}

// ========================================================================
// === PDF CONTENT BUILDERS (INLINE STYLES) ============================
// ========================================================================
function pdfTable(headers, rows) {
    var h = '<table style="width:100%;border-collapse:collapse;border:1px solid #d6d3d1;margin-bottom:14px;">';
    h += '<thead><tr style="background:#fafaf9;">';
    for (var i=0;i<headers.length;i++) {
        h += '<th style="padding:6px 10px;text-align:' + (headers[i].a||'left') + ';font-size:9px;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #d6d3d1;">' + headers[i].t + '</th>';
    }
    h += '</tr></thead><tbody>';
    for (var r=0;r<rows.length;r++) {
        h += '<tr>';
        for (var c=0;c<rows[r].length;c++) {
            h += '<td style="padding:5px 10px;font-size:10px;color:#292524;' + (rows[r][c].s||'') + 'border-bottom:1px solid #e7e5e4;">' + rows[r][c].v + '</td>';
        }
        h += '</tr>';
    }
    h += '</tbody></table>';
    return h;
}

function pdfCards(items) {
    var h = '<div style="display:flex;gap:8px;margin-bottom:14px;">';
    for (var i=0;i<items.length;i++) {
        h += '<div style="flex:1;border:1px solid #d6d3d1;border-radius:6px;padding:10px;">' +
            '<div style="font-size:8px;color:#a8a29e;text-transform:uppercase;letter-spacing:0.5px;">' + items[i].l + '</div>' +
            '<div style="font-size:17px;font-weight:800;color:#1c1917;margin-top:2px;">' + items[i].v + '</div>' +
        '</div>';
    }
    h += '</div>';
    return h;
}

function pdfBars(items, color) {
    var maxV = 0;
    for (var i=0;i<items.length;i++) { if (items[i].v>maxV) maxV=items[i].v; }
    var colors = ['#B91C1C','#0d9488','#7c3aed','#db2777','#d97706','#2563eb'];
    var h = '<div style="margin-bottom:14px;">';
    for (var i=0;i<items.length;i++) {
        var pct = maxV>0?(items[i].v/maxV*100):0;
        var c = color || colors[i%colors.length];
        h += '<div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">' +
            '<div style="width:120px;font-size:10px;font-weight:600;text-align:right;color:#292524;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + items[i].l + '</div>' +
            '<div style="flex:1;background:#f5f5f4;border-radius:99px;height:20px;overflow:hidden;">' +
                '<div style="height:100%;border-radius:99px;background:' + c + ';width:' + pct + '%;display:flex;align-items:center;justify-content:flex-end;padding-right:6px;min-width:30px;">' +
                    '<span style="font-size:9px;font-weight:700;color:white;">' + items[i].d + '</span>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    h += '</div>';
    return h;
}

function buildPDFContent(type) {
    switch(type) {
        case 'transaction': return buildPDFTransaction();
        case 'frequency':   return buildPDFFrequency();
        case 'bestmeat':    return buildPDFBestMeat();
        case 'avgweight':   return buildPDFAvgWeight();
        default: return '<p style="text-align:center;color:#a8a29e;padding:40px;">Tidak ada data</p>';
    }
}

function buildPDFTransaction() {
    var trx = window.transactions || [];
    var totalTrx = trx.length;
    var totalKg = 0; for (var i=0;i<trx.length;i++) totalKg += parseFloat(trx[i].jumlah_kg);
    var grouped = {};
    for (var i=0;i<trx.length;i++) {
        if (!grouped[trx[i].tanggal]) grouped[trx[i].tanggal] = [];
        grouped[trx[i].tanggal].push(trx[i]);
    }
    var dates = Object.keys(grouped).sort(function(a,b){return b.localeCompare(a);});
    var content = pdfCards([{l:'Total Transaksi',v:totalTrx},{l:'Total Berat Terjual',v:totalKg.toFixed(1)+' kg'},{l:'Jumlah Hari',v:dates.length}]);
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:4px;">Ringkasan Per Tanggal</div>';
    var sRows = [];
    for (var i=0;i<dates.length;i++) {
        var items=grouped[dates[i]], berat=0; for(var j=0;j<items.length;j++) berat+=parseFloat(items[j].jumlah_kg);
        sRows.push([{v:formatTanggal(dates[i])},{v:items.length+'',s:'text-align:center;'},{v:berat.toFixed(1)+' kg',s:'text-align:right;font-weight:700;'}]);
    }
    content += pdfTable([{t:'Tanggal'},{t:'Jumlah Trx',a:'center'},{t:'Total Berat',a:'right'}], sRows);
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:4px;">Detail Semua Transaksi</div>';
    var sorted = trx.slice().sort(function(a,b){return b.tanggal.localeCompare(a.tanggal);});
    var dRows = [];
    for (var i=0;i<sorted.length;i++) {
        dRows.push([{v:(i+1)+'',s:'text-align:center;color:#a8a29e;'},{v:formatTanggal(sorted[i].tanggal)},{v:sorted[i].nama_daging,s:'font-weight:600;'},{v:parseFloat(sorted[i].jumlah_kg).toFixed(1)+' kg',s:'text-align:center;font-weight:700;color:#B91C1C;'}]);
    }
    content += pdfTable([{t:'No',a:'center'},{t:'Tanggal'},{t:'Jenis Daging'},{t:'Berat (kg)',a:'center'}], dRows);
    return content;
}

function buildPDFFrequency() {
    var trx = window.transactions || [];
    var freq = {};
    for (var i=0;i<trx.length;i++) { freq[trx[i].nama_daging]=(freq[trx[i].nama_daging]||0)+1; }
    var sorted = Object.entries(freq).sort(function(a,b){return b[1]-a[1];});
    var content = pdfCards([{l:'Total Penjualan',v:trx.length+' kali'},{l:'Jenis Daging Terjual',v:sorted.length+' jenis'}]);
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:3px;">Frekuensi Penjualan per Jenis Daging</div>';
    content += '<div style="font-size:9px;color:#a8a29e;margin-bottom:8px;">Semakin panjang bar, semakin sering terjual</div>';
    var barItems = [];
    for (var i=0;i<sorted.length;i++) barItems.push({l:sorted[i][0],v:sorted[i][1],d:sorted[i][1]+'x'});
    content += pdfBars(barItems, '#B91C1C');
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:4px;">Tabel Frekuensi Penjualan</div>';
    var rows = [];
    for (var i=0;i<sorted.length;i++) {
        rows.push([{v:(i+1)+'',s:'text-align:center;'},{v:sorted[i][0],s:'font-weight:600;'},{v:sorted[i][1]+' kali',s:'text-align:center;'},{v:(sorted[i][1]/trx.length*100).toFixed(1)+'%',s:'text-align:right;'}]);
    }
    content += pdfTable([{t:'Peringkat'},{t:'Jenis Daging'},{t:'Frekuensi',a:'center'},{t:'Persentase',a:'right'}], rows);
    return content;
}

function buildPDFBestMeat() {
    var trx = window.transactions || [];
    var stats = {};
    for (var i=0;i<trx.length;i++) {
        var n=trx[i].nama_daging;
        if (!stats[n]) stats[n]={kg:0,qty:0};
        stats[n].kg += parseFloat(trx[i].jumlah_kg);
        stats[n].qty++;
    }
    var sorted = Object.entries(stats).sort(function(a,b){return b[1].kg-a[1].kg;});
    var totalKg = 0; for (var i=0;i<trx.length;i++) totalKg += parseFloat(trx[i].jumlah_kg);
    var top = sorted[0];
    var content = '';
    if (top) {
        content += '<div style="background:linear-gradient(135deg,#d97706,#b91c1c);color:white;border-radius:8px;padding:14px;margin-bottom:14px;">' +
            '<div style="display:flex;align-items:center;gap:12px;">' +
                '<div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:18px;">&#9733;</div>' +
                '<div><div style="font-size:10px;color:#fde68a;">Daging Terlaris</div>' +
                '<div style="font-size:18px;font-weight:800;">' + top[0] + '</div>' +
                '<div style="font-size:10px;color:#fde68a;margin-top:1px;">Total: ' + top[1].kg.toFixed(1) + ' kg &middot; ' + top[1].qty + ' kali terjual</div></div>' +
            '</div></div>';
    }
    content += '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">';
    for (var i=0;i<sorted.length;i++) {
        var pct = totalKg>0?(sorted[i][1].kg/totalKg*100).toFixed(1):'0.0';
        var isTop = i===0;
        content += '<div style="flex:1;min-width:170px;border:' + (isTop?'2px solid #d97706':'1px solid #d6d3d1') + ';border-radius:8px;padding:10px;position:relative;">' +
            (isTop?'<div style="position:absolute;top:-8px;left:10px;background:#d97706;color:white;font-size:8px;font-weight:700;padding:1px 8px;border-radius:99px;">Terlaris</div>':'') +
            '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;' + (isTop?'margin-top:3px;':'') + '">' +
                '<span style="font-size:11px;font-weight:700;color:#1c1917;">' + sorted[i][0] + '</span>' +
                '<span style="font-size:9px;font-weight:600;color:' + (isTop?'#d97706':'#78716c') + ';background:' + (isTop?'#fffbeb':'#f5f5f4') + ';padding:1px 6px;border-radius:99px;">' + pct + '%</span>' +
            '</div>' +
            '<div style="display:flex;gap:12px;">' +
                '<div><div style="font-size:8px;color:#a8a29e;">Total Berat</div><div style="font-size:11px;font-weight:700;">' + sorted[i][1].kg.toFixed(1) + ' kg</div></div>' +
                '<div><div style="font-size:8px;color:#a8a29e;">Terjual</div><div style="font-size:11px;font-weight:700;">' + sorted[i][1].qty + 'x</div></div>' +
            '</div>' +
            '<div style="margin-top:5px;background:#f5f5f4;border-radius:99px;height:4px;overflow:hidden;"><div style="height:100%;border-radius:99px;background:' + (isTop?'#d97706':'#a8a29e') + ';width:' + pct + '%;"></div></div>' +
        '</div>';
    }
    content += '</div>';
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:4px;">Peringkat Berdasarkan Total Berat Terjual</div>';
    var rows = [];
    for (var i=0;i<sorted.length;i++) {
        var pct = totalKg>0?(sorted[i][1].kg/totalKg*100).toFixed(1):'0.0';
        rows.push([{v:(i+1)+'',s:'text-align:center;'},{v:sorted[i][0],s:'font-weight:600;'},{v:sorted[i][1].qty+'x',s:'text-align:center;'},{v:sorted[i][1].kg.toFixed(1)+' kg',s:'text-align:center;font-weight:600;'},{v:pct+'%',s:'text-align:right;'}]);
    }
    content += pdfTable([{t:'#'},{t:'Jenis Daging'},{t:'Qty',a:'center'},{t:'Total Berat',a:'center'},{t:'Kontribusi',a:'right'}], rows);
    return content;
}

function buildPDFAvgWeight() {
    var trx = window.transactions || [];
    var ws = {};
    for (var i=0;i<trx.length;i++) {
        var n=trx[i].nama_daging, kg=parseFloat(trx[i].jumlah_kg);
        if (!ws[n]) ws[n]={totalKg:0,qty:0,minKg:Infinity,maxKg:-Infinity};
        ws[n].totalKg+=kg; ws[n].qty++;
        if (kg<ws[n].minKg) ws[n].minKg=kg;
        if (kg>ws[n].maxKg) ws[n].maxKg=kg;
    }
    var sorted = [];
    var keys = Object.keys(ws);
    for (var i=0;i<keys.length;i++) {
        var d=ws[keys[i]];
        sorted.push({jenis:keys[i],avgKg:d.totalKg/d.qty,minKg:d.minKg,maxKg:d.maxKg,totalKg:d.totalKg,qty:d.qty});
    }
    sorted.sort(function(a,b){return b.avgKg-a.avgKg;});
    var totalKg=0; for(var i=0;i<trx.length;i++) totalKg+=parseFloat(trx[i].jumlah_kg);
    var overallAvg = trx.length>0?totalKg/trx.length:0;
    var content = pdfCards([{l:'Rata-rata Keseluruhan',v:overallAvg.toFixed(2)+' kg'},{l:'Rata-rata Tertinggi',v:sorted.length>0?sorted[0].avgKg.toFixed(2)+' kg':'-'},{l:'Rata-rata Terendah',v:sorted.length>0?sorted[sorted.length-1].avgKg.toFixed(2)+' kg':'-'}]);
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:3px;">Rata-rata Berat Pembelian per Jenis Daging</div>';
    content += '<div style="font-size:9px;color:#a8a29e;margin-bottom:8px;">Perbandingan rata-rata berat dalam setiap transaksi</div>';
    var barItems = [];
    for (var i=0;i<sorted.length;i++) barItems.push({l:sorted[i].jenis,v:sorted[i].avgKg,d:sorted[i].avgKg.toFixed(1)+' kg'});
    content += pdfBars(barItems, null);
    content += '<div style="font-size:12px;font-weight:700;color:#1c1917;margin-bottom:4px;">Detail Statistik Berat per Jenis Daging</div>';
    var rows = [];
    for (var i=0;i<sorted.length;i++) {
        rows.push([{v:sorted[i].jenis,s:'font-weight:600;'},{v:sorted[i].qty+'x',s:'text-align:center;'},{v:sorted[i].minKg===Infinity?'-':sorted[i].minKg.toFixed(1),s:'text-align:center;'},{v:sorted[i].maxKg===-Infinity?'-':sorted[i].maxKg.toFixed(1),s:'text-align:center;'},{v:sorted[i].totalKg.toFixed(1),s:'text-align:center;'},{v:sorted[i].avgKg.toFixed(2),s:'text-align:center;font-weight:700;background:#ccfbf1;color:#0f766e;padding:1px 6px;border-radius:99px;'}]);
    }
    content += pdfTable([{t:'Jenis Daging'},{t:'Jml Trx',a:'center'},{t:'Min',a:'center'},{t:'Max',a:'center'},{t:'Total',a:'center'},{t:'Rata-rata',a:'center'}], rows);
    return content;
}

// ========================================================================
// === PREVIEW — KLIK DOWNLOAD LAPORAN → MUNCUL PREVIEW DULU ============
// ========================================================================
window.downloadReport = function() {
    var trx = window.transactions || [];
    if (trx.length === 0) {
        alert('Belum ada data transaksi.');
        return;
    }
    showPreview(document.getElementById('reportType').value);
};

function showPreview(type) {
    var typeName = getTypeName(type);
    var reportContent = buildPDFContent(type);

    // Update judul di toolbar
    document.getElementById('previewTitle').textContent = typeName;

    // Render halaman A4 ke dalam preview
    var pageDiv = document.getElementById('pdfPageContent');
    pageDiv.style.cssText = 'width:794px;min-height:1123px;background:#ffffff;font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;color:#292524;box-shadow:0 4px 24px rgba(0,0,0,0.3);border-radius:2px;';
    pageDiv.innerHTML =
        '<div style="width:794px;min-height:1123px;background:#ffffff;">' +
            buildKopSuratHTML() +
            '<div style="text-align:center;padding:14px 28px 0;">' +
                '<div style="font-size:14px;font-weight:800;color:#1c1917;text-transform:uppercase;letter-spacing:2px;">' + typeName + '</div>' +
                '<div style="font-size:10px;color:#78716c;margin-top:3px;">' + getTanggalLengkap() + '</div>' +
                '<div style="margin:10px 0 0;border:none;border-top:1px solid #d6d3d1;"></div>' +
            '</div>' +
            '<div style="padding:12px 28px;">' + reportContent + '</div>' +
            buildTandaTanganHTML() +
        '</div>';

    // Tampilkan overlay
    var overlay = document.getElementById('pdfPreviewOverlay');
    overlay.style.display = 'flex';
    overlay.dataset.reportType = type;
    document.body.style.overflow = 'hidden';

    // Scroll ke atas
    overlay.querySelector('div[style*="overflow:auto"]').scrollTop = 0;
}

function tutupPreview() {
    document.getElementById('pdfPreviewOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

// ========================================================================
// === CETAK PDF DARI PREVIEW (PWA/APK COMPATIBLE) ======================
// ========================================================================
async function cetakDariPreview() {
    var type = document.getElementById('pdfPreviewOverlay').dataset.reportType;
    var typeName = getTypeName(type);

    document.getElementById('loadingOverlay').style.display = 'flex';

    // Cek library
    if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        fallbackPrint(type);
        document.getElementById('loadingOverlay').style.display = 'none';
        return;
    }

    try {
        var target = document.getElementById('pdfPageContent');

        // Scroll ke atas dulu supaya seluruh konten ter-render
        var scrollContainer = target.parentElement;
        scrollContainer.scrollTop = 0;

        await new Promise(function(r){ setTimeout(r, 500); });

        var canvas = await html2canvas(target, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            width: 794,
            windowWidth: 794,
            logging: false,
            backgroundColor: '#ffffff'
        });

        var jsPDF = window.jspdf.jsPDF;
        var pdf = new jsPDF('p', 'mm', 'a4');
        var pageWidth = pdf.internal.pageSize.getWidth();
        var pageHeight = pdf.internal.pageSize.getHeight();
        var imgWidth = pageWidth;
        var imgHeight = (canvas.height * imgWidth) / canvas.width;
        var heightLeft = imgHeight;
        var position = 0;

        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft > 0) {
            position -= pageHeight;
            pdf.addPage();
            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        // Download via anchor (PWA/APK compatible)
        var fileName = typeName.replace(/\s+/g, '_') + '_' + new Date().toISOString().slice(0,10) + '.pdf';
        var blob = pdf.output('blob');
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 3000);

    } catch(err) {
        console.error('PDF error:', err);
        try { fallbackPrint(type); } catch(e2) { alert('Gagal membuat PDF: ' + e2.message); }
    }

    document.getElementById('loadingOverlay').style.display = 'none';
}

// ================= FALLBACK =================
function fallbackPrint(type) {
    var typeName = getTypeName(type);
    var reportContent = buildPDFContent(type);
    var w = window.open('', '_blank');
    if (!w) { alert('Pop-up diblokir. Izinkan pop-up untuk halaman ini.'); return; }
    w.document.write('<!DOCTYPE html><html><head><title>' + typeName + '</title>' +
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">' +
        '<style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:Segoe UI,Tahoma,sans-serif;color:#292524;}@page{size:A4;margin:0;}@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}</style>' +
        '</head><body>' +
        '<div style="width:210mm;margin:0 auto;background:#fff;">' +
            buildKopSuratHTML().replace(/<img /g, '<img crossorigin="anonymous" ') +
            '<div style="text-align:center;padding:14px 18mm 0;">' +
                '<div style="font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:2px;">' + typeName + '</div>' +
                '<div style="font-size:10px;color:#78716c;margin-top:3px;">' + getTanggalLengkap() + '</div>' +
                '<div style="margin:10px 0 0;border:none;border-top:1px solid #d6d3d1;"></div>' +
            '</div>' +
            '<div style="padding:12px 18mm;">' + reportContent + '</div>' +
            buildTandaTanganHTML().replace(/padding:20px 28px/g, 'padding:20px 18mm') +
        '</div>' +
        '<script>setTimeout(function(){window.print();},1000);<\/script>' +
        '</body></html>');
    w.document.close();
}