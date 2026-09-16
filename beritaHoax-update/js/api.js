const apiClient = {
    baseUrl: "api/",

    // ===== SESSION HELPERS =====
    saveUser: (user) => {
        localStorage.setItem("user", JSON.stringify(user));
    },

    getUserData: () => {
        return JSON.parse(localStorage.getItem("user"));
    },

    isLoggedIn: () => {
        return localStorage.getItem("user") !== null;
    },

    logout: async () => {
        localStorage.removeItem("user");
        return { success: true };
    },

    // ===== AUTH =====
    login: async (email, password) => {
        const res = await fetch(`${apiClient.baseUrl}login.php`, {
            method: "POST",
            body: new URLSearchParams({ email, password })
        });

        const data = await res.json();

        // Perbaikan: Cek apakah data.user ada sebelum disimpan
        if (data.success && data.user) {
            apiClient.saveUser(data.user);
        }

        return data;
    },

    register: async (name, email, password) => {
        const res = await fetch(`${apiClient.baseUrl}register.php`, {
            method: "POST",
            body: new URLSearchParams({ name, email, password })
        });

        const data = await res.json();
        return data;
    },

    verifySession: async () => {
        if (!apiClient.isLoggedIn()) return { success: false };
        return { success: true };
    },

    // ===== NEWS CHECK =====
    checkNews: async (text, link) => {
        const user = apiClient.getUserData();

        const res = await fetch(`${apiClient.baseUrl}check_news.php`, {
            method: "POST",
            body: new URLSearchParams({
                text: text,
                link: link,
                user_id: user ? user.id : null // Safety check
            })
        });

        return res.json();
    },

    // ===== HISTORY =====
    getHistory: async () => {
        const user = apiClient.getUserData();

        const res = await fetch(`${apiClient.baseUrl}get_history.php`, {
            method: "POST",
            body: new URLSearchParams({
                action: "get",
                user_id: user.id
            })
        });

        return res.json();
    },

    deleteHistory: async (id) => {
        const user = apiClient.getUserData();

        const res = await fetch(`${apiClient.baseUrl}get_history.php`, {
            method: "POST",
            body: new URLSearchParams({
                action: "delete_one",
                id: id,
                user_id: user.id
            })
        });

        return res.json();
    },

    // Perbaikan: Pindahkan fungsi ini ke dalam objek apiClient
    getStatistics: async () => {
        const res = await fetch(`${apiClient.baseUrl}get_statistics.php`);
        return res.json();
    }
};