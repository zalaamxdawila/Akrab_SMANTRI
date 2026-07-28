import pandas as pd
from sklearn.linear_model import LogisticRegression
import sys

def train_anemia_model(csv_path):
    print("Membaca dataset dari:", csv_path)
    try:
        df = pd.read_csv(csv_path)
    except Exception as e:
        print("Gagal membaca file CSV:", e)
        sys.exit(1)
        
    # Asumsi kolom dari biswaranjanrao/anemia-dataset:
    # Gender, Hemoglobin, MCH, MCHC, MCV, Result
    # Kita menggunakan fitur-fitur ini untuk melatih regresi logistik.
    
    features = ['Gender', 'Hemoglobin', 'MCH', 'MCHC', 'MCV']
    target = 'Result'
    
    if not all(col in df.columns for col in features + [target]):
        print("Kolom tidak sesuai. Dataset harus memiliki:", features + [target])
        sys.exit(1)
        
    X = df[features]
    y = df[target]
    
    model = LogisticRegression(max_iter=1000)
    model.fit(X, y)
    
    print("\n--- HASIL TRAINING MODEL ---")
    print("Akurasi Model: {:.2f}%".format(model.score(X, y) * 100))
    print("\n--- KOEFISIEN (Untuk di-copy ke helpers.php) ---")
    print(f"'b0' => {model.intercept_[0]:.4f},")
    for feat, coef in zip(features, model.coef_[0]):
        print(f"'{feat.lower()}' => {coef:.4f},")
        
    print("\nSilakan salin nilai koefisien di atas ke dalam fungsi prediksiRisikoAnemia() di file helpers.php")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Penggunaan: python train_model.py path/to/anemia.csv")
    else:
        train_anemia_model(sys.argv[1])
