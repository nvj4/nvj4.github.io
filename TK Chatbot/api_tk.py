import mysql.connector
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from groq import Groq
import uvicorn

# ================= CONFIG =================
GROQ_API_KEY = "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"
MODEL_NAME = "llama-3.3-70b-versatile"

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

client = Groq(api_key=GROQ_API_KEY)

# ===== MEMORY =====
last_murid_by_guru = {}

# ================= DB =================
def get_db():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="tk_db"
    )

# ================= MURID =================
def get_all_murid(guru_id):
    db = get_db()
    cur = db.cursor(dictionary=True)

    cur.execute("""
        SELECT m.id, m.nama_lengkap, k.nama_kelas
        FROM murid m
        JOIN kelas k ON m.kelas_id = k.id
        WHERE k.guru_id=%s AND m.status='aktif'
        ORDER BY m.nama_lengkap
    """, (guru_id,))

    rows = cur.fetchall()
    db.close()
    return rows

# ================= DETEKSI NAMA =================
def detect_murid(pesan, guru_id):
    murid_list = get_all_murid(guru_id)
    text = pesan.lower()

    for m in murid_list:
        nama = m["nama_lengkap"].lower()

        if nama in text:
            return m["id"], m["nama_lengkap"]

        for kata in nama.split():
            if kata in text:
                return m["id"], m["nama_lengkap"]

    return None, None

# ================= KEGIATAN MURID =================
def get_kegiatan_murid(murid_id):
    db = get_db()
    cur = db.cursor(dictionary=True)

    cur.execute("""
        SELECT tanggal, kegiatan, catatan, mood,
               makan_siang, tidur_siang, kebersihan,
               status_hadir
        FROM kegiatan_harian
        WHERE murid_id=%s
        ORDER BY tanggal DESC
    """, (murid_id,))

    rows = cur.fetchall()
    db.close()
    return rows

# ================= ANALISIS KELAS =================
def analisis_kelas(guru_id):
    murid = get_all_murid(guru_id)

    if not murid:
        return "MODE:KELAS\nTIDAK_ADA"

    db = get_db()
    cur = db.cursor(dictionary=True)

    ctx = "MODE:KELAS\n"

    for m in murid:
        cur.execute("""
            SELECT COUNT(*) total,
                   SUM(status_hadir='hadir') hadir
            FROM kegiatan_harian
            WHERE murid_id=%s
        """, (m["id"],))

        r = cur.fetchone()
        total = r["total"] or 0
        hadir = r["hadir"] or 0

        ctx += f"{m['nama_lengkap']} | kegiatan:{total} | hadir:{hadir}\n"

    db.close()
    return ctx

# ================= CONTEXT BUILDER =================
def build_context(guru_id, pesan):
    pesan_lower = pesan.lower()

    # ===== DAFTAR MURID =====
    if any(k in pesan_lower for k in ["daftar murid", "murid saya", "anak di kelas"]):
        murid = get_all_murid(guru_id)
        if not murid:
            return "MODE:DAFTAR\nTIDAK_ADA"

        ctx = "MODE:DAFTAR\n"
        for m in murid:
            ctx += f"{m['nama_lengkap']} | {m['nama_kelas']}\n"
        return ctx

    # ===== ANALISIS KELAS =====
    if any(k in pesan_lower for k in ["paling aktif", "analisis kelas", "ranking"]):
        return analisis_kelas(guru_id)

    # ===== DETEKSI MURID =====
    murid_id, nama = detect_murid(pesan, guru_id)

    if not murid_id:
        murid_id = last_murid_by_guru.get(guru_id)
        if murid_id:
            murid = get_all_murid(guru_id)
            for m in murid:
                if m["id"] == murid_id:
                    nama = m["nama_lengkap"]

    if not murid_id:
        return "MODE:ERROR\nMURID_TIDAK_DITEMUKAN"

    last_murid_by_guru[guru_id] = murid_id

    kegiatan = get_kegiatan_murid(murid_id)

    ctx = f"MODE:MURID\nNAMA:{nama}\n"

    if not kegiatan:
        ctx += "TIDAK_ADA_KEGIATAN\n"
        return ctx

    for k in kegiatan:
        tgl = k["tanggal"].strftime("%d-%m-%Y")
        keg = k["kegiatan"]
        cat = k["catatan"] or "-"
        mood = k["mood"] or "-"
        hadir = k["status_hadir"]
        ctx += f"{tgl} | {keg} | {cat} | mood:{mood} | hadir:{hadir}\n"

    return ctx

# ================= CHAT =================
@app.post("/chat")
async def chat(data: dict):
    pesan = data.get("pesan", "")
    guru_id = data.get("guru_id")

    context = build_context(guru_id, pesan)

    system_prompt = f"""
ANDA ADALAH ASISTEN AI GURU TAMAN KANAK-KANAK PROFESIONAL.

KEPRIBADIAN:
- ramah
- hangat
- sopan
- profesional
- memahami dunia anak usia dini

ATURAN PENTING:
- gunakan HANYA data pada DATA SEKOLAH
- dilarang mengarang
- jika data tidak ada → katakan belum ada
- jawaban singkat (2–4 kalimat)
- bahasa natural guru TK

DATA SEKOLAH:
{context}

KEMAMPUAN:
- kegiatan harian murid
- resume murid
- perkembangan anak
- analisis sederhana
- saran pembelajaran TK
- ide kegiatan anak
- pertanyaan umum sekolah TK

FORMAT:
- sebut nama murid jika ada
- jika kegiatan → sebut tanggal & kegiatan
- boleh beri 1 kalimat analisis ringan

GAYA:
"Baik Bu, berdasarkan catatan hari ini Raffi mengikuti kegiatan menggambar. Mood terlihat senang dan hadir di kelas."
"""

    try:
        res = client.chat.completions.create(
            model=MODEL_NAME,
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": pesan}
            ],
            temperature=0.3,
            max_tokens=200
        )

        return {"jawaban": res.choices[0].message.content}

    except Exception:
        return {"jawaban": "Maaf Bu, sistem AI sedang sibuk. Silakan coba lagi ya 🙏"}

# ================= RUN =================
if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8000)