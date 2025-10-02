<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\BackupProdukController;
use App\Http\Controllers\JasaController;
use App\Http\Controllers\PembelianDetailController;
use App\Http\Controllers\PenjualanDetailController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\SimpananIndukController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 1 Admin
    // 2 Waserda
    // 3 Gudang
    // 4 Bengkel
    // 5 Fotocopy Admin
    // 6 Kasir
    // 7 SHU
    // 8 Kasir Fotocopy

    Route::group(['middleware' => 'level:1,2,4,5,6,8'], function () {
        Route::get('/member/data', [MemberController::class, 'data'])->name('member.data');
        Route::post('/member/cetak-member', [MemberController::class, 'cetakMember'])->name('member.cetak_member');
        Route::resource('/member', MemberController::class);

        Route::get('/pengeluaran/data', [PengeluaranController::class, 'data'])->name('pengeluaran.data');
        Route::resource('/pengeluaran', PengeluaranController::class);

        Route::get('/penjualan/data', [PenjualanController::class, 'data'])->name('penjualan.data');
        Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
        Route::get('/penjualan/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');
        Route::delete('/penjualan/{id}', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');
        Route::get('/penjualan/{pembayaran}/{awal}/{akhir}', [PenjualanController::class, 'pdf'])->name('penjualan.pdf');

        Route::get('/transaksi-penjualan-nota/{jenis}/{id}', [PenjualanController::class, 'nota'])->name('penjualan.nota');
    });
    Route::group(['middleware' => 'level:1,4'], function () {
        Route::get('/jasa/data', [JasaController::class, 'data'])->name('jasa.data');
        Route::resource('/jasa', JasaController::class);
        Route::get('/jasa/{jasa}/{awal}/{akhir}', [JasaController::class, 'pdf'])->name('jasa.pdf');
        Route::get('/transaksi-jasa/{id}', [JasaController::class, 'nota'])->name('transaksi.jasa');
    });

    Route::group(['middleware' => 'level:1'], function() {
        Route::get('/kategori/data', [KategoriController::class, 'data'])->name('kategori.data');
        Route::resource('/kategori', KategoriController::class);
    });

    Route::group(['middleware' => 'level:1,2,3,4,5'], function () {
        Route::get('/pembelian/data', [PembelianController::class, 'data'])->name('pembelian.data');

        Route::get('/pembelian/selesai', [PembelianController::class, 'selesai'])->name('pembelian.selesai');
        Route::get('/pembelian/kwitansi', [PembelianController::class, 'kwitansi'])->name('pembelian.kwitansi');

        Route::get('/pembelian/pdf/{awal}/{akhir}', [PembelianController::class, 'pdf'])->name('pembelian.pdf');
        Route::get('/pembelian/{id}/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::resource('/pembelian', PembelianController::class)
            ->except('create');

        Route::get('/pembelian_detail/{id}/data', [PembelianDetailController::class, 'data'])->name('pembelian_detail.data');
        Route::get('/pembelian_detail/loadform/{diskon}/{total}', [PembelianDetailController::class, 'loadForm'])->name('pembelian_detail.load_form');
        Route::resource('/pembelian_detail', PembelianDetailController::class)
            ->except('create', 'show', 'edit');

        Route::get('/pembelian_detail/search', [PembelianDetailController::class, 'search'])->name('search-pembelian');

        Route::get('/supplier/data', [SupplierController::class, 'data'])->name('supplier.data');
        Route::get('/supplier/pdf', [SupplierController::class, 'pdf'])->name('supplier.pdf');
        Route::resource('/supplier', SupplierController::class);
    });

    Route::group(['middleware' => 'level:1,2,3,4,5,6,8'], function () {
        Route::get('/produk/data', [ProdukController::class, 'data'])->name('produk.data');
        Route::post('/produk/delete-selected', [ProdukController::class, 'deleteSelected'])->name('produk.delete_selected');
        Route::post('/produk/cetak-barcode', [ProdukController::class, 'cetakBarcode'])->name('produk.cetak_barcode');
        Route::get('/produk/stok/{awal}/{akhir}', [ProdukController::class, 'pdf'])->name('produk.pdf');
        Route::resource('/produk', ProdukController::class);
    });

    Route::group(['middleware' => 'level:1,2,4,5,6,8'], function () {

        Route::get('/transaksi/baru', [PenjualanController::class, 'create'])->name('transaksi.baru');
        Route::post('/transaksi/simpan', [PenjualanController::class, 'store'])->name('transaksi.simpan');
        Route::delete('/transaksi/batal/{id}', [PenjualanController::class, 'cancel'])->name('transaksi.batal');
        Route::get('/transaksi/cleanup', function() {
            $cleaned = App\Http\Controllers\PenjualanController::cleanupIncompleteTransactions();
            return response()->json(['message' => "Cleaned up {$cleaned} incomplete transactions"]);
        })->name('transaksi.cleanup');
        Route::get('/transaksi/selesai', [PenjualanController::class, 'selesai'])->name('transaksi.selesai');
        Route::get('/transaksi/nota-kecil', [PenjualanController::class, 'notaKecil'])->name('transaksi.nota_kecil');
        Route::get('/transaksi/nota-besar', [PenjualanController::class, 'notaBesar'])->name('transaksi.nota_besar');

        Route::get('/laporan-kasir', [KasirController::class, 'index'])->name('laporan.kasir');
        Route::get('/kasir/data', [KasirController::class, 'data'])->name('kasir.data');
        Route::get('/kasir/{id}', [KasirController::class, 'show'])->name('kasir.show');
        Route::get('/kasir/pdf/{awal}/{akhir}', [KasirController::class, 'laporan'])->name('kasir.pdf');

        Route::get('/produk/stok/{id_produk}', [PenjualanDetailController::class, 'stok'])->name('produk.stok');

        Route::get('/transaksi/{id}/data', [PenjualanDetailController::class, 'data'])->name('transaksi.data');
        Route::get('/transaksi/loadform/{diskon}/{total}/{diterima}', [PenjualanDetailController::class, 'loadForm'])->name('transaksi.load_form');
        Route::resource('/transaksi', PenjualanDetailController::class)
            ->except('create', 'show', 'edit');
        Route::get('/transaksi/search', [PenjualanDetailController::class, 'search'])->name('search');
    });

    Route::group(['middleware' => 'level:1,2,4,5,7'], function () {
        Route::get('/user/data', [UserController::class, 'data'])->name('user.data');
        Route::resource('/user', UserController::class);
    });

    Route::group(['middleware' => 'level:1,7'], function () {
        Route::get('/simpanan/data', [SimpananController::class, 'data'])->name('simpanan.data');
        Route::resource('simpanan', SimpananController::class);
        Route::get('/transaksi-simpanan', [SimpananController::class, 'transaksi'])->name('simpanan.transaksi');
        Route::get('/transaksi-simpanan/selesai', [SimpananController::class, 'selesai'])->name('simpanan.selesai');
        Route::get('/transaksi-simpanan/loadform/{pokok}/{wajib}/{manasuka}', [SimpananController::class, 'loadForm'])->name('simpanan.load_form');

        Route::get('simpanan/simpanan-induk', [SimpananIndukController::class, 'index'])->name('simpanan_induk.index');
        Route::post('simpanan/simpanan-induk', [SimpananIndukController::class, 'store'])->name('simpanan_induk.store');
        Route::get('simpanan/simpanan-induk/data', [SimpananIndukController::class, 'data'])->name('simpanan_induk.data');

    });
    
    Route::group(['middleware' => 'level:1,2,4,5,8'], function () {
        Route::post('/produk/backup', [BackupProdukController::class, 'store'])->name('produk.backup_data');

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/data/{awal}/{akhir}', [LaporanController::class, 'data'])->name('laporan.data');
        Route::get('/laporan/pdf/{awal}/{akhir}', [LaporanController::class, 'exportPDF'])->name('laporan.export_pdf');
        Route::get('/laporan/laba-rugi/{awal}/{akhir}', [LaporanController::class, 'labaPdf'])->name('laporan.labaPdf');
        Route::get('/laporan/hpp/{tanggal_awal}/{tanggal_akhir}', [LaporanController::class, 'hpp'])->name('laporan.hpp');
        Route::get('/laporan/hasil_usaha/{awal}/{akhir}', [LaporanController::class, 'hasil_usaha'])->name('laporan.hasil_usaha');
        Route::get('/laporan/shu/{awal}/{akhir}', [LaporanController::class, 'shu'])->name('laporan.shu');
        Route::get('/laporan/jurnal_penjualan/{tanggal_aw}/{tanggal_ak}', [LaporanController::class, 'jurnal_penjualan'])->name('laporan.jurnal_penjualan');
        Route::get('/laporan/jurnal_pembelian/{tang_awal}/{tang_akhir}', [LaporanController::class, 'jurnal_pembelian'])->name('laporan.jurnal_pembelian');

        Route::get('/pengeluaran/{jenis}/{awal}/{akhir}', [PengeluaranController::class, 'pdf'])->name('pengeluaran.pdf');
        Route::get('/export-anggota-kpri', [MemberController::class, 'pdf'])->name('member.pdf');

        Route::get('/setting', [SettingController::class, 'index'])->name('setting.index');
        Route::get('/setting/first', [SettingController::class, 'show'])->name('setting.show');
        Route::post('/setting', [SettingController::class, 'update'])->name('setting.update');
    });
 
    Route::group(['middleware' => 'level:1,2,4,5'], function () {
        Route::get('/profil', [UserController::class, 'profil'])->name('user.profil');
        Route::post('/profil', [UserController::class, 'updateProfil'])->name('user.update_profil');
    });
});