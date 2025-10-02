<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use App\Models\Produk;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Pengeluaran;
use App\Models\Jasa;
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
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
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
            } else {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
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

        // dd($data);
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
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 5]])
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
            } else {
                $total_penjualan = DB::table('penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['penjualan.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->sum('penjualan.bayar');

                $total_pembelian = DB::table('pembelian')
                    ->join('users', 'pembelian.id_user', '=', 'users.id')
                    ->where([['pembelian.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
                    ->sum('pembelian.bayar');

                $total_pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['pengeluaran.created_at', 'LIKE', "%$tanggal%"], ['users.level', 2]])
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
        
        return view('laporan.pdf', ['awal' => $awal, 'akhir' => $akhir, 'data' => $data]);
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
                DB::raw('(select sum(jumlah) from pembelian_detail where pembelian_detail.id_produk = backup_produks.id_produk and pembelian_detail.created_at between "'.$awal.'" and "'.$akhir.'" group by pembelian_detail.id_produk) as stok_belanja'),
                'backup_produks.created_at',
                'produk.harga_jual'
            )
            ->groupBy('backup_produks.id_produk')
            ->get();
        // dd($results);

        $total_laba_rugi = 0;

        foreach ($results as $row) {
            $total_laba_rugi += ($row->harga_jual * $row->stok_belanja) - ($row->harga_beli * $row->stok_belanja);
        }

        return view('laporan.laba_rugi', [
            'awal' => $awal, 'akhir' => $akhir, 'results' => $results, 'total_laba_rugi' => $total_laba_rugi
            ]);
    }

    public function hpp($tanggal_awal, $tanggal_akhir)
    {
        $tanggal_akhir = Carbon::parse($tanggal_akhir)->endOfDay();
        
        $baseQuery = DB::table('backup_produks as bp')
            ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, SUM(jumlah) as total_belanja 
                FROM pembelian_detail 
                WHERE created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
                GROUP BY id_produk
            ) as pd"), 'bp.id_produk', '=', 'pd.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, 
                    FIRST_VALUE(stok_awal) OVER (PARTITION BY id_produk ORDER BY created_at ASC) as first_stok_awal
                FROM backup_produks 
                WHERE created_at >= '$tanggal_awal'
            ) as bp_awal"), 'bp.id_produk', '=', 'bp_awal.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, 
                    FIRST_VALUE(stok_akhir) OVER (PARTITION BY id_produk ORDER BY created_at DESC) as last_stok_akhir
                FROM backup_produks 
                WHERE created_at <= '$tanggal_akhir'
            ) as bp_akhir"), 'bp.id_produk', '=', 'bp_akhir.id_produk')
            ->whereBetween('bp.created_at', [$tanggal_awal, $tanggal_akhir])
            ->select(
                'bp.id_produk',
                'bp.nama_produk',
                'bp.satuan',
                'bp.harga_beli',
                DB::raw('COALESCE(bp_awal.first_stok_awal, 0) as stok_awal'),
                DB::raw('COALESCE(bp_akhir.last_stok_akhir, 0) as stok_akhir'),
                DB::raw('COALESCE(pd.total_belanja, 0) as stok_belanja')
            )
            ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli', 'bp_awal.first_stok_awal', 'bp_akhir.last_stok_akhir', 'pd.total_belanja');
        
        if (auth()->user()->level == 1) {
            $results = $baseQuery->get();
        } elseif (auth()->user()->level == 4) {
            $results = $baseQuery->where('bp.id_kategori', 4)->get();
        } elseif (auth()->user()->level == 5) {
            $results = $baseQuery->where('bp.id_kategori', 5)->get();
        } else {
            $results = $baseQuery->where([['bp.id_kategori', '!=', 4], ['bp.id_kategori', '!=', 5]])->get();
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
    
    public function hasil_usaha($tanggal_awal, $tanggal_akhir) {
       $tanggal_akhir = Carbon::parse($tanggal_akhir)->endOfDay();
        $jasa = Jasa::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])
            ->sum('nominal');
            
        $baseQuery = DB::table('backup_produks as bp')
            ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, SUM(jumlah) as total_belanja 
                FROM pembelian_detail 
                WHERE created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
                GROUP BY id_produk
            ) as pd"), 'bp.id_produk', '=', 'pd.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, 
                    FIRST_VALUE(stok_awal) OVER (PARTITION BY id_produk ORDER BY created_at ASC) as first_stok_awal
                FROM backup_produks 
                WHERE created_at >= '$tanggal_awal'
            ) as bp_awal"), 'bp.id_produk', '=', 'bp_awal.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, 
                    FIRST_VALUE(stok_akhir) OVER (PARTITION BY id_produk ORDER BY created_at DESC) as last_stok_akhir
                FROM backup_produks 
                WHERE created_at <= '$tanggal_akhir'
            ) as bp_akhir"), 'bp.id_produk', '=', 'bp_akhir.id_produk')
            ->whereBetween('bp.created_at', [$tanggal_awal, $tanggal_akhir])
            ->select(
                'bp.id_produk',
                'bp.nama_produk',
                'bp.satuan',
                'bp.harga_beli',
                DB::raw('COALESCE(bp_awal.first_stok_awal, 0) as stok_awal'),
                DB::raw('COALESCE(bp_akhir.last_stok_akhir, 0) as stok_akhir'),
                DB::raw('COALESCE(pd.total_belanja, 0) as stok_belanja')
            )
            ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli', 'bp_awal.first_stok_awal', 'bp_akhir.last_stok_akhir', 'pd.total_belanja');
            
        if (auth()->user()->level == 4) {
            $results = $baseQuery->where('bp.id_kategori', 4)->get();
            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_awal, $tanggal_akhir])
                ->sum('penjualan_detail.subtotal');
        } elseif (auth()->user()->level == 5) {
            $results = $baseQuery->where('bp.id_kategori', 5)->get();
            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('penjualan_detail.created_at', [$tanggal_awal, $tanggal_akhir])
                ->sum('penjualan_detail.subtotal');
        } elseif (auth()->user()->level == 1) {
            $results = $baseQuery->get();
            $penjualan = DB::table('penjualan_detail')->whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])->sum('subtotal');
        } else {
            $results = $baseQuery->where([['bp.id_kategori', '!=', 4], ['bp.id_kategori', '!=', 5]])->get();
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

    public function shu($awal_tanggal, $akhir_tanggal) {
        $akhir_tanggal = Carbon::parse($akhir_tanggal)->endOfDay();
        $jasa = Jasa::whereBetween('created_at', [$awal_tanggal, $akhir_tanggal])->sum('nominal');
        
        $baseQuery = DB::table('backup_produks as bp')
            ->join('produk as p', 'bp.id_produk', '=', 'p.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, SUM(jumlah) as total_belanja 
                FROM pembelian_detail 
                WHERE created_at BETWEEN '$awal_tanggal' AND '$akhir_tanggal' 
                GROUP BY id_produk
            ) as pd"), 'bp.id_produk', '=', 'pd.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, 
                    FIRST_VALUE(stok_awal) OVER (PARTITION BY id_produk ORDER BY created_at ASC) as first_stok_awal
                FROM backup_produks 
                WHERE created_at >= '$awal_tanggal'
            ) as bp_awal"), 'bp.id_produk', '=', 'bp_awal.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, 
                    FIRST_VALUE(stok_akhir) OVER (PARTITION BY id_produk ORDER BY created_at DESC) as last_stok_akhir
                FROM backup_produks 
                WHERE created_at <= '$akhir_tanggal'
            ) as bp_akhir"), 'bp.id_produk', '=', 'bp_akhir.id_produk')
            ->whereBetween('bp.created_at', [$awal_tanggal, $akhir_tanggal])
            ->select(
                'bp.id_produk',
                'bp.nama_produk',
                'bp.satuan',
                'bp.harga_beli',
                DB::raw('COALESCE(bp_awal.first_stok_awal, 0) as stok_awal'),
                DB::raw('COALESCE(bp_akhir.last_stok_akhir, 0) as stok_akhir'),
                DB::raw('COALESCE(pd.total_belanja, 0) as stok_belanja')
            )
            ->groupBy('bp.id_produk', 'bp.nama_produk', 'bp.satuan', 'bp.harga_beli', 'bp_awal.first_stok_awal', 'bp_akhir.last_stok_akhir', 'pd.total_belanja');
        
        if (auth()->user()->level == 4) {
            $results = $baseQuery->where('bp.id_kategori', 4)->get();
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
            $results = $baseQuery->where('bp.id_kategori', 5)->get();
            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->whereBetween('penjualan_detail.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('penjualan_detail.subtotal');
            $pengeluaran = DB::table('pengeluaran')
                ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                ->where(function ($q) {
                    $q->where('users.level', 5)
                      ->orWhere('users.level', 8);
                })
                ->whereBetween('pengeluaran.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('pengeluaran.nominal');
        } elseif (auth()->user()->level == 1) {
            $results = $baseQuery->get();
            $penjualan = DB::table('penjualan_detail')
                ->whereBetween('created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('subtotal');
            $pengeluaran = DB::table('pengeluaran')
                ->whereBetween('created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('nominal');
        } else {
            $results = $baseQuery->where([['bp.id_kategori', '!=', 4], ['bp.id_kategori', '!=', 5]])->get();
            $penjualan = DB::table('penjualan_detail')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->whereBetween('penjualan_detail.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('penjualan_detail.subtotal');
            $pengeluaran = DB::table('pengeluaran')
                ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                ->where(function ($q) {
                    $q->where('users.level', 2)
                      ->orWhere('users.level', 6);
                })
                ->whereBetween('pengeluaran.created_at', [$awal_tanggal, $akhir_tanggal])
                ->sum('pengeluaran.nominal');
        }

        $totalValue = 0;

        foreach ($results as $result) {
            $totalValue += (($result->harga_beli * $result->stok_awal) + ($result->stok_belanja * $result->harga_beli)) - ($result->harga_beli * $result->stok_akhir);
        }

        return view('laporan.shu', ['awal' => $awal_tanggal, 'akhir' => $akhir_tanggal, 'pengeluaran' => $pengeluaran,'penjualan'  => $penjualan, 'hpp' => $totalValue, 'jasa' => $jasa]);
    }
    
    public function jurnal_penjualan($tanggal_aw, $tanggal_ak)
    {
        $tanggal_ak = Carbon::parse($tanggal_ak)->endOfDay();
        $value_penjualan = 0;
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
            foreach ($detail_penjualan as $detail) 
            {
                $value_penjualan += $detail->total_harga;
            }
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
            foreach ($detail_penjualan as $detail) 
            {
                $value_penjualan += $detail->total_harga;
            }
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
            foreach ($detail_penjualan as $detail) 
            {
                $value_penjualan += $detail->total_harga;
            }
        } else {
           $detail_penjualan = DB::table('penjualan_detail')
                ->join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                ->join('users', 'penjualan.id_user', '=', 'users.id')
                ->join('produk', 'penjualan_detail.id_produk', '=', 'produk.id_produk')
                ->where(function ($q) {
                    $q->where('users.level', 2)
                      ->orWhere('users.level', 6);
                })
                ->whereBetween('penjualan_detail.created_at', [$tanggal_aw, $tanggal_ak])
                ->select(
                    'penjualan_detail.*',
                    'penjualan.*',
                    'produk.nama_produk'
                )
                ->orderBy('penjualan_detail.created_at', 'asc')
                ->get();
            
            $penjualan = DB::table('penjualan')
                ->join('users', 'penjualan.id_user', '=', 'users.id')
                ->where(function ($q) {
                    $q->where('users.level', 2)
                      ->orWhere('users.level', 6);
                })
                ->whereBetween('penjualan.created_at', [$tanggal_aw, $tanggal_ak])
                ->sum('penjualan.bayar');
            foreach ($detail_penjualan as $detail) 
            {
               $value_penjualan += $detail->harga_jual * $detail->jumlah;
            }
            
            $penjualan = $value_penjualan;

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

        foreach ($detail_pembelian as $detail) 
        {
            $value_pembelian += $detail->total_harga;
        }

        return view('laporan.jurnal_pembelian', ['awal' => $tanggal_aw, 'akhir' => $tanggal_ak, 'pembelian'  => $pembelian, 'detail_pembelian' => $detail_pembelian, 'value_pembelian' => $value_pembelian]);
    }
}
