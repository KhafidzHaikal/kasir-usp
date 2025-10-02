<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\BackupProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as Barpdf;
use RealRashid\SweetAlert\Facades\Alert;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (auth()->user()->level == 4) {
            $kategori = Kategori::where('id_kategori', 4)->pluck('nama_kategori', 'id_kategori');
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $kategori = Kategori::where('id_kategori', 5)->pluck('nama_kategori', 'id_kategori');
        } elseif (auth()->user()->level == 1) {
            $kategori = Kategori::all()->pluck('nama_kategori', 'id_kategori');
        } else {
            $kategori = Kategori::where([['id_kategori', '!=', 5], ['id_kategori', '!=', 4], ['id_kategori', '!=', 13]])->pluck('nama_kategori', 'id_kategori');
        }
        $buttonClass = '';
        $buttonAttributes = '';

        // Data disabled jika sudah backup tiap bulan
        $now = Carbon::now();
        if (auth()->user()->level == 4) {
            $backups = DB::table('backup_produks')
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->where('backup_produks.id_kategori', 4)
                ->select('backup_produks.created_at')
                ->get();
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $backups = DB::table('backup_produks')
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->where('backup_produks.id_kategori', 5)
                ->select('backup_produks.created_at')
                ->get();
        } elseif (auth()->user()->level == 1) {
            $backups = DB::table('backup_produks')
                ->select('created_at')
                ->get();
        } else {
            $backups = DB::table('backup_produks')
                ->join('produk', 'backup_produks.id_produk', '=', 'produk.id_produk')
                ->where([['backup_produks.id_kategori', '!=', 4], ['backup_produks.id_kategori', '!=', 5], ['backup_produks.id_kategori', '!=', 13]])
                ->select('backup_produks.created_at')
                ->get();
        }

        foreach ($backups as $backup) {
            $backupDate = Carbon::parse($backup->created_at);

            if ($backupDate->month == $now->month) {
                $buttonClass = 'disabled';
                break;
            }
        }

        // $buttonAttributes = $buttonClass ? " disabled" : "";

        if (auth()->user()->level == 4) {
            $stok = Produk::where('id_kategori', 4)
                ->whereBetween('stok', [1, 2])
                ->whereNotNull('stok')
                ->get();

            $stok_kosong = Produk::where([['stok', '<=', 0], ['id_kategori', 4]])
                ->whereNotNull('stok')
                ->get();
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $stok = Produk::where('id_kategori', 5)
                ->whereBetween('stok', [1, 2])
                ->whereNotNull('stok')
                ->get();
            $stok_kosong = Produk::where([['stok', '<=', 0], ['id_kategori', 5]])
                ->whereNotNull('stok')
                ->get();
        } elseif (auth()->user()->level == 1) {
            $stok = Produk::whereBetween('stok', [1, 2])
                ->whereNotNull('stok')
                ->get();

            $stok_kosong = Produk::where('stok', '<=', 0)
                ->whereNotNull('stok')
                ->get();
        } else {
            $stok = Produk::where([['id_kategori', '!=', 4], ['id_kategori', '!=', 5], ['id_kategori', '!=', 13]])
                ->whereBetween('stok', [1, 2])
                ->whereNotNull('stok')
                ->get();
            $stok_kosong = Produk::where([['stok', '<=', 0], [[['id_kategori', '!=', 4], ['id_kategori', '!=', 5]]]])
                ->whereNotNull('stok')
                ->get();
        }

        if ($stok->isNotEmpty()) {
            Alert::warning('Stok Produk', "Halo, " . $stok->count() . " Produk stok produk kurang dari 2 stok. Harap pastikan untuk mengelola stok produk Anda.");
        }

        if ($stok_kosong->isNotEmpty()) {
            Alert::error('Stok Produk', "Halo, " . $stok_kosong->count() . " Produk stok produk habis. Harap pastikan untuk mengelola stok produk Anda.");
        }

        return view('produk.index', compact('kategori', 'buttonAttributes', 'buttonClass'));
    }

    public function data(Request $request)
    {
        if (auth()->user()->level == 4) {
            $produk = DB::table('produk')->where('id_kategori', 4)->latest();
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $produk = DB::table('produk')->where('id_kategori', 5)->latest();
        } elseif (auth()->user()->level == 1) {
            $produk = Produk::latest();
        } else {
            $produk = Produk::where([['id_kategori', '!=', 4], ['id_kategori', '!=', 5], ['id_kategori', '!=', 13]])->latest();
        }

        return datatables()
            ->of($produk)
            ->addIndexColumn()
            ->addColumn('select_all', function ($data) {
                return '<input type="checkbox" name="id_produk[]" value="' . $data->id_produk . '">';
            })
            ->addColumn('kode_produk', function ($data) {
                return '<span class="label label-success">' . $data->kode_produk . '</span>';
            })
            ->addColumn('tanggal_expire', function ($data) {
                $expired_products = Produk::where('tanggal_expire', '<=', Carbon::now()->addDays(7))
                    ->whereNotNull('tanggal_expire')
                    ->pluck('tanggal_expire')
                    ->toArray();

                if (in_array($data->tanggal_expire, $expired_products)) {
                    return '<span class="label label-danger">' . $data->tanggal_expire . '</span>';
                } else {
                    return '<span class="label label-success">' . $data->tanggal_expire . '</span>';
                }
            })
            ->addColumn('harga_beli', function ($data) {
                return format_uang($data->harga_beli);
            })
            ->addColumn('harga_jual', function ($data) {
                return format_uang($data->harga_jual);
            })
            ->addColumn('stok', function ($data) {
                return format_uang($data->stok);
            })
            ->addColumn('aksi', function ($data) {
                return '
            <div class="btn-group">
                <button type="button" onclick="editForm(`' . route('produk.update', $data->id_produk) . '`)" class="btn btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                <button type="button" onclick="deleteData(`' . route('produk.destroy', $data->id_produk) . '`)" class="btn btn-danger btn-flat"><i class="fa fa-trash"></i></button>
            </div>
        ';
            })
            ->rawColumns(['aksi', 'kode_produk', 'tanggal_expire', 'select_all'])
            ->make(true);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $produk = Produk::latest()->first() ?? new Produk();
        $request['kode_produk'] = $request->kode_produk;
        $request['stok_lama'] = $request->stok;

        $produk = Produk::create($request->all());

        return response(null, 204);
    }


    public function show($id)
    {
        $produk = Produk::find($id);

        return response()->json($produk);
    }


    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        $totalJumlah = DB::table('produk')->join('penjualan_detail', 'produk.id_produk', '=', 'penjualan_detail.id_produk')->where('produk.id_produk', '=', $id)->select(DB::raw("SUM(penjualan_detail.jumlah) as total_jumlah"))
            ->value('total_jumlah');
        // $request['stok_lama'] = $request['stok'];
        $request['stok'] = $request['stok'];
        // $request['stok'] = ($request['stok_lama'] - $totalJumlah);
        // $request->request->remove('updated_at');

        // $produk->timestamps = false;
        $produk->update($request->all());

        return response(null, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $produk = Produk::find($id);
        $produk->delete();

        return response(null, 204);
    }

    public function deleteSelected(Request $request)
    {
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $produk->delete();
        }

        return response(null, 204);
    }

    public function cetakBarcode(Request $request)
    {
        $dataproduk = array();
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $dataproduk[] = $produk;
        }

        $no  = 1;
        $pdf = Barpdf::loadView('produk.barcode', compact('dataproduk', 'no'));
        $pdf->setPaper('a4', 'potrait');
        return $pdf->stream('produk.pdf');
    }

    public function pdf($awal, $akhir, Request $request)
    {
        $akhir = Carbon::parse($akhir)->endOfDay();

        $baseQuery = Produk::leftJoin(DB::raw("(
                SELECT id_produk, SUM(jumlah) as total_pembelian
                FROM pembelian_detail 
                WHERE created_at BETWEEN '$awal' AND '$akhir'
                GROUP BY id_produk
            ) as pd"), 'produk.id_produk', '=', 'pd.id_produk')
            ->leftJoin(DB::raw("(
                SELECT id_produk, SUM(jumlah) as total_penjualan
                FROM penjualan_detail 
                WHERE created_at BETWEEN '$awal' AND '$akhir'
                GROUP BY id_produk
            ) as pjd"), 'produk.id_produk', '=', 'pjd.id_produk')
            ->leftJoin(DB::raw("(
                SELECT bp1.*
                FROM backup_produks bp1
                INNER JOIN (
                    SELECT id_produk, MIN(created_at) as min_created_at
                    FROM backup_produks
                    WHERE created_at BETWEEN '$awal' AND '$akhir'
                    GROUP BY id_produk
                ) bp2 ON bp1.id_produk = bp2.id_produk AND bp1.created_at = bp2.min_created_at
            ) as backup_produks"), 'produk.id_produk', '=', 'backup_produks.id_produk')
            ->where(function ($query) use ($awal, $akhir) {
                $query->whereBetween('produk.created_at', [$awal, $akhir])
                    ->orWhereBetween('produk.updated_at', [$awal, $akhir])
                    ->orWhereBetween('backup_produks.created_at', [$awal, $akhir])
                    ->orWhereBetween('backup_produks.updated_at', [$awal, $akhir]);
            })
            ->select(
                'produk.id_produk',
                'produk.id_kategori',
                'produk.nama_produk',
                'backup_produks.stok_awal as backup_stok_awal',
                'produk.kode_produk',
                'produk.created_at',
                'produk.stok',
                'produk.stok_lama',
                'produk.harga_beli',
                DB::raw('COALESCE(pd.total_pembelian, 0) as total_jumlah_pembelian'),
                DB::raw('COALESCE(pjd.total_penjualan, 0) as total_jumlah')
            );

        if (auth()->user()->level == 4) {
            $produk = $baseQuery->where('produk.id_kategori', 4)->get();
        } elseif (auth()->user()->level == 5 || auth()->user()->level == 8) {
            $produk = $baseQuery->where('produk.id_kategori', 5)->get();
        } elseif (auth()->user()->level == 1) {
            $produk = $baseQuery->get();
        } else {
            $produk = $baseQuery->where([['produk.id_kategori', '!=', 4], ['produk.id_kategori', '!=', 5], ['produk.id_kategori', '!=', 13]])->get();
        }

        $total_penjualan = 0;

        // dd($produk);

        // foreach ($produk as $item) {
        //     $total_penjualan += $item->harga_beli * ($item->stok_lama + $item->total_jumlah_pembelian - $item->total_jumlah);
        // }

        foreach ($produk as $item) {
            $pembelian = $item->total_jumlah_pembelian ?? 0;
            $penjualan = $item->total_jumlah ?? 0;
            $total_penjualan += $item->harga_beli * ($item->backup_stok_awal + $pembelian - $penjualan);
        }

        // dd($total_penjualan);

        // dd($produk);

        return view('produk.pdf', ['awal' => $awal, 'akhir' => $akhir, 'produk'  => $produk, 'total_penjualan' => $total_penjualan]);
    }
}
