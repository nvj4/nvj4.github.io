import mysql.connector
import json
import numpy as np
from sklearn.naive_bayes import GaussianNB
import datetime
import sys

def run_prediction():
    try:
        # Konfigurasi Database
        db_config = {
            "host": "localhost",
            "user": "root",
            "password": "",
            "database": "tokodaging_db"
        }
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)

        # 1. AMBIL DATA TRAINING
        cursor.execute("SELECT meat_type_id, jumlah_kg FROM transactions")
        train_rows = cursor.fetchall()
        
        if len(train_rows) < 2:
            print(json.dumps({"status": "error", "message": "Data transaksi minimal 2 untuk training"}))
            return

        # Fitur (X): jumlah_kg, Target (y): meat_type_id
        X = np.array([[float(r['jumlah_kg'])] for r in train_rows])
        y = np.array([int(r['meat_type_id']) for r in train_rows])

        # Latih Model
        model = GaussianNB()
        model.fit(X, y)
        akurasi_val = model.score(X, y)

        # 2. PROSES PREDIKSI 7 HARI KE DEPAN
        mean_kg = np.mean(X)
        today = datetime.date.today()
        
        # Ambil semua jenis daging yang ada
        cursor.execute("SELECT id FROM meat_types") # Sesuaikan nama tabel jenis daging Anda
        meat_types = cursor.fetchall()

        inserted_count = 0
        for meat in meat_types:
            m_id = meat['id']   
            for i in range(1, 8):
                tgl_prediksi = today + datetime.timedelta(days=i)
                
                # Cek agar tidak duplikat di DB
                cursor.execute("SELECT id FROM predictions WHERE tanggal = %s AND meat_type_id = %s", 
                               (tgl_prediksi, m_id))
                
                if cursor.fetchone() is None:
                    # Prediksi angka stok (Mean + Noise)
                    pred_kg = round(float(mean_kg + np.random.uniform(-0.5, 0.5)), 2)

                    cursor.execute(
                        "INSERT INTO predictions (tanggal, meat_type_id, prediksi_kg, akurasi, created_at) VALUES (%s, %s, %s, %s, NOW())",
                        (tgl_prediksi, m_id, pred_kg, float(akurasi_val))
                    )
                    inserted_count += 1

        conn.commit()
        print(json.dumps({
            "status": "success", 
            "message": f"Berhasil menghitung & menyimpan {inserted_count} data ke DB", 
            "akurasi": round(akurasi_val, 2)
        }))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    run_prediction()