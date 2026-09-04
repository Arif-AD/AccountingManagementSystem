<!-- ================= HEADER ================= -->

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=0:17352D,50:285348,100:6D9E69&height=220&section=header&text=Web%20Accounting%20Management%20System&fontSize=42&fontColor=ffffff&animation=fadeIn&fontAlignY=40&desc=Ruang%20kerja%20digital%20untuk%20pembukuan%20dan%20laporan%20keuangan&descAlignY=60&descSize=16" width="100%" />
</p>

<p align="center">
  <img src="https://readme-typing-svg.demolab.com?font=Fira+Code&weight=600&size=19&duration=3000&pause=1000&color=6D9E69&center=true&vCenter=true&width=760&lines=Chart+of+Accounts;Double-entry+Journal;Manager+Approval+Workflow;Financial+Reports" alt="Typing SVG" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB" />
  <img src="https://img.shields.io/badge/TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" />
</p>

---

## About the Project

**Web Accounting Management System (WAMS)** adalah aplikasi web untuk mengelola pembukuan, transaksi jurnal berpasangan, persetujuan manager, dan laporan keuangan dalam satu ruang kerja.

Aplikasi ini membantu accountant mencatat transaksi secara konsisten dan membantu manager meninjau transaksi sebelum diposting. Sistem menggunakan akses berbasis peran dan hanya menghitung jurnal dengan status **posted** pada laporan keuangan.

## Project Preview

<p align="center">
  <img src="./documentation/ss-WAMS1%20(1).png" alt="WAMS Dashboard" width="400" />
  <img src="./documentation/ss-WAMS1%20(2).png" alt="WAMS Chart of Accounts" width="400" />
  <img src="./documentation/ss-WAMS1%20(3).png" alt="WAMS Journal" width="400" />
  <img src="./documentation/ss-WAMS1%20(4).png" alt="WAMS General Ledger" width="400" />
  <img src="./documentation/ss-WAMS1%20(5).png" alt="WAMS Trial Balance" width="400" />
  <img src="./documentation/ss-WAMS1%20(6).png" alt="WAMS Balance Sheet" width="400" />
  <img src="./documentation/ss-WAMS1%20(7).png" alt="WAMS Income Statement" width="400" />
  <img src="./documentation/ss-WAMS1%20(8).png" alt="WAMS Financial Position" width="400" />
</p>

## Features

### Accounting Workspace

| Feature           | Description                                                          |
| ----------------- | -------------------------------------------------------------------- |
| Chart of Accounts | Kelola COA dengan kode, nama, tipe, akun induk, dan status aktif     |
| Journal Entry     | Buat jurnal double-entry dengan total debit dan kredit yang seimbang |
| Draft Management  | Edit atau hapus jurnal selama masih berstatus draf                   |
| Role-based Access | Accountant membuat dan memposting jurnal; manager melakukan approval |

### Approval Workflow

| Status   | Description                                        |
| -------- | -------------------------------------------------- |
| Draft    | Jurnal baru yang masih dapat diedit                |
| Pending  | Jurnal dikirim accountant untuk ditinjau manager   |
| Approved | Jurnal telah disetujui manager                     |
| Posted   | Jurnal final dan masuk ke seluruh laporan keuangan |

### File Upload

| Feature          | Description                                                 |
| ---------------- | ----------------------------------------------------------- |
| Supported Format | CSV, XLSX, dan XLS                                          |
| File Attachment  | Menyimpan file unggahan sebagai lampiran jurnal             |
| Approval         | File upload dapat disetujui manager tanpa validasi isi file |
| Upload Limit     | Mengikuti konfigurasi upload server, default 2 MB           |

### Financial Reports

| Report             | Description                                             |
| ------------------ | ------------------------------------------------------- |
| Dashboard          | Ringkasan transaksi, pendapatan, beban, dan laba bersih |
| General Ledger     | Riwayat transaksi dan saldo berjalan per akun           |
| Trial Balance      | Perbandingan total debit dan kredit                     |
| Balance Sheet      | Aset, kewajiban, ekuitas, dan laba bersih               |
| Income Statement   | Pendapatan, beban, dan laba bersih periode tertentu     |
| Financial Position | Ringkasan posisi keuangan dan status keseimbangan       |

