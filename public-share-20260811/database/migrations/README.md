# Migration Policy

- Migrasi hanya dijalankan melalui CLI `php tools/migrate.php`.
- Environment produksi membutuhkan flag eksplisit `--allow-production`.
- Setiap migrasi dicatat pada `schema_migrations` setelah langkah `up` berhasil.
- MySQL melakukan implicit commit untuk banyak DDL; runner tidak mengklaim transaksi atau rollback otomatis.
- Sebelum migrasi staging/produksi, ambil backup terverifikasi dan lakukan rehearsal pada clone.

## Rollback Sprint 4

- `001_baseline_schema`: pada database kosong, hapus database test secara keseluruhan; pada database existing, statement `CREATE IF NOT EXISTS` tidak menghapus tabel.
- `002_reconcile_existing_users`: pulihkan backup jika perubahan enum/kolom perlu dibatalkan. Jangan menghapus `anak_username` jika sudah berisi relasi.
- `003_seed_reference_advice`: record referensi dapat dihapus berdasarkan kategori hanya setelah dipastikan bukan hasil modifikasi operator.

Rollback produksi wajib mengikuti backup/restore runbook; tidak ada rollback destruktif otomatis.
