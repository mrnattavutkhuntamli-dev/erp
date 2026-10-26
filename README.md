<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


# 🚀 Mini ERP - ระบบจัดการธุรกิจสำหรับร้านอะไหล่รถยนต์

ระบบ ERP ขนาดย่อม (Mini ERP) ที่ออกแบบมาเพื่อตอบโจทย์ธุรกิจร้านขายอะไหล่รถยนต์โดยเฉพาะ รองรับการจัดการสินค้าหลายประเภท (รถยนต์, รถไถ, รถสิบล้อ, มอเตอร์ไซต์) พร้อมระบบขายหน้าร้าน (POS), การจัดการสต็อก, การจัดซื้อ, และระบบลูกหนี้/เจ้าหนี้

พัฒนาด้วย **Laravel 12** + **Tailwind CSS** โดยเน้น UI/UX ที่ใช้งานง่าย รวดเร็ว และรองรับการทำงานหลายอุปกรณ์

---

## 📋 สารบัญ

- [คุณสมบัติหลัก (Features)](#-คุณสมบัติหลัก-features)
- [เทคโนโลยีที่ใช้ (Tech Stack)](#-เทคโนโลยีที่ใช้-tech-stack)
- [การติดตั้ง (Installation)](#-การติดตั้ง-installation)
- [โครงสร้างระบบ (Modules)](#-โครงสร้างระบบ-modules)
- [บัญชีเริ่มต้น (Default Accounts)](#-บัญชีเริ่มต้น-default-accounts)
- [การใช้งาน (Usage)](#-การใช้งาน-usage)
- [แผนงานในอนาคต (Roadmap)](#-แผนงานในอนาคต-roadmap)
- [License](#-license)

---

## ✨ คุณสมบัติหลัก (Features)

### 📦 ระบบจัดการสินค้าและคลังสินค้า
- **จัดการสินค้า** พร้อมรูปภาพหลายรูป, ยี่ห้ออะไหล่, รุ่นรถที่รองรับ
- **หมวดหมู่สินค้า** แยกตามประเภทธุรกิจ (น้ำมันเครื่อง, ระบบเบรก, อะไหล่รถไถ, ฯลฯ)
- **ยี่ห้ออะไหล่ / ยี่ห้อรถ / รุ่นรถ** (Many-to-Many กับสินค้า)
- **หน่วยนับ** (ชิ้น, กล่อง, ลิตร, ฯลฯ)
- **รับเข้า / เบิกออกสินค้า** พร้อมพิมพ์ใบรับ/ใบเบิก
- **ปรับปรุงสต็อก** (ของหาย, ชำรุด, นับสต็อกจริง)
- **ยกเลิกเอกสาร (Void)** พร้อมคืนสต็อกอัตโนมัติ

### 💰 ระบบขายหน้าร้าน (POS)
- **หน้าจอ POS** ค้นหาสินค้า, สแกนบาร์โค้ด, กดเพิ่มลงตะกร้าได้ทันที
- **ส่วนลด** รองรับทั้งแบบบาท และเปอร์เซ็นต์ (%)
- **ระบบขายสด** (เงินสด/โอน/บัตรเครดิต) และ **ระบบขายเชื่อ**
- **พิมพ์ใบเสร็จ** รองรับการพิมพ์ A4 และ Thermal Printer
- **ระบบใบเสนอราคา (Quotation)** พร้อมแปลงเป็นใบเสร็จอัตโนมัติ

### 🛒 ระบบจัดซื้อ (Purchase Order)
- **ใบสั่งซื้อ (PO)** พร้อมสถานะ (รอรับของ, รับของแล้ว, ยกเลิก)
- **ระบบรับของ** → เพิ่มสต็อกอัตโนมัติ
- **เชื่อมโยงกับซัพพลายเออร์** แต่ละราย

### 👥 ระบบลูกค้าและซัพพลายเออร์
- **จัดการลูกค้า** (ชื่อ, เบอร์โทร, ทะเบียนรถ)
- **จัดการซัพพลายเออร์** (ชื่อบริษัท, ผู้ติดต่อ, ที่อยู่)
- **ระบบรับชำระเงิน (Receipt)** สำหรับติดตามลูกหนี้ (A/R)
- **ยกเลิกใบรับเงิน (Void)** พร้อม Audit Trail

### 📊 รายงานและวิเคราะห์
- **รายงานภาพรวม** (ยอดขาย, กำไรขั้นต้น, สินค้าขายดี, ลูกค้าซื้อมากสุด)
- **รายงานสต็อก** (สินค้าคงเหลือ, มูลค่าสต็อก)
- **กราฟยอดขายรายวัน** (Chart.js)
- **Export PDF** รองรับภาษาไทย (DomPDF + Sarabun)

### 🔐 ระบบความปลอดภัย
- **ระบบ Login** ด้วย Laravel Breeze
- **Two-Factor Authentication (2FA)** ด้วย Google Authenticator
- **ระบบสิทธิ์พนักงาน (Role & Permission)** ตามตำแหน่ง:
  - `admin` (เจ้าของร้าน)
  - `cashier` (พนักงานขาย)
  - `stock` (พนักงานคลัง)
  - `accountant` (พนักงานบัญชี)
  - `delivery` (พนักงานขนส่ง)
- **Idempotency Key** ป้องกันการกดซ้ำ (Double Submit)

### ⚙️ ตั้งค่าระบบ
- **ข้อมูลบริษัท** (ชื่อ, ที่อยู่, เบอร์โทร, เลขภาษี, โลโก้)
- **Custom Settings** เพิ่ม/แก้ไข/ลบ/เปิด-ปิดการใช้งาน (เช่น LINE Notify Token)
- **นำข้อมูลจาก Settings ไปใช้ในเอกสารทุกใบอัตโนมัติ**

---

## 🛠 เทคโนโลยีที่ใช้ (Tech Stack)

### Backend
- **PHP 8.2+**
- **Laravel 12.x** — Framework หลัก
- **SQLite / MySQL** — ฐานข้อมูล (รองรับทั้งคู่)
- **Spatie Laravel Permission** — ระบบ Role & Permission
- **PragmaRX Google2FA** — Two-Factor Authentication
- **Barryvdh DomPDF** — Export PDF (รองรับภาษาไทย)

### Frontend
- **Tailwind CSS** — Utility-first CSS Framework
- **Alpine.js** — JavaScript Framework ขนาดเล็ก
- **Chart.js** — กราฟและ Data Visualization
- **Vite** — Bundler สำหรับ Assets

### เครื่องมือพัฒนา
- **Laravel Breeze** — Authentication scaffolding
- **Laravel Pint** — Code formatter
- **Laravel Boost** — AI coding assistant guidelines

---

## 🚀 การติดตั้ง (Installation)

### สิ่งที่ต้องมี (Requirements)
- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite หรือ MySQL

### ขั้นตอนการติดตั้ง

```bash
# 1. Clone โปรเจกต์
git clone <your-repo-url> mini-erp
cd mini-erp

# 2. ติดตั้ง Dependencies
composer install
npm install

# 3. คัดลอกไฟล์ .env
cp .env.example .env

# 4. สร้าง Application Key
php artisan key:generate

# 5. ตั้งค่าฐานข้อมูลใน .env
#    - ถ้าใช้ SQLite: DB_CONNECTION=sqlite
#    - ถ้าใช้ MySQL: DB_CONNECTION=mysql, DB_DATABASE=mini_erp, ...
touch database/database.sqlite  # (สำหรับ SQLite)

# 6. รัน Migration + Seeder
php artisan migrate:fresh --seed

# 7. สร้าง Storage Link (สำหรับรูปภาพ)
php artisan storage:link

# 8. Build Assets
npm run build

# 9. รันเซิร์ฟเวอร์
php artisan serve


app/
├── Http/
│   └── Controllers/
│       ├── ProductController.php         # จัดการสินค้า
│       ├── StockDocumentController.php   # ใบรับ/ใบเบิก
│       ├── StockAdjustmentController.php # ปรับปรุงสต็อก
│       ├── PurchaseOrderController.php   # ใบสั่งซื้อ (PO)
│       ├── SaleController.php            # POS / ขายหน้าร้าน
│       ├── QuotationController.php       # ใบเสนอราคา
│       ├── ReceiptController.php         # ใบรับชำระเงิน
│       ├── ReportController.php          # รายงาน
│       ├── SettingController.php         # ตั้งค่าบริษัท
│       └── UserController.php            # จัดการพนักงาน
├── Models/
│   ├── Product.php, Category.php, Brand.php
│   ├── Supplier.php, Customer.php
│   ├── Sale.php, SaleItem.php, Receipt.php
│   ├── PurchaseOrder.php, PurchaseOrderItem.php
│   ├── StockDocument.php, StockAdjustment.php
│   ├── Quotation.php, QuotationItem.php
│   └── Setting.php
└── Middleware/
    ├── CheckPermission.php               # ตรวจสอบสิทธิ์
    └── EnsureTwoFactorIsVerified.php     # ตรวจสอบ 2FA

    