## Tech Stack

### Frontend

<p align="center">
  <img src="https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB" />
  <img src="https://img.shields.io/badge/TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white" />
  <img src="https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" />
</p>

### Backend and Database

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/PhpSpreadsheet-217346?style=for-the-badge&logo=microsoftexcel&logoColor=white" />
</p>

### Tools

<p align="center">
  <img src="https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white" />
  <img src="https://img.shields.io/badge/PHPUnit-366488?style=for-the-badge&logo=phpunit&logoColor=white" />
  <img src="https://img.shields.io/badge/VS_Code-007ACC?style=for-the-badge&logo=visual-studio-code&logoColor=white" />
  <img src="https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white" />
</p>

## Getting Started

### Requirements

* PHP 8.3 atau lebih baru
* Composer
* Node.js dan npm
* MySQL 8.0 atau lebih baru

### Installation

```bash
git clone https://github.com/Arif-AD/AccountingManagementSystem.git
cd AccountingManagementSystem
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Buat database MySQL terlebih dahulu, kemudian sesuaikan konfigurasi database pada file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_accounting_system
DB_USERNAME=root
DB_PASSWORD=
```

Setelah konfigurasi database selesai, jalankan migration dan seeder:

```bash
php artisan migrate:fresh --seed
npm run build
```

Untuk Windows PowerShell, gunakan `Copy-Item .env.example .env` jika perintah `copy` tidak tersedia.

Jalankan aplikasi:

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000`.

### Demo Accounts

| Role       | Email                    | Password   |
| ---------- | ------------------------ | ---------- |
| Accountant | `accountant@example.com` | `password` |
| Manager    | `manager@example.com`    | `password` |

## Journal Workflow

1. Login sebagai **Akuntan**.
2. Buka menu **Jurnal** dan pilih **Buat jurnal**.
3. Pilih minimal dua akun dan isi nilai debit/kredit.
4. Pastikan total debit sama dengan total kredit.
5. Simpan sebagai draf.
6. Buka detail jurnal dan pilih **Kirim untuk persetujuan**.
7. Login sebagai **Manager**, buka jurnal pending, lalu pilih **Setujui**.
8. Login kembali sebagai **Akuntan**, lalu pilih **Posting**.
9. Buka menu laporan untuk melihat transaksi yang sudah diposting.

Jurnal upload file disimpan sebagai attachment dan tidak membuat baris debit/kredit otomatis.

## Minimal COA

Seeder menyediakan COA ringkas yang mencakup seluruh kelompok laporan:

| Code | Account         | Type      |
| ---- | --------------- | --------- |
| 1100 | Kas             | Asset     |
| 2100 | Utang Usaha     | Liability |
| 3100 | Modal Pemilik   | Equity    |
| 4100 | Pendapatan Jasa | Revenue   |
| 5100 | Beban Gaji      | Expense   |
| 5200 | Beban Sewa      | Expense   |

## Architecture

```text
+---------------------------------+
|       React + TypeScript UI     |
|       Inertia.js + Tailwind     |
+----------------+----------------+
                 | Inertia requests
                 v
+---------------------------------+
|        Laravel Application      |
| Controllers - Models - Policies |
+----------------+----------------+
                 | Eloquent ORM
                 v
+---------------------------------+
|             MySQL               |
| COA - Journals - Approval Data  |
+---------------------------------+
```

## Testing

```bash
php artisan test
npm run build
```

## Security Notes

* Jangan commit file `.env`, credential, database lokal, atau file upload pengguna.
* File upload disimpan pada `storage/app/private` dan tidak dilacak Git.
* Gunakan password yang berbeda untuk environment production.

## License

Project ini menggunakan struktur aplikasi Laravel dan dilisensikan sesuai kebutuhan pengembangan proyek.
