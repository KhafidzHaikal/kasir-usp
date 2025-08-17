<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use App\Models\PembelianDetail;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class PembelianController extends Controller
{
    public function index()
    {
        $supplier = Supplier::orderBy('nama')->get();

        return view('pembelian.index', compact('supplier'));
    }

    public function data()
    {
        // if (auth()->user()->level == 4) {
        //     $pembelian = Pembelian::join('users', 'id_user', '=', 'users.id')
        //         ->where('users.level', 4)
        //         ->orderBy('id_pembelian', 'desc')
        //         ->get();
        // } elseif (auth()->user()->level == 5) {
        //     $pembelian = Pembelian::join('users', 'id_user', '=', 'users.id')
        //         ->where('users.level', 5)
        //         ->orderBy('id_pembelian', 'desc')
        //         ->get();
        // } elseif (auth()->user()->level == 2) {
        //     $pembelian = Pembelian::join('users', 'id_user', '=', 'users.id')
        //         ->where('users.level', 2)
        //         ->orderBy('id_pembelian', 'desc')
        //         ->get();
        // } else {
        //     $pembelian = Pembelian::orderBy('id_pembelian', 'desc')->get();
        // }

        if (auth()->user()->level == 4) {
            $pembelian = Pembelian::join('pembelian_detail', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->select('pembelian.*')->distinct()
                ->orderBy('pembelian.id_pembelian', 'desc')
                ->get();
        
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $pembelian = Pembelian::join('pembelian_detail', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->select('pembelian.*')->distinct()
                ->orderBy('pembelian.id_pembelian', 'desc')
                ->get();
        
        } elseif (auth()->user()->level == 1) {
            $pembelian = Pembelian::join('pembelian_detail', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->select('pembelian.*')->distinct()
                ->orderBy('pembelian.id_pembelian', 'desc')
                ->get();
        
        } else {
            $pembelian = Pembelian::join('pembelian_detail', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
                ->join('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->whereNotIn('produk.id_kategori', [4, 5])
                ->select('pembelian.*')->distinct()
                ->orderBy('pembelian.id_pembelian', 'desc')
                ->get();
        }

        return datatables()
            ->of($pembelian)
            ->addIndexColumn()
            ->addColumn('total_item', function ($pembelian) {
                return format_uang($pembelian->total_item);
            })
            ->addColumn('total_harga', function ($pembelian) {
                return 'Rp. ' . format_uang($pembelian->total_harga);
            })
            ->addColumn('bayar', function ($pembelian) {
                return 'Rp. ' . format_uang($pembelian->bayar);
            })
            ->addColumn('tanggal', function ($pembelian) {
                return tanggal_indonesia($pembelian->created_at, false);
            })
            ->addColumn('supplier', function ($pembelian) {
                return $pembelian->supplier->nama;
            })
            ->editColumn('diskon', function ($pembelian) {
                return $pembelian->diskon . '%';
            })
            ->addColumn('aksi', function ($pembelian) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`' . route('pembelian.show', $pembelian->id_pembelian) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="deleteData(`' . route('pembelian.destroy', $pembelian->id_pembelian) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create($id)
    {
        $pembelian = new Pembelian();
        $pembelian->id_supplier = $id;
        $pembelian->total_item  = 0;
        $pembelian->total_harga = 0;
        $pembelian->diskon      = 0;
        $pembelian->bayar       = 0;
        $pembelian->id_user = auth()->id();
        $pembelian->save();

        session(['id_pembelian' => $pembelian->id_pembelian]);
        session(['id_supplier' => $pembelian->id_supplier]);

        return redirect()->route('pembelian_detail.index');
    }

    public function store(Request $request)
    {
        $pembelian = Pembelian::findOrFail($request->id_pembelian);
        $pembelian->total_item = $request->total_item;
        $pembelian->total_harga = $request->total;
        $pembelian->diskon = $request->diskon;
        $pembelian->bayar = $request->bayar;
        $pembelian->created_at = $request->created_at;
        $pembelian->update();

        $detail = PembelianDetail::where('id_pembelian', $pembelian->id_pembelian)->get();
        foreach ($detail as $item) {
            $item->created_at = $request->created_at;
            $item->save();
            $produk = Produk::find($item->id_produk);
            $produk->stok += $item->jumlah;
            $produk->update();
        }

        return redirect()->route('pembelian.selesai');
        // return redirect('pembelian.selesai')->with('success', 'Pembelian Berhasil');
    }

    public function show($id)
    {
        $detail = PembelianDetail::with('produk')->where('id_pembelian', $id)->get();
        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">' . $detail->produk->kode_produk . '</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_beli', function ($detail) {
                return 'Rp. ' . format_uang($detail->harga_beli);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('subtotal', function ($detail) {
                return 'Rp. ' . format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }

    public function destroy($id)
    {
        $pembelian = Pembelian::find($id);
        $detail    = PembelianDetail::where('id_pembelian', $pembelian->id_pembelian)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok -= $item->jumlah;
                $produk->update();
            }
            $item->delete();
        }

        $pembelian->delete();

        return response(null, 204);
    }

    public function selesai()
    {
        return view('pembelian.selesai');
    }

    public function kwitansi()
    {
        $pembelian = Pembelian::find(session('id_pembelian'));
        if (!$pembelian) {
            abort(404);
        }
        $detail = PembelianDetail::with('produk')
            ->where('id_pembelian', session('id_pembelian'))
            ->get();

        $pdf = PDF::loadView('pembelian.kwitansi', compact('pembelian', 'detail'));
        return $pdf->inline('Transaksi-' . date('Y-m-d-his') . '.pdf');
    }

    public function pdf($awal, $akhir)
    {
        $akhir = Carbon::parse($akhir)->endOfDay();
        if (auth()->user()->level == 4) {
            $pembelian = PembelianDetail::leftJoin('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 4)
                ->join('pembelian', 'pembelian_detail.id_pembelian', '=', 'pembelian.id_pembelian')
                ->select('pembelian_detail.*', 'pembelian.id_supplier', 'produk.nama_produk', 'produk.harga_beli')
                ->whereBetween('pembelian_detail.created_at', [$awal, $akhir])
                ->orderBy('pembelian_detail.created_at', 'asc')
                ->get();
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $pembelian = PembelianDetail::leftJoin('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where('produk.id_kategori', 5)
                ->join('pembelian', 'pembelian_detail.id_pembelian', '=', 'pembelian.id_pembelian')
                ->select('pembelian_detail.*', 'pembelian.id_supplier', 'produk.nama_produk', 'produk.harga_beli')
                ->whereBetween('pembelian_detail.created_at', [$awal, $akhir])
                ->orderBy('pembelian_detail.created_at', 'asc')
                ->get();
        } elseif (auth()->user()->level == 1) {
            $pembelian = PembelianDetail::leftJoin('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->join('pembelian', 'pembelian_detail.id_pembelian', '=', 'pembelian.id_pembelian')
                ->select('pembelian_detail.*', 'pembelian.id_supplier', 'produk.nama_produk', 'produk.harga_beli')
                ->whereBetween('pembelian_detail.created_at', [$awal, $akhir])
                ->orderBy('pembelian_detail.created_at', 'asc')
                ->get();
        } else {
            $pembelian = PembelianDetail::leftJoin('produk', 'pembelian_detail.id_produk', '=', 'produk.id_produk')
                ->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5]])
                ->join('pembelian', 'pembelian_detail.id_pembelian', '=', 'pembelian.id_pembelian')
                ->select('pembelian_detail.*', 'pembelian.id_supplier', 'produk.nama_produk', 'produk.harga_beli')
                ->whereBetween('pembelian_detail.created_at', [$awal, $akhir])
                ->orderBy('pembelian_detail.created_at', 'asc')
                ->get();
        }


        $jumlah = 0;
        foreach ($pembelian as $item) {
            $jumlah += $item->subtotal;
        }

        return view('pembelian.pdf', [
            'awal' => $awal, 'akhir' => $akhir, 'pembelian' => $pembelian, 'jumlah' => $jumlah
        ]);
        // $pdf  = PDF::loadView('pembelian.pdf', compact('awal', 'akhir', 'pembelian', 'jumlah'));
        // return $pdf->inline('Laporan-Pembelian-' . date('Y-m-d-his') . '.pdf');
    }
}
