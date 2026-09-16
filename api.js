const API = {

    // ================= BASE URL =================
    // Untuk tes di browser komputer:
    _base: 'http://localhost/tokodaging',
    
    // Untuk tes di HP (PWA/APK) — ganti IP sesuai komputer kamu:
    // _base: 'http://192.168.1.XXX/tokodaging',

    // ================= LOGIN =================
    login: async (username, password) => {
        const res = await fetch(API._base + "/api/login.php", {
            method: "POST",
            body: new URLSearchParams({ username, password })
        });
        return res.text();
    },

    // ================= MEAT TYPES (Dropdown) =================
    getMeatTypes: async () => {
        const res = await fetch(API._base + "/api/get_meat_types.php");
        return res.json();
    },

    // ================= TRANSAKSI =================

    // Ambil semua transaksi
    getTransactions: async () => {
        const res = await fetch(API._base + "/api/transaksi.php", {
            method: "POST",
            body: new URLSearchParams({ action: "get" })
        });
        return res.json();
    },

    // Tambah transaksi
    insertTransaction: async (tanggal, meat_type_id, jumlah_kg, kualitas_daging, status_hari, harga, promo, metode_penjualan) => {
        const res = await fetch(API._base + "/api/transaksi.php", {
            method: "POST",
            body: new URLSearchParams({
                action: "insert",
                tanggal: tanggal,
                meat_type_id: meat_type_id,
                jumlah_kg: jumlah_kg,
                kualitas_daging: kualitas_daging,
                status_hari: status_hari,
                harga: harga,
                promo: promo,
                metode_penjualan: metode_penjualan
            })
        });
        return res.text();
    },

    // Update transaksi
    updateTransaction: async (id, tanggal, meat_type_id, jumlah_kg, kualitas_daging, status_hari, harga, promo, metode_penjualan) => {
        const res = await fetch(API._base + "/api/transaksi.php", {
            method: "POST",
            body: new URLSearchParams({
                action: "update",
                id: id,
                tanggal: tanggal,
                meat_type_id: meat_type_id,
                jumlah_kg: jumlah_kg,
                kualitas_daging: kualitas_daging,
                status_hari: status_hari,
                harga: harga,
                promo: promo,
                metode_penjualan: metode_penjualan
            })
        });
        return res.text();
    },

    // Hapus transaksi
    deleteTransaction: async (id) => {
        const res = await fetch(API._base + "/api/transaksi.php", {
            method: "POST",
            body: new URLSearchParams({
                action: "delete",
                id: id
            })
        });
        return res.text();
    },

    // ================= PREDIKSI =================
    // Menghitung prediksi baru: sekarang murni PHP (naive_bayes.php), TIDAK memanggil Python lagi
    runPrediction: async () => {
        const res = await fetch(API._base + "/api/naive_bayes.php", {
            method: "POST",
            body: new URLSearchParams({ action: "run" })
        });
        return res.json();
    },

    // Mengambil hasil prediksi yang tersimpan, untuk ditampilkan di tabel
    getPredictions: async () => {
        const res = await fetch(API._base + "/api/prediksi.php", {
            method: "POST",
            body: new URLSearchParams({ action: "get" })
        });
        return res.json();
    },

    // ================= STATISTIK DATA HISTORIS (dari file .jsonl, bukan DB) =================
    getHistoricalStats: async () => {
        const res = await fetch(API._base + "/api/historical_stats.php");
        return res.json();
    }
};