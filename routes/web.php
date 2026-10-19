<?php
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CarBrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockDocumentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CustomerController;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::resource('products', ProductController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('units', UnitController::class);

    Route::get('/stock-documents', [StockDocumentController::class, 'index'])->name('stock-documents.index');
    Route::get('/stock-documents/create', [StockDocumentController::class, 'create'])->name('stock-documents.create');
    Route::post('/stock-documents', [StockDocumentController::class, 'store'])->name('stock-documents.store');
    Route::get('/stock-documents/{stockDocument}', [StockDocumentController::class, 'show'])->name('stock-documents.show');
    Route::get('/stock-documents/{stockDocument}/edit', [StockDocumentController::class, 'edit'])->name('stock-documents.edit');
    Route::put('/stock-documents/{stockDocument}', [StockDocumentController::class, 'update'])->name('stock-documents.update');
    Route::delete('/stock-documents/{stockDocument}', [StockDocumentController::class, 'destroy'])->name('stock-documents.destroy');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    Route::get('/sales-history', [SaleController::class, 'history'])->name('sales.history');

    Route::resource('brands', BrandController::class);
    Route::resource('car-brands', CarBrandController::class);
    Route::post('/car-models', [App\Http\Controllers\CarBrandController::class, 'storeCarModel'])->name('car-models.store');
    Route::put('/car-models/{id}', [App\Http\Controllers\CarBrandController::class, 'updateCarModel'])->name('car-models.update');
    Route::delete('/car-models/{id}', [App\Http\Controllers\CarBrandController::class, 'destroyCarModel'])->name('car-models.destroy');
    Route::resource('customers', CustomerController::class);

    Route::get('/dashboard', function () {
        // ดึงข้อมูลสรุป
        $totalProducts  = Product::count();
        $totalSuppliers = Supplier::count();

        // คำนวณมูลค่าสต็อกรวม (ราคาขาย x จำนวนสต็อก)
        $totalStockValue = Product::sum(DB::raw('price * stock'));

        // ดึงสินค้าที่สต็อกต่ำกว่า 10 ชิ้น (แจ้งเตือน)
        $lowStockProducts = Product::where('stock', '<', 10)->where('is_active', true)->get();

        return view('dashboard', compact('totalProducts', 'totalSuppliers', 'totalStockValue', 'lowStockProducts'));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
