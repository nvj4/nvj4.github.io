// --------- Fungsi cek berita (simulasi Naive Bayes) ---------
function cekBerita() {
  const teks = document.getElementById("newsText").value;
  const link = document.getElementById("newsLink").value;
  const hasilDiv = document.getElementById("hasil");

  if (teks.trim() === "" && link.trim() === "") {
    hasilDiv.innerHTML = "<p style='color:red;'>Masukkan teks atau link berita!</p>";
    return;
  }

  // Simulasi hasil (sementara pakai random)
  const probabilitas = Math.random();

  if (probabilitas > 0.5) {
    hasilDiv.className = "result hoax";
    hasilDiv.innerHTML = `🚨 Berita terdeteksi <b>HOAX</b> dengan kemungkinan ${(probabilitas * 100).toFixed(2)}%`;
  } else {
    hasilDiv.className = "result valid";
    hasilDiv.innerHTML = `✅ Berita terdeteksi <b>VALID</b> dengan kemungkinan ${((1 - probabilitas) * 100).toFixed(2)}%`;
  }
}

// --------- PWA Install Prompt ---------
let deferredPrompt;
const installBtn = document.createElement("button");
installBtn.id = "manualInstall";
installBtn.textContent = "📲 Install Aplikasi";
installBtn.style.display = "none"; // sembunyikan dulu
document.body.appendChild(installBtn);

// Trigger event bawaan browser
window.addEventListener("beforeinstallprompt", (e) => {
  e.preventDefault();
  deferredPrompt = e;

  // Munculin banner custom
  if (!document.getElementById("installNotif")) {
    const notif = document.createElement("div");
    notif.id = "installNotif";
    notif.className = "install-banner";
    notif.innerHTML = `
      <span>📲 Pasang aplikasi ini di HP kamu?</span>
      <div class="btn-group">
        <button id="btnInstall">Install</button>
        <button id="btnTutup">Nanti</button>
      </div>
    `;
    document.body.appendChild(notif);

    // Tombol install (popup)
    document.getElementById("btnInstall").addEventListener("click", () => {
      notif.remove();
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choice) => {
        console.log("Pilihan user:", choice.outcome);
        deferredPrompt = null;
      });
    });

    // Tombol tutup → simpan tombol manual sebagai alternatif
    document.getElementById("btnTutup").addEventListener("click", () => {
      notif.remove();
      installBtn.style.display = "block";
    });
  }
});

// Event tombol manual (kalau notif custom udah ditutup)
installBtn.addEventListener("click", () => {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then((choice) => {
    console.log("Pilihan user:", choice.outcome);
    deferredPrompt = null;
    installBtn.style.display = "none";
  });
});
