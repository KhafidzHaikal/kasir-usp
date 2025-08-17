<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use Carbon\Carbon;

use App\Models\Produk;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use App\Models\PembelianDetail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as Barpdf;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $tanggalAkhir = date('Y-m-d');

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        return view('laporan.index', compact('tanggalAwal', 'tanggalAkhir'));
    }

    public function getData($awal, $akhir)
    {
        $no = 1;
        $data = array();
        $pendapatan = 0;
        $total_pendapatan = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            $tanggal = $awal;
            $awal = date('Y-m-d', strtotime("+1 day", strtotime($awal)));

            if (auth()->user()->level == 4) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 4]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 4]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 4]])
                    ->sum('pengeluaran.nominal');
            } elseif (auth()->user()->level == 5) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->orWhere([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->orWhere([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->orWhere([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pengeluaran.nominal');
            } elseif (auth()->user()->level == 8) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pengeluaran.nominal');
            } elseif (auth()->user()->level == 1) {
                $total_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal%")->sum('bayar');
                $total_pembelian = Pembelian::where('created_at', 'LIKE', "%$tanggal%")->sum('bayar');
                $total_pengeluaran = Pengeluaran::where('created_at', 'LIKE', "%$tanggal%")->sum('nominal');
            } elseif (auth()->user()->level == 2) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->orWhere([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->orWhere([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->orWhere([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pengeluaran.nominal');
            } else {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pengeluaran.nominal');
            }


            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $total_pendapatan += $pendapatan;

            $row = array();
            $row['DT_RowIndex'] = $no++;
            $row['tanggal'] = tanggal_indonesia($tanggal, false);
            $row['penjualan'] = format_uang($total_penjualan);
            $row['pembelian'] = format_uang($total_pembelian);
            $row['pengeluaran'] = format_uang($total_pengeluaran);
            $row['pendapatan'] = format_uang($pendapatan);

            $data[] = $row;
        }

        $data[] = [
            'DT_RowIndex' => '',
            'tanggal' => '',
            'penjualan' => '',
            'pembelian' => '',
            'pengeluaran' => 'Total Pendapatan',
            'pendapatan' => format_uang($total_pendapatan),
        ];

        return $data;
    }

    public function data($awal, $akhir)
    {
        $data = $this->getData($awal, $akhir);

        return datatables()
            ->of($data)
            ->make(true);
    }

    public function exportPDF($awal, $akhir)
    {
        $no = 1;
        $data = array();
        $pendapatan = 0;
        $total_pendapatan = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            $tanggal = $awal;
            $awal = date('Y-m-d', strtotime("+1 day", strtotime($awal)));

            if (auth()->user()->level == 4) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 4]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 4]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 4]])
                    ->sum('pengeluaran.nominal');
            } elseif (auth()->user()->level == 5) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->orWhere([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->orWhere([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->orWhere([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pengeluaran.nominal');
            } elseif (auth()->user()->level == 8) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 8]])
                    ->sum('pengeluaran.nominal');
            } elseif (auth()->user()->level == 1) {
                $total_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal%")->sum('bayar');
                $total_pembelian = Pembelian::where('created_at', 'LIKE', "%$tanggal%")->sum('bayar');
                $total_pengeluaran = Pengeluaran::where('created_at', 'LIKE', "%$tanggal%")->sum('nominal');
            } elseif (auth()->user()->level == 2) {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->orWhere([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->orWhere([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->orWhere([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pengeluaran.nominal');
            } else {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 6]])
                    ->sum('pengeluaran.nominal');
            }

            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $total_pendapatan += $pendapatan;

            $row = array();
            $row['DT_RowIndex'] = $no++;
            $row['tanggal'] = tanggal_indonesia($tanggal, false);
            $row['penjualan'] = format_uang($total_penjualan);
            $row['pembelian'] = format_uang($total_pembelian);
            $row['pengeluaran'] = format_uang($total_pengeluaran);
            $row['pendapatan'] = format_uang($pendapatan);

            $data[] = $row;
        }

        $data[] = [
            'DT_RowIndex' => '',
            'tanggal' => '',
            'penjualan' => '',
            'pembelian' => '',
            'pengeluaran' => 'Total Pendapatan',
            'pendapatan' => format_uang($total_pendapatan),
        ];

        $data = collect($data)->map(function ($item) {
            return (object) $item;
        });
        $pdf  = Barpdf::loadView('laporan.pdf', compact('awal', 'akhir', 'data'))->setPaper('a4', 'potrait');

        return $pdf->stream('Laporan-pendapatan-' . date('Y-m-d-his') . '.pdf');
    }

    public function labaPdf($awal, $akhir)
    {
        $akhir = Carbon::parse($akhir)->endOfDay();
        $results = DB::table('backup_produks')
            ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
            ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
            ->whereBetween('backup_produks.created_at', [$awal, $akhir])
            ->select(
                'backup_produks.id_produk',
                'backup_produks.nama_produk',
                'backup_produks.satuan',
                'backup_produks.harga_beli',
                DB::raw('(select sum(jumlah) from pembelian_detail where pembelian_detail.id_produk = backup_produks.id_produk and pembelian_detail.created_at between "' . $awal . '" and "' . $akhir . '" group by pembelian_detail.id_produk) as stok_belanja'),
                'backup_produks.created_at',
                'produk.harga_jual',
            )
            ->groupBy('backup_produks.id_produk')
            ->get();
        // dd($results);

        $total_laba_rugi = 0;

        foreach ($results as $row) {
            $total_laba_rugi += ($row->harga_jual * $row->stok_belanja) - ($row->harga_beli * $row->stok_belanja);
        }

        // dd($total_laba_rugi);
        $pdf = PDF::loadView('laporan.laba_rugi', compact('awal', 'akhir', 'results', 'total_laba_rugi'))->setPaper('a4');
        return $pdf->inline('Laporan-laba_Rugi-' . date('Y-m-d-his') . '.pdf');
    }

    public function hpp($tanggal_awal, $tanggal_akhir)
    {
        $tanggal_akhir = Carbon::parse($tanggal_akhir)->endOfDay();
        
        if (auth()->user()->level == 1) {
            $results = DB::table('backup_produks as bp')
                ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
                ->whereBetween('bp.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'bp.id_produk',
                    'bp.nama_produk',
                    'bp.satuan',
                    'bp.harga_beli',
                    DB::raw("COALESCE((SELECT stok_awal FROM backup_produks WHERE id_produk = bp.id_produk AND created_at >= '$tanggal_awal' ORDER BY created_at ASC LIMIT 1), 0) as stok_awal"),
                    DB::raw("COALESCE((SELECT stok_akhir FROM backup_produks WHERE id_produk = bp.id_produk AND created_at <= '$tanggal_akhir' ORDER BY created_at DESC LIMIT 1), 0) as stok_akhir"),
                    DB::raw("COALESCE((SELECT SUM(jumlah) FROM pembelian_detail WHERE id_produk = bp.id_produk AND created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) as stok_belanja")
                )
                ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli')
                ->get();
        } elseif (auth()->user()->level == 4) {
            $results = DB::table('backup_produks as bp')
                ->where('bp.id_kategori', 4)
                ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
                ->whereBetween('bp.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'bp.id_produk',
                    'bp.nama_produk',
                    'bp.satuan',
                    'bp.harga_beli',
                    DB::raw("COALESCE((SELECT stok_awal FROM backup_produks WHERE id_produk = bp.id_produk AND created_at >= '$tanggal_awal' ORDER BY created_at ASC LIMIT 1), 0) as stok_awal"),
                    DB::raw("COALESCE((SELECT stok_akhir FROM backup_produks WHERE id_produk = bp.id_produk AND created_at <= '$tanggal_akhir' ORDER BY created_at DESC LIMIT 1), 0) as stok_akhir"),
                    DB::raw("COALESCE((SELECT SUM(jumlah) FROM pembelian_detail WHERE id_produk = bp.id_produk AND created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) as stok_belanja")
                )
                ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli')
                ->get();
        } elseif (auth()->user()->level == 5) {
            $results = DB::table('backup_produks as bp')
                ->where('bp.id_kategori', 5)
                ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
                ->whereBetween('bp.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'bp.id_produk',
                    'bp.nama_produk',
                    'bp.satuan',
                    'bp.harga_beli',
                    DB::raw("COALESCE((SELECT stok_awal FROM backup_produks WHERE id_produk = bp.id_produk AND created_at >= '$tanggal_awal' ORDER BY created_at ASC LIMIT 1), 0) as stok_awal"),
                    DB::raw("COALESCE((SELECT stok_akhir FROM backup_produks WHERE id_produk = bp.id_produk AND created_at <= '$tanggal_akhir' ORDER BY created_at DESC LIMIT 1), 0) as stok_akhir"),
                    DB::raw("COALESCE((SELECT SUM(jumlah) FROM pembelian_detail WHERE id_produk = bp.id_produk AND created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) as stok_belanja")
                )
                ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli')
                ->get();
        } else {
            $results = DB::table('backup_produks as bp')
                ->where([['bp.id_kategori', '!=', 4], ['bp.id_kategori', '!=', 5]])
                ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
                ->whereBetween('bp.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'bp.id_produk',
                    'bp.nama_produk',
                    'bp.satuan',
                    'bp.harga_beli',
                    DB::raw("COALESCE((SELECT stok_awal FROM backup_produks WHERE id_produk = bp.id_produk AND created_at >= '$tanggal_awal' ORDER BY created_at ASC LIMIT 1), 0) as stok_awal"),
                    DB::raw("COALESCE((SELECT stok_akhir FROM backup_produks WHERE id_produk = bp.id_produk AND created_at <= '$tanggal_akhir' ORDER BY created_at DESC LIMIT 1), 0) as stok_akhir"),
                    DB::raw("COALESCE((SELECT SUM(jumlah) FROM pembelian_detail WHERE id_produk = bp.id_produk AND created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) as stok_belanja")
                )
                ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli')
                ->get();
        }

        $totalValue = 0;
        $totalAwal = 0;
        $totalBeli = 0;
        $totalAkhir = 0;

        foreach ($results as $result) {
            // Ensure all values are numeric and handle nulls
            $stok_awal = (float) ($result->stok_awal ?? 0);
            $stok_belanja = (float) ($result->stok_belanja ?? 0);
            $stok_akhir = (float) ($result->stok_akhir ?? 0);
            $harga_beli = (float) ($result->harga_beli ?? 0);
            
            // Calculate HPP with proper null handling
            $hpp = (($harga_beli * $stok_awal) + ($stok_belanja * $harga_beli)) - ($harga_beli * $stok_akhir);
            
            $totalValue += $hpp;
            $totalAwal += $harga_beli * $stok_awal;
            $totalBeli += $stok_belanja * $harga_beli;
            $totalAkhir += $harga_beli * $stok_akhir;
            
            // Update result object with calculated values
            $result->stok_awal = $stok_awal;
            $result->stok_belanja = $stok_belanja;
            $result->stok_akhir = $stok_akhir;
            $result->harga_beli = $harga_beli;
        }

        return view('laporan.hpp', compact('tanggal_awal', 'tanggal_akhir', 'results', 'totalValue', 'totalAwal', 'totalBeli', 'totalAkhir'));
    }

    public function hasil_usaha($tanggal_awal, $tanggal_akhir)
    {
        $tanggal_akhir = Carbon::parse($tanggal_akhir)->endOfDay();
        $jasa = Jasa::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])
            ->sum('nominal');
        if (auth()->user()->level == 4) {
            $results = DB::table('backup_produks')
                ->where('backup_produks.id_kategori', 4)
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$tanggal_awal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $tanggal_awal . "' and '" . $tanggal_akhir . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_awal, $tanggal_akhir])
                ->sum('penjualan_detail.subtotal');
        } elseif (auth()->user()->level == 5) {
            $results = DB::table('backup_produks')
                ->where('backup_produks.id_kategori', 5)
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$tanggal_awal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $tanggal_awal . "' and '" . $tanggal_akhir . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_awal, $tanggal_akhir])
                ->sum('penjualan_detail.subtotal');
        } elseif (auth()->user()->level == 1) {
            $results = DB::table('backup_produks')
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$tanggal_awal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $tanggal_awal . "' and '" . $tanggal_akhir . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')->whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])->sum('subtotal');
        } else {
            $results = DB::table('backup_produks')
                ->where([['backup_produks.id_kategori', '!=', 4], ['backup_produks.id_kategori', '!=', 5]])
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$tanggal_awal, $tanggal_akhir])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$tanggal_awal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$tanggal_akhir' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $tanggal_awal . "' and '" . $tanggal_akhir . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('penjualan_detail.created_at', [$tanggal_awal, $tanggal_akhir])
                ->sum('penjualan_detail.subtotal');
        }

        $totalValue = 0;

        foreach ($results as $result) {
            $totalValue += (($result->harga_beli * $result->stok_awal) + ($result->stok_belanja * $result->harga_beli)) - ($result->harga_beli * $result->stok_akhir);
        }

        return view('laporan.hasil_usaha', ['awal' => $tanggal_awal, 'akhir' => $tanggal_akhir, 'penjualan'  => $penjualan, 'hpp' => $totalValue, 'jasa' => $jasa]);
    }

    public function shu($awal_tanggal, $akhir_tanggal)
    {
        $akhir_tanggal = Carbon::parse($akhir_tanggal)->endOfDay();
        $jasa = Jasa::whereBetween('created_at', [$awal_tanggal, $akhir_tanggal])->sum('nominal');
        if (auth()->user()->level == 4) {
            $results = DB::table('backup_produks')
                ->where('backup_produks.id_kategori', 4)
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$awal_tanggal, $akhir_tanggal])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$awal_tanggal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $awal_tanggal . "' and '" . $akhir_tanggal . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('penjualan_detail.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('penjualan_detail.subtotal');
            $pengeluaran = DB::table('pengeluaran')
                ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                ->where('users.level', 4)
                ->whereBetween('pengeluaran.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('pengeluaran.nominal');
        } elseif (auth()->user()->level == 5) {
            $results = DB::table('backup_produks')
                ->where('backup_produks.id_kategori', 5)
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$awal_tanggal, $akhir_tanggal])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$awal_tanggal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $awal_tanggal . "' and '" . $akhir_tanggal . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('penjualan_detail.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('penjualan_detail.subtotal');
            $pengeluaran = DB::table('pengeluaran')
                ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                ->where('users.level', 4)
                ->whereBetween('pengeluaran.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('pengeluaran.nominal');
        } elseif (auth()->user()->level == 1) {
            $results = DB::table('backup_produks')
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$awal_tanggal, $akhir_tanggal])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$awal_tanggal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $awal_tanggal . "' and '" . $akhir_tanggal . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')->whereBetween('created_at', [$awal_tanggal, $akhir_tanggal])->sum('subtotal');
            $pengeluaran = DB::table('pengeluaran')->whereBetween('created_at', [$awal_tanggal, $akhir_tanggal])->sum('nominal');
        } else {
            $results = DB::table('backup_produks')
                ->where([['backup_produks.id_kategori', '!=', 4], ['backup_produks.id_kategori', '!=', 5]])
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->leftJoin('pembelian_detail', 'produk.id_produk', '=', 'pembelian_detail.id_produk')
                ->whereBetween('backup_produks.created_at', [$awal_tanggal, $akhir_tanggal])
                ->select(
                    'backup_produks.id_produk',
                    'backup_produks.nama_produk',
                    'backup_produks.satuan',
                    'backup_produks.harga_beli',
                    DB::raw("(select stok_awal from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at >= '$awal_tanggal' order by created_at asc limit 1) as stok_awal"),
                    DB::raw("(select stok_akhir from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_akhir"),
                    DB::raw("(select stok_belanja from backup_produks as bp where bp.id_produk = backup_produks.id_produk and bp.created_at <= '$akhir_tanggal' order by created_at desc limit 1) as stok_belanja"),
                    DB::raw("(SELECT SUM(jumlah) FROM pembelian_detail pembelian_detail WHERE pembelian_detail.id_produk = produk.id_produk and pembelian_detail.created_at between '" . $awal_tanggal . "' and '" . $akhir_tanggal . "' ) as total_jumlah_pembelian"),
                )
                ->groupBy('backup_produks.id_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('penjualan_detail.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('penjualan_detail.subtotal');
            $pengeluaran = DB::table('pengeluaran')
                ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                ->where('users.level', 2)
                ->orWhere('users.level', 6)
                ->whereBetween('pengeluaran.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('pengeluaran.nominal');
        }

        $totalValue = 0;

        foreach ($results as $result) {
            $totalValue += (($result->harga_beli * $result->stok_awal) + ($result->stok_belanja * $result->harga_beli)) - ($result->harga_beli * $result->stok_akhir);
        }

        return view('laporan.shu', ['awal' => $awal_tanggal, 'akhir' => $akhir_tanggal, 'pengeluaran' => $pengeluaran, 'penjualan'  => $penjualan, 'hpp' => $totalValue, 'jasa' => $jasa]);
    }

    public function jurnal_penjualan($tanggal_aw, $tanggal_ak)
    {
        $tanggal_ak = Carbon::parse($tanggal_ak)->endOfDay();
        if (auth()->user()->level == 4) {
            $detail_penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(penjualan_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('penjualan_detail.subtotal');
        } elseif (auth()->user()->level == 5) {
            $detail_penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(penjualan_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('penjualan_detail.subtotal');
        } elseif (auth()->user()->level == 1) {
            $detail_penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(penjualan_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->whereBetween('created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('subtotal');
        } else {
            $detail_penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(penjualan_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('penjualan_detail.subtotal');
        }

        $value_penjualan = 0;

        foreach ($detail_penjualan as $detail) {
            $value_penjualan += $detail->total_harga;
        }

        return view('laporan.jurnal_penjualan', ['awal' => $tanggal_aw, 'akhir' => $tanggal_ak, 'penjualan'  => $penjualan, 'detail_penjualan' => $detail_penjualan, 'value_penjualan' => $value_penjualan]);
    }

    public function jurnal_pembelian($tanggal_aw, $tanggal_ak)
    {
        $tanggal_ak = Carbon::parse($tanggal_ak)->endOfDay();
        if (auth()->user()->level == 4) {
            $detail_pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(pembelian_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('pembelian_detail.subtotal');
        } elseif (auth()->user()->level == 5) {
            $detail_pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(pembelian_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('pembelian_detail.subtotal');
        } elseif (auth()->user()->level == 1) {
            $detail_pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(pembelian_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();
            $pembelian = DB::table('pembelian_detail')
                ->whereBetween('created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('subtotal');
        } else {
            $detail_pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'produk.nama_produk',
                    DB::raw('sum(pembelian_detail.subtotal) as total_harga')
                )
                ->groupBy('produk.id_produk', 'produk.nama_produk')
                ->get();

            $pembelian = DB::table('pembelian_detail')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('pembelian_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('pembelian_detail.subtotal');
        }

        $value_pembelian = 0;

        foreach ($detail_pembelian as $detail) {
            $value_pembelian += $detail->total_harga;
        }

        return view('laporan.jurnal_pembelian', ['awal' => $tanggal_aw, 'akhir' => $tanggal_ak, 'pembelian'  => $pembelian, 'detail_pembelian' => $detail_pembelian, 'value_pembelian' => $value_pembelian]);
    }
}
