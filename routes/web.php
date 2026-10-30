<?php

use App\Http\Controllers\ApAgingController;
use App\Http\Controllers\ArAgingController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CarBrandController;
use App\Http\Controllers\CashierShiftController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftScheduleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockDocumentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TwoFactorChallengeController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ==========================================
// ✅ Routes สำหรับ 2FA Challenge (เข้าถึงได้ก่อนยืนยัน 2FA)
// ==========================================
Route::middleware(['auth'])->group(function () {
    Route::get('/2fa-challenge', [TwoFactorChallengeController::class, 'show'])->name('2fa.challenge');
    Route::post('/2fa-verify', [TwoFactorChallengeController::class, 'verify'])->name('2fa.verify');
});

// ==========================================
// กลุ่ม Routes ที่ต้องล็อกอิน + ยืนยัน 2FA แล้วเท่านั้น
// ==========================================
Route::middleware(['auth', '2fa'])->group(function () {

    // 1. หน้า Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. หน้าจัดการผู้ใช้ (Admin)
    Route::middleware('permission:manage-users')->group(function () {
        Route::resource('users', UserController::class);

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
        Route::put('/settings/{key}/update', [SettingController::class, 'updateCustom'])->name('settings.update-custom');
        Route::patch('/settings/{key}/toggle', [SettingController::class, 'toggleActive'])->name('settings.toggle');
        Route::delete('/settings/{key}', [SettingController::class, 'destroy'])->name('settings.destroy');

        // ตารางกะพนักงาน (Shift Schedule)
        Route::get('/shift-schedules/{shiftSchedule}/edit-data', [ShiftScheduleController::class, 'editData'])->name('shift-schedules.edit-data');
        Route::get('/shift-schedules/events', [ShiftScheduleController::class, 'getEvents'])->name('shift-schedules.events');
        Route::resource('shift-schedules', ShiftScheduleController::class)->except(['create', 'edit', 'show']);
    });

    // 3. หน้าจัดการข้อมูลหลัก (Admin, Stock)
    Route::middleware('permission:manage-master-data')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('brands', BrandController::class);
        Route::resource('car-brands', CarBrandController::class);
        Route::resource('units', UnitController::class);
        Route::post('/car-models', [CarBrandController::class, 'storeCarModel'])->name('car-models.store');
        Route::put('/car-models/{id}', [CarBrandController::class, 'updateCarModel'])->name('car-models.update');
        Route::delete('/car-models/{id}', [CarBrandController::class, 'destroyCarModel'])->name('car-models.destroy');
    });

    // ==========================================
    // 4. หน้าสินค้า (⚠️ ลำดับสำคัญมาก)
    // ==========================================
    Route::middleware('permission:view-products')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    });

    Route::middleware('permission:create-products')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    });

    Route::middleware('permission:edit-products')->group(function () {
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    });

    Route::middleware('permission:delete-products')->group(function () {
        Route::patch('/products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // ==========================================
    // 5. หน้าคลังสินค้า (Stock Documents & PO & Adjustment)
    // ==========================================

    // ✅ 5.1: Index
    Route::middleware('permission:view-stock')->group(function () {
        Route::get('/stock-documents', [StockDocumentController::class, 'index'])->name('stock-documents.index');

        // ความเคลื่อนไหวสต็อก
        Route::get('/reports/stock-movement', [StockMovementController::class, 'index'])->name('reports.stock-movement');

    });

    // ✅ 5.2: Create & Store (ต้องมาก่อน /{stockDocument})
    Route::middleware('permission:create-stock-document')->group(function () {
        Route::get('/stock-documents/create', [StockDocumentController::class, 'create'])->name('stock-documents.create');
        Route::post('/stock-documents', [StockDocumentController::class, 'store'])->name('stock-documents.store');
        Route::get('/stock-documents/{stockDocument}/edit', [StockDocumentController::class, 'edit'])->name('stock-documents.edit');
        Route::put('/stock-documents/{stockDocument}', [StockDocumentController::class, 'update'])->name('stock-documents.update');
    });

    // ✅ 5.3: Purchase Orders
    Route::middleware('permission:create-stock-document')->group(function () {
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
    });

    // ✅ 5.3b: Stock Adjustments (แยกสิทธิ์จากเอกสารรับ/เบิก)
    Route::middleware('permission:adjust-stock')->group(function () {
        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    });

    // ✅ 5.4: Show (⚠️ ต้องอยู่ล่างสุดเสมอ)
    Route::middleware('permission:view-stock')->group(function () {
        Route::get('/stock-documents/{stockDocument}', [StockDocumentController::class, 'show'])->name('stock-documents.show');
    });

    // ✅ 5.5: Void
    Route::middleware('permission:void-stock-document')->group(function () {
        Route::delete('/stock-documents/{stockDocument}', [StockDocumentController::class, 'destroy'])->name('stock-documents.destroy');
    });

    // ==========================================
    // 6. หน้าขาย POS (Admin, Cashier)
    // ==========================================
    Route::middleware('permission:create-sale')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        // ==========================================
        //  ระบบเปิด-ปิดกะ (Cashier Shift)
        // ==========================================
        Route::get('/cashier-shifts', [CashierShiftController::class, 'index'])->name('cashier-shifts.index');
        Route::post('/cashier-shifts', [CashierShiftController::class, 'store'])->name('cashier-shifts.store');
        Route::post('/cashier-shifts/{cashierShift}/close', [CashierShiftController::class, 'close'])->name('cashier-shifts.close');
        Route::get('/cashier-shifts/{cashierShift}', [CashierShiftController::class, 'show'])->name('cashier-shifts.show');
    });

    // ⚠️ /sales/{sale} ต้องอยู่หลัง /sales/create (ถ้ามี)
    Route::middleware('permission:view-sales')->group(function () {
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    });

    Route::middleware('permission:void-sale')->group(function () {
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    });

    Route::middleware('permission:view-sales')->group(function () {
        Route::get('/sales-history', [SaleController::class, 'history'])->name('sales.history');
    });

    // ✅ Quotations (ใบเสนอราคา)
    Route::middleware('permission:create-sale')->group(function () {
        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
        Route::post('/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.update-status');
        Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convertToSale'])->name('quotations.convert');
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'exportPdf'])->name('quotations.pdf');
    });

    // ✅ Receipts (บิลชำระเงิน)
    Route::middleware('permission:manage-receipts')->group(function () {
        Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/create-from-sale/{sale}', [ReceiptController::class, 'createFromSale'])->name('receipts.create-from-sale');
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    });

    // ยกเลิกใบรับเงิน: เฉพาะ admin / accountant (แยกจากการรับเงิน)
    Route::middleware('permission:void-receipt')->group(function () {
        Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
    });

    // ==========================================
    // 7. หน้าจัดการคู่ค้า (Admin, Cashier)
    // ==========================================
    Route::middleware('permission:manage-customers')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['show']);
    });

    Route::middleware('permission:manage-suppliers')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show']);
    });

    // ==========================================
    // 8. หน้ารายงาน (Admin, Accountant)
    // ==========================================
    Route::middleware('permission:view-reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        // รายงานลูกหนี้ (A/R Aging)
        Route::get('/reports/ar-aging', [ArAgingController::class, 'index'])->name('reports.ar-aging');
        Route::get('/reports/ar-aging/export-pdf', [ArAgingController::class, 'exportPdf'])->name('reports.ar-aging.export-pdf');
        // รายงานเจ้าหนี้ (A/P Aging)
        Route::get('/reports/ap-aging', [ApAgingController::class, 'index'])->name('reports.ap-aging');
        Route::get('/reports/ap-aging/export-pdf', [ApAgingController::class, 'exportPdf'])->name('reports.ap-aging.export-pdf');
    });

    // ระบบค่าใช้จ่าย (Expenses) แยกสิทธิ์จากรายงาน
    Route::middleware('permission:manage-expenses')->group(function () {
        Route::get('/expenses/{expense}/edit-data', [ExpenseController::class, 'editData'])->name('expenses.edit-data');
        Route::resource('expenses', ExpenseController::class)->except(['create', 'edit', 'show']);
    });

    // ==========================================
    // 9. หน้า Profile (ทุกคน)
    // ==========================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ==========================================
    // 10. หน้าตั้งค่า 2FA
    // ==========================================
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

});

require __DIR__ . '/auth.php';
