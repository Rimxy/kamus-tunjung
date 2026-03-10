import pandas as pd
import mysql.connector

# =========================
# 1️⃣ Koneksi ke database
# =========================
conn = mysql.connector.connect(
    host="localhost",
    user="root",       
    password="",       
    database="kamus_tunjung"
)
cursor = conn.cursor(buffered=True)

# =========================
# 2️⃣ Baca file CSV
# =========================
csv_file = "kamus_tunjung.csv"  
df = pd.read_csv(csv_file)
df.columns = df.columns.str.strip()  

print(f"📄 Kolom terbaca: {list(df.columns)}")
print(f"📊 Jumlah baris data: {len(df)}")

# =========================
# 3️⃣ Isi tabel jenis_kata
# =========================
print("\n🔹 Memasukkan data ke tabel 'jenis_kata'...")
jenis_list = df['JENIS KATA'].dropna().unique()

for i, jenis in enumerate(jenis_list, start=1):
    id_jenis = f"J{i:03}"
    cursor.execute("SELECT * FROM jenis_kata WHERE nama_jenis = %s", (jenis,))
    cursor.fetchall()
    if cursor.rowcount == 0:
        cursor.execute(
            "INSERT INTO jenis_kata (id_jeniskata, nama_jenis) VALUES (%s, %s)",
            (id_jenis, jenis)
        )
conn.commit()
print("✅ Data 'jenis_kata' selesai dimasukkan.")

# =========================
# 4️⃣ Isi tabel kalimat
# =========================
print("\n🔹 Memasukkan data ke tabel 'kalimat'...")
for i, row in df.iterrows():
    id_kalimat = f"KAL{i+1:05}"
    kal_tunjung = str(row['KALIMAT BAHASA TUNJUNG']) if pd.notna(row['KALIMAT BAHASA TUNJUNG']) else None
    kal_indo = str(row['KALIMAT BAHASA INDONESIA']) if pd.notna(row['KALIMAT BAHASA INDONESIA']) else None
    kal_ing = str(row['KALIMAT BAHASA INGGRIS']) if pd.notna(row['KALIMAT BAHASA INGGRIS']) else None
    cursor.execute(
        "INSERT INTO kalimat (id_kalimat, kalimat_tunjung, kalimat_indonesia, kalimat_inggris) VALUES (%s, %s, %s, %s)",
        (id_kalimat, kal_tunjung, kal_indo, kal_ing)
    )
conn.commit()
print("✅ Data 'kalimat' selesai dimasukkan.")

# =========================
# 5️⃣ Isi tabel kata
# =========================
print("\n🔹 Memasukkan data ke tabel 'kata'...")
for i, row in df.iterrows():
    id_kata = f"W{i+1:05}"
    kata_tunjung = str(row['BAHASA TUNJUNG']) if pd.notna(row['BAHASA TUNJUNG']) else None
    turunan = str(row['TURUNAN KATA']) if pd.notna(row['TURUNAN KATA']) else None
    kata_indo = str(row['BAHASA INDONESIA']) if pd.notna(row['BAHASA INDONESIA']) else None
    kata_ing = str(row['BAHASA INGGRIS']) if pd.notna(row['BAHASA INGGRIS']) else None
    dialek = str(row['DIALEK']) if pd.notna(row['DIALEK']) else None

    # cari id_jeniskata berdasarkan nama_jenis
    jenis = str(row['JENIS KATA']) if pd.notna(row['JENIS KATA']) else None
    id_jenis = None
    if jenis:
        cursor.execute("SELECT id_jeniskata FROM jenis_kata WHERE nama_jenis = %s", (jenis,))
        result = cursor.fetchone()
        if result:
            id_jenis = result[0]

    # id kalimat berdasarkan urutan (sama index)
    id_kalimat = f"KAL{i+1:05}"

    cursor.execute("""
        INSERT INTO kata (id_kata, kata_tunjung, turunan_kata, kata_indonesia, kata_inggris, id_jeniskata, id_kalimat, dialek)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    """, (id_kata, kata_tunjung, turunan, kata_indo, kata_ing, id_jenis, id_kalimat, dialek))

conn.commit()
print("✅ Data 'kata' selesai dimasukkan.")

# =========================
# 6️⃣ Tutup koneksi
# =========================
cursor.close()
conn.close()
print("\n🎉 Semua data berhasil dimasukkan ke database!")
