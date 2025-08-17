<?php

namespace App\Console\Commands;

use App\Models\BackupProduk;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DBProduk extends Command
{
    protected $signature = 'db:produk';
    protected $description = 'Backup produk data on the first day of each month';

    public function handle()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');

        // First backup run - end of previous month
        $results = DB::table('produk')
            ->select(
                'produk.id_produk',
                'produk.id_kategori',
                'produk.nama_produk',
                'produk.satuan',
                'produk.harga_beli',
                'produk.tanggal_expire',
                'produk.stok_lama',
                DB::raw("(SELECT COALESCE(SUM(jumlah), 0) FROM pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk AND DATE_FORMAT(pembelian_detail.created_at, '%Y-%m') = '$previousMonth') as total_jumlah_pembelian"),
                DB::raw("(SELECT COALESCE(SUM(jumlah), 0) FROM penjualan_detail WHERE penjualan_detail.id_produk = produk.id_produk AND DATE_FORMAT(penjualan_detail.created_at, '%Y-%m') = '$previousMonth') as total_jumlah_penjualan")
            )
            ->get();

        $results->map(function ($results) {
            $stok_awal = $results->stok_lama ?? 0;
            $stok_akhir = $stok_awal + $results->total_jumlah_pembelian - $results->total_jumlah_penjualan;
            
            $backup = new BackupProduk();
            $backup->id_produk = $results->id_produk;
            $backup->id_kategori = $results->id_kategori;
            $backup->nama_produk = $results->nama_produk;
            $backup->satuan = $results->satuan;
            $backup->harga_beli = $results->harga_beli;
            $backup->stok_awal = $stok_awal;
            $backup->stok_akhir = $stok_akhir;
            $backup->stok_belanja = $results->total_jumlah_pembelian;
            $backup->total_belanja = $results->total_jumlah_pembelian;
            $backup->tanggal_expire = $results->tanggal_expire;
            $backup->created_at = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();
            $backup->updated_at = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();
            $backup->save();
        });

        // Update produk table with calculated stok values
        $results->each(function ($result) {
            $stok_akhir = ($result->stok_lama ?? 0) + $result->total_jumlah_pembelian - $result->total_jumlah_penjualan;
            DB::table('produk')
                ->where('id_produk', $result->id_produk)
                ->update([
                    'stok' => $stok_akhir,
                    'stok_lama' => $stok_akhir
                ]);
        });

        $hasil = DB::table('produk')
            ->select(
                'produk.id_produk',
                'produk.id_kategori',
                'produk.nama_produk',
                'produk.satuan',
                'produk.harga_beli',
                'produk.tanggal_expire',
                'produk.stok_lama',
                DB::raw("(SELECT COALESCE(SUM(jumlah), 0) FROM pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk AND DATE_FORMAT(pembelian_detail.created_at, '%Y-%m') = '$currentMonth') as total_jumlah_pembelian"),
                DB::raw("(SELECT COALESCE(SUM(jumlah), 0) FROM penjualan_detail WHERE penjualan_detail.id_produk = produk.id_produk AND DATE_FORMAT(penjualan_detail.created_at, '%Y-%m') = '$currentMonth') as total_jumlah_penjualan")
            )
            ->get();

        $hasil->map(function ($hasil) {
            $stok_awal = $hasil->stok_lama ?? 0;
            $stok_akhir = $stok_awal + $hasil->total_jumlah_pembelian - $hasil->total_jumlah_penjualan;
            
            $backup = new BackupProduk();
            $backup->id_produk = $hasil->id_produk;
            $backup->id_kategori = $hasil->id_kategori;
            $backup->nama_produk = $hasil->nama_produk;
            $backup->satuan = $hasil->satuan;
            $backup->harga_beli = $hasil->harga_beli;
            $backup->stok_awal = $stok_awal;
            $backup->stok_akhir = $stok_akhir;
            $backup->stok_belanja = $hasil->total_jumlah_pembelian;
            $backup->total_belanja = $hasil->harga_beli * $hasil->total_jumlah_pembelian;
            $backup->tanggal_expire = $hasil->tanggal_expire;
            $backup->created_at = Carbon::now()->startOfMonth();
            $backup->updated_at = Carbon::now()->startOfMonth();
            $backup->save();
        });

        $this->info("Backup created for both end-of-last-month and start-of-this-month for {$currentMonth}");
    }
}