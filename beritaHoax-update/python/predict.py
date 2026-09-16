import sys
import mysql.connector
import json
import re
import math
import os

# Paksa UTF-8 (hindari error karakter di Windows)
sys.stdout.reconfigure(encoding='utf-8')


def preprocess(text):
    if not text:
        return []
    text = text.lower()
    text = re.sub(r'[^a-z\s]', '', text)
    return [word for word in text.split() if len(word) > 2]


def predict():
    # ==============================
    # CEK ARGUMENT
    # ==============================
    if len(sys.argv) < 2:
        print("error: ID artikel tidak terdeteksi")
        return

    article_id = sys.argv[1]

    # Default user_id supaya tidak NULL
    user_id = 1  

    if len(sys.argv) > 2 and sys.argv[2] != "NULL":
        user_id = sys.argv[2]

    # ==============================
    # PATH MODEL
    # ==============================
    base_path = os.path.dirname(os.path.abspath(__file__))
    json_path = os.path.join(base_path, "..", "api", "knowledge_base.json")

    try:
        # ==============================
        # CEK FILE MODEL
        # ==============================
        if not os.path.exists(json_path):
            print("error: File model tidak ditemukan")
            return

        with open(json_path, "r", encoding="utf-8") as f:
            model = json.load(f)

        # ==============================
        # KONEKSI DATABASE
        # ==============================
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="hoax_detector_db"
        )

        cursor = db.cursor(dictionary=True)

        # ==============================
        # AMBIL ARTIKEL
        # ==============================
        cursor.execute(
            "SELECT content FROM news_articles WHERE id = %s",
            (article_id,)
        )

        article = cursor.fetchone()

        if not article or not article["content"]:
            print("error: Artikel tidak ditemukan di database")
            return

        words = preprocess(article["content"])

        if not words:
            print("fakta")
            return

        # ==============================
        # NAIVE BAYES
        # ==============================
        labels = ["hoax", "fakta"]
        scores = {}

        for label in labels:
            prior = model["count_label"][label] / model["total_berita"]
            scores[label] = math.log(prior)

            total_kata_label = sum(model["kata_per_label"][label].values())

            for word in words:
                count_kata = model["kata_per_label"][label].get(word, 0)
                word_prob = (count_kata + 1) / (
                    total_kata_label + model["vocab_size"]
                )
                scores[label] += math.log(word_prob)

        prediction = "hoax" if scores["hoax"] > scores["fakta"] else "fakta"

        # ==============================
        # SIMPAN KE HISTORY
        # ==============================
        sql_insert = """
            INSERT INTO detection_history 
            (user_id, content, detection_result, date)
            VALUES (%s, %s, %s, NOW())
        """

        cursor.execute(
            sql_insert,
            (user_id, article["content"], prediction)
        )

        db.commit()

        # OUTPUT UNTUK PHP (WAJIB SATU BARIS)
        print(prediction)

    except Exception as e:
        print(f"error: {str(e)}")

    finally:
        if "db" in locals() and db.is_connected():
            cursor.close()
            db.close()


if __name__ == "__main__":
    predict()
