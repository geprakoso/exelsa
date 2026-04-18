# Laravel MCP Setup untuk Opencode

## 🎯 Apa yang Sudah Dibuat

### 1. MCP Server (`app/Mcp/Servers/ArabicaServer.php`)
Server utama yang mendaftarkan semua tools dan resources dengan nama **"Arabica POS System"**.

### 2. MCP Tools (`app/Mcp/Tools/`)
Tersedia 7 tools untuk berinteraksi dengan data:

| Tool | Deskripsi |
|------|-----------|
| `ProdukTool` | Mencari produk berdasarkan nama, SKU, brand, kategori, stok |
| `PenjualanTool` | Melihat riwayat penjualan, omset, filter per periode |
| `MemberTool` | Mencari member/pelanggan dan riwayat pembelian |
| `KaryawanTool` | Melihat data karyawan dan posisi |
| `SupplierTool` | Mencari supplier dan daftar produknya |
| `StokTool` | Monitoring stok rendah, habis, dan nilai inventori |
| `LaporanTool` | Lihat laba rugi, penjualan harian, summary finansial |

### 3. MCP Resources (`app/Mcp/Resources/`)
- `DashboardResource` - Ringkasan statistik dashboard real-time

### 4. Routes (`routes/mcp.php`)
Endpoint: `POST /mcp/arabica`

### 5. Konfigurasi Opencode (`mcp.json`)
File konfigurasi untuk menghubungkan ke Opencode.

---

## 🚀 Cara Menggunakan

### Setup di Opencode

Tambahkan MCP server ke konfigurasi Opencode. Di Opencode, biasanya melalui:

**Settings → MCP → Add Server**

```json
{
  "name": "Arabica POS",
  "type": "http",
  "url": "http://localhost:8000/mcp/arabica"
}
```

Atau jika Opencode mendukung `mcp.json`, letakkan file `mcp.json` di root project.

### Testing

Test dengan curl:
```bash
curl -X POST http://localhost:8000/mcp/arabica \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/list"
  }'
```

Atau gunakan MCP Inspector:
```bash
php artisan mcp:inspector
```

---

## 💡 Contoh Penggunaan Tools

### Cari Produk
```json
{
  "name": "search_produk",
  "arguments": {
    "search": "iPhone",
    "limit": 10
  }
}
```

### Lihat Penjualan Bulan Ini
```json
{
  "name": "lihat_penjualan",
  "arguments": {
    "tanggal_dari": "2026-04-01",
    "tanggal_sampai": "2026-04-30",
    "summary": true
  }
}
```

### Cek Stok Rendah
```json
{
  "name": "cek_stok",
  "arguments": {
    "mode": "rendah"
  }
}
```

### Lihat Laporan Laba Rugi
```json
{
  "name": "lihat_laporan",
  "arguments": {
    "tipe": "laba_rugi",
    "bulan": 4,
    "tahun": 2026
  }
}
```

### Ringkasan Dashboard
Resource: `dashboard` akan mengembalikan statistik real-time.

---

## 📝 Notes

- Pastikan server Laravel berjalan (`php artisan serve` atau Docker)
- Tools menggunakan database yang sama dengan aplikasi Arabica
- Tidak perlu autentikasi khusus untuk test local
- Untuk production, pertimbangkan menambahkan middleware auth

## 🔧 Troubleshooting

Jika terjadi error koneksi:
1. Cek apakah Laravel server berjalan
2. Cek routes: `php artisan route:list --path=mcp`
3. Cek log: `tail -f storage/logs/laravel.log`
