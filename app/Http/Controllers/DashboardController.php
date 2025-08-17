<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Supplier;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Pengeluaran;
use RealRashid\SweetAlert\Facades\Alert;

class DashboardController extends Controller
{
    public function index()
    {
        $kategori = Kategori::count();

        if (auth()->user()->level == 4) {
            $produk = Produk::where('id_kategori', 4)->count();
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $produk = Produk::where('id_kategori', 5)->count();
        } elseif (auth()->user()->level == 1) {
            $produk = Produk::count();

            $expired_products = Produk::where([['tanggal_expire', '<=', Carbon::now()->addDays(7)], ['id_kategori', '!=', 4], ['id_kategori', '!=', 5]])
                ->whereNotNull('tanggal_expire')
                ->get();

            if ($expired_products->isNotEmpty()) {
                Alert::warning('Produk Kadaluarsa', "Halo, " . $expired_products->count() . " Produk yang habis masa berlaku dalam 7 hari ke depan. Harap pastikan untuk mengelola stok produk Anda.");
            }
        } else {
            $expired_products = Produk::where([['tanggal_expire', '<=', Carbon::now()->addDays(7)], ['id_kategori', '!=', 4], ['id_kategori', '!=', 5]])
                ->whereNotNull('tanggal_expire')
                ->get();
            $produk = Produk::where([['id_kategori', '!=', 4], ['id_kategori', '!=', 5]])->count();

            if ($expired_products->isNotEmpty()) {
                Alert::warning('Produk Kadaluarsa', "Halo, " . $expired_products->count() . " Produk yang habis masa berlaku dalam 7 hari ke depan. Harap pastikan untuk mengelola stok produk Anda.");
            }
        }

        $supplier = Supplier::count();
        $member = Member::count();

        $tanggal_awal = date('Y-m-01');
        $tanggal_akhir = date('Y-m-d');

        $data_tanggal = array();
        $data_pendapatan = array();

        while (strtotime($tanggal_awal) <= strtotime($tanggal_akhir)) {
            $data_tanggal[] = (int) substr($tanggal_awal, 8, 2);

            $total_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('bayar');
            $total_pembelian = Pembelian::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('bayar');
            $total_pengeluaran = Pengeluaran::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('nominal');

            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $data_pendapatan[] += $pendapatan;

            $tanggal_awal = date('Y-m-d', strtotime("+1 day", strtotime($tanggal_awal)));
        }

        if (auth()->user()->level == 6 || auth()->user()->level == 8) {
            return view('kasir.dashboard');
        } else {
            return view('admin.dashboard', compact('kategori', 'produk', 'supplier', 'member', 'tanggal_awal', 'tanggal_akhir', 'data_tanggal', 'data_pendapatan'));
        }
    }
}
