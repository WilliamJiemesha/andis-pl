# Non Buku External Item API

Dokumen ini dipakai oleh programmer aplikasi external yang perlu mencari dan sinkron data master barang ke Non Buku Project.

## Ringkasan

API external yang tersedia:

- `GET /api/v1/non-buku/items/search`
- `POST /api/v1/non-buku/items/sync`

Semua respons selalu JSON.

Untuk saat ini endpoint external bisa dipanggil tanpa auth agar integrasi lebih cepat dicoba.

Header minimum:

```http
Accept: application/json
```

## Token API

Project ini sudah menyiapkan token bearer sederhana untuk dipakai nanti, tetapi saat ini middleware auth sedang dinonaktifkan pada route external.

Seeder default:

- `Database\\Seeders\\ExternalApiTokenSeeder`

Token default saat `php artisan migrate:fresh --seed`:

```txt
non-buku-external-demo-token
```

Bisa diganti lewat environment:

```env
NON_BUKU_EXTERNAL_API_TOKEN=token-baru-anda
NON_BUKU_EXTERNAL_API_TOKEN_NAME=Integrasi ERP
```

## Struktur Data

Field utama:

- `barang`
- `tipe`
- `merk`
- `official_name`
- `alias`
- `sku_code`
- `cost_code`

Catatan:

- Pencarian existing item diprioritaskan berdasarkan kombinasi normalisasi `barang + merk + tipe`.
- `alias` wajib untuk sync dan disimpan sebagai nama barang yang mudah dibaca.
- `sku_code` opsional. Jika kosong saat membuat master baru, sistem membuat SKU otomatis.
- `cost_code` hanya diterima sebagai input internal.
- API ini tidak pernah mengembalikan decoded cost amount.

## Endpoint 1: Search Master Item

```http
GET /api/v1/non-buku/items/search?barang=CHAINSAW&tipe=MS382&merk=STIHL
```

### Query Parameter

- `barang` wajib
- `tipe` wajib
- `merk` wajib

### Matching Rule

1. Input dinormalisasi ke format internal.
2. Sistem mencari exact match `barang + merk + tipe`.
3. Jika belum ditemukan, sistem boleh mencari kecocokan dari `official_name`.
4. Endpoint ini tidak pernah membuat master item baru.

### Success Response

```json
{
  "success": true,
  "exists": true,
  "message": "Master barang ditemukan.",
  "data": {
    "id": 123,
    "barang": "CHAINSAW",
    "tipe": "MS382",
    "merk": "STIHL",
    "official_name": "CHAINSAW STIHL MS382",
    "alias": "Chainsaw Stihl MS382",
    "sku_code": "NB-000123"
  }
}
```

### Not Found Response

HTTP status: `404`

```json
{
  "success": true,
  "exists": false,
  "message": "Master barang belum tersedia untuk kombinasi barang, tipe, dan merk tersebut.",
  "data": {
    "barang": "CHAINSAW",
    "tipe": "MS382",
    "merk": "STIHL"
  }
}
```

## Endpoint 2: Sync Item

```http
POST /api/v1/non-buku/items/sync
Content-Type: application/json
```

### Request Body

```json
{
  "barang": "CHAINSAW",
  "tipe": "MS382",
  "merk": "STIHL",
  "official_name": "CHAINSAW STIHL MS382",
  "alias": "Chainsaw Stihl MS382",
  "sku_code": "NB-000123",
  "cost_code": "IPAUD"
}
```

### Validasi

- `barang` required string
- `tipe` required string
- `merk` nullable string
- `official_name` nullable string
- `alias` required string
- `sku_code` nullable string
- `cost_code` required saat create item baru
- `cost_code` nullable saat update existing item

### Case A: Master Barang Sudah Ada

Jika item ditemukan:

1. Sistem mengembalikan item existing.
2. Jika `sku_code` berubah, SKU akan di-update.
3. Jika `alias` berubah, Alias akan di-update.
4. Master item tidak akan diduplikasi.
5. Jika `cost_code` dikirim dan berbeda dari cost code terbaru, sistem membuat cost history baru dan task `Review Harga`.

Contoh respons:

```json
{
  "success": true,
  "exists": true,
  "action": "updated_existing",
  "message": "Master barang sudah ada. Data alias/SKU diperbarui jika ada perubahan.",
  "data": {
    "id": 123,
    "barang": "CHAINSAW",
    "tipe": "MS382",
    "merk": "STIHL",
    "official_name": "CHAINSAW STIHL MS382",
    "alias": "Chainsaw Stihl MS382",
    "sku_code": "NB-000123",
    "price_review_created": true
  }
}
```

### Case B: Master Barang Belum Ada

Jika item belum ada:

1. Sistem membuat master item baru.
2. Sistem menyimpan `alias` sebagai Alias / Nama Barang.
3. Sistem menyimpan `sku_code` jika dikirim. Jika kosong, sistem membuat SKU otomatis.
4. Sistem menyimpan `cost_code`.
5. Sistem membuat cost history awal.
6. Sistem membuat task `Review Harga`.
7. Sistem membuat notifikasi berbahasa Indonesia untuk Admin / Price Handler.

Contoh respons:

```json
{
  "success": true,
  "exists": false,
  "action": "created_new",
  "message": "Master barang baru dibuat dan masuk ke Review Harga.",
  "data": {
    "id": 456,
    "barang": "CHAINSAW",
    "tipe": "MS382",
    "merk": "STIHL",
    "official_name": "CHAINSAW STIHL MS382",
    "alias": "Chainsaw Stihl MS382",
    "sku_code": "NB-000456",
    "price_review_created": true
  }
}
```

## Error Response

### Token tidak ada / salah

Catatan: respons ini baru berlaku lagi jika middleware auth external diaktifkan kembali.

HTTP status: `401`

```json
{
  "success": false,
  "message": "Token API tidak valid."
}
```

### Validasi gagal

HTTP status: `422`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cost_code": [
      "Kode modal wajib diisi saat membuat master barang baru."
    ]
  }
}
```

## cURL Example

### Search

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/non-buku/items/search?barang=CHAINSAW&tipe=MS382&merk=STIHL" \
  -H "Accept: application/json"
```

### Sync

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/non-buku/items/sync" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "barang":"CHAINSAW",
    "tipe":"MS382",
    "merk":"STIHL",
    "official_name":"CHAINSAW STIHL MS382",
    "alias":"Chainsaw Stihl MS382",
    "sku_code":"NB-000123",
    "cost_code":"IPAUD"
  }'
```

## File Implementasi

- `routes/api.php`
- `app/Http/Middleware/AuthenticateExternalApiToken.php`
- `app/Http/Controllers/Api/ExternalItemController.php`
- `app/Http/Requests/Api/SearchExternalItemRequest.php`
- `app/Http/Requests/Api/SyncExternalItemRequest.php`
- `app/Services/ExternalItemSyncService.php`
- `app/Models/ExternalApiToken.php`
- `database/seeders/ExternalApiTokenSeeder.php`
