<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\Auth;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class PenjualanController extends Controller
{
    public function index()
    {
        return view('penjualan.index');
    }

    public function data()
    {
        if (auth()->user()->level == 4) {
            $penjualan = Penjualan::with('member')
                ->join('users', 'id_user', '=', 'users.id')
                ->where([['users.level', 4], ['total_item', '!=', 0]])
                ->select('penjualan.*')
                ->orderBy('id_penjualan', 'desc')
                ->get();
        } elseif (auth()->user()->level == 5) {
            $penjualan = Penjualan::with('member')
                ->join('users', 'id_user', '=', 'users.id')
                ->where([['users.level', 5], ['total_item', '!=', 0]])
                ->orWhere([['users.level', 8], ['total_item', '!=', 0]])
                ->select('penjualan.*')
                ->orderBy('id_penjualan', 'desc')
                ->get();
        } elseif (auth()->user()->level == 1) {
            $penjualan = Penjualan::with('member')
                ->where('total_item', '!=', 0)
                ->select('penjualan.*')
                ->orderBy('id_penjualan', 'desc')
                ->get();
        } elseif (auth()->user()->level == 2) {
            $penjualan = Penjualan::with('member')
                ->join('users', 'id_user', '=', 'users.id')
                ->where([['users.level', 2], ['total_item', '!=', 0]])
                ->orWhere([['users.level', 6], ['total_item', '!=', 0]])
                ->select('penjualan.*')
                ->orderBy('id_penjualan', 'desc')
                ->get();
        } elseif (auth()->user()->level == 8) {
            $penjualan = Penjualan::with('member')
                ->join('users', 'id_user', '=', 'users.id')
                ->where([['users.level', 8], ['total_item', '!=', 0]])
                ->select('penjualan.*')
                ->orderBy('id_penjualan', 'desc')
                ->get();
        } else {
            $penjualan = Penjualan::with('member')
                ->where([['id_user', auth()->user()->id], ['total_item', '!=', 0]])
                ->select('penjualan.*')
                ->orderBy('id_penjualan', 'desc')
                ->get();
        }

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. ' . format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. ' . format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('kode_member', function ($penjualan) {
                $member = $penjualan->member->kode_member ?? '';
                return '<span class="label label-success">' . $member . '</spa>';
            })
            ->addColumn('pembayaran', function ($penjualan) {
                if ($penjualan->pembayaran == 'kredit') {
                    return '<span class="label label-warning" style="text-transform: capitalize">' . $penjualan->pembayaran . '</span>';
                } else {
                    return '<span class="label label-primary" style="text-transform: capitalize">' . $penjualan->pembayaran . '</span>';
                }
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`' . route('penjualan.show', $penjualan->id_penjualan) . '`)" class="btn btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button type="button" onclick="openNotaModal(' . $penjualan->id_penjualan . ')" class="btn btn-warning btn-flat"><i class="fa fa-print"></i></button>
                    <button onclick="deleteData(`' . route('penjualan.destroy', $penjualan->id_penjualan) . '`)" class="btn btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi', 'kode_member', 'pembayaran'])
            ->make(true);
    }

    public function create()
    {
        // Check if there's an active transaction
        if ($old_id = session('id_penjualan')) {
            $old_penjualan = Penjualan::find($old_id);
            if ($old_penjualan) {
                // If transaction is completed, clear session and allow new transaction
                if ($old_penjualan->total_item > 0) {
                    session()->forget('id_penjualan');
                } else {
                    // If transaction is incomplete, clean it up
                    PenjualanDetail::where('id_penjualan', $old_id)->delete();
                    $old_penjualan->delete();
                }
            }
        }
        
        $penjualan = new Penjualan();
        $penjualan->id_member = null;
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->pembayaran = 0;
        $penjualan->cicilan = 0;
        $penjualan->id_user = auth()->id();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return redirect()->route('transaksi.index');
    }

    public function store(Request $request)
    {
        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        
        // Validate stock availability before saving transaction
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if (!$produk || $produk->stok < $item->jumlah) {
                return redirect()->back()->with('error', 'Stok produk ' . ($produk->nama_produk ?? 'tidak ditemukan') . ' tidak mencukupi');
            }
        }
        
        // Update transaction details
        $penjualan->id_member = $request->id_member;
        $penjualan->total_item = $request->total_item;
        $penjualan->total_harga = $request->total;
        $penjualan->diskon = $request->diskon;
        $penjualan->bayar = $request->bayar;
        $penjualan->diterima = $request->diterima;
        $penjualan->pembayaran = $request->pembayaran;
        $penjualan->cicilan = $request->cicilan;
        $penjualan->created_at = $request->created_at;
        $penjualan->updated_at = $request->created_at;
        $penjualan->update();

        // Only reduce stock after transaction is successfully saved
        foreach ($detail as $item) {
            $item->diskon = $request->diskon;
            $item->created_at = $request->created_at;
            $item->updated_at = $request->created_at;
            $item->update();

            $produk = Produk::find($item->id_produk);
            $produk->stok -= $item->jumlah;
            $produk->updated_at = $request->created_at;
            $request->request->remove('updated_at');

            $produk->timestamps = false;
            $produk->save();
        }
        
        // Clear session after successful transaction
        session()->forget('id_penjualan');

        return redirect()->route('transaksi.selesai');
    }
    
    public function cancel($id)
    {
        $penjualan = Penjualan::find($id);
        if (!$penjualan) {
            return response()->json('Transaksi tidak ditemukan', 404);
        }
        
        // Only allow cancellation of unsaved transactions (total_item = 0)
        if ($penjualan->total_item > 0) {
            return response()->json('Transaksi sudah disimpan, tidak dapat dibatalkan', 400);
        }
        
        // Delete all transaction details (no stock reversal needed as stock was never reduced)
        PenjualanDetail::where('id_penjualan', $id)->delete();
        
        // Delete the transaction
        $penjualan->delete();
        
        // Clear session
        session()->forget('id_penjualan');
        
        return response()->json('Transaksi berhasil dibatalkan', 200);
    }
    
    public static function cleanupIncompleteTransactions()
    {
        // Clean up incomplete transactions older than 1 hour
        $cutoff = now()->subHour();
        
        $incomplete = Penjualan::where('total_item', 0)
            ->where('created_at', '<', $cutoff)
            ->get();
            
        foreach ($incomplete as $penjualan) {
            // Delete transaction details (no stock reversal needed as stock was never reduced)
            PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->delete();
            $penjualan->delete();
        }
        
        return count($incomplete);
    }

    public function show($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">' . $detail->produk->kode_produk . '</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_jual', function ($detail) {
                return 'Rp. ' . format_uang($detail->harga_jual);
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
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        
        // Only reverse stock for completed transactions (total_item > 0)
        if ($penjualan->total_item > 0) {
            foreach ($detail as $item) {
                $produk = Produk::find($item->id_produk);
                if ($produk) {
                    $produk->stok += $item->jumlah;
                    $produk->update();
                }
                $item->delete();
            }
        } else {
            // For unsaved transactions, just delete details without stock reversal
            foreach ($detail as $item) {
                $item->delete();
            }
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function pdf($pembayaran, $awal, $akhir)
    {
        $akhir = Carbon::parse($akhir)->endOfDay();
        $no = 1;
        $total = 0;
        if ($pembayaran == 'tunai') {
            $title = "TUNAI";

            if (auth()->user()->level == 4) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['penjualan.pembayaran', 0]])
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['penjualan.pembayaran', 0]])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                    
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } elseif (auth()->user()->level == 5) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 5], ['penjualan.pembayaran', 0]])
                          ->orWhere([['users.level', 8], ['penjualan.pembayaran', 0]]);
                    })
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 5], ['penjualan.pembayaran', 0]])
                          ->orWhere([['users.level', 8], ['penjualan.pembayaran', 0]]);
                    })
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) { $total += $item->bayar; }
            } elseif (auth()->user()->level == 1) {
                $data_penjualan = Penjualan::where('penjualan.pembayaran', 0)
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc') 
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->where('penjualan.pembayaran', 0)
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                }  
            } elseif (auth()->user()->level == 2) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 2], ['penjualan.pembayaran', 0]])
                          ->orWhere([['users.level', 6], ['penjualan.pembayaran', 0]]);
                    })
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 2], ['penjualan.pembayaran', 0]])
                          ->orWhere([['users.level', 6], ['penjualan.pembayaran', 0]]);
                    })
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } elseif (auth()->user()->level == 6) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 6], ['penjualan.pembayaran', 0]])
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 6], ['penjualan.pembayaran', 0]])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } else {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 8], ['penjualan.pembayaran', 0]])
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 8], ['penjualan.pembayaran', 0]])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            }
        } elseif ($pembayaran == 'kredit') {
            $title = "KREDIT";
            if (auth()->user()->level == 4) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['penjualan.pembayaran', 1]])
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['penjualan.pembayaran', 1]])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } elseif (auth()->user()->level == 5) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 5], ['penjualan.pembayaran', 1]])
                          ->orWhere([['users.level', 8], ['penjualan.pembayaran', 1]]);
                    })
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 5], ['penjualan.pembayaran', 1]])
                          ->orWhere([['users.level', 8], ['penjualan.pembayaran', 1]]);
                    })
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) { $total += $item->bayar; } 
            } elseif (auth()->user()->level == 1) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('penjualan.pembayaran', 1)
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc') 
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->where('penjualan.pembayaran', 1)
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } elseif (auth()->user()->level == 2) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 2], ['penjualan.pembayaran', 1]])
                          ->orWhere([['users.level', 6], ['penjualan.pembayaran', 1]]);
                    })
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where([['users.level', 2], ['penjualan.pembayaran', 1]])
                          ->orWhere([['users.level', 6], ['penjualan.pembayaran', 1]]);
                    })
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } elseif (auth()->user()->level == 6) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 6], ['penjualan.pembayaran', 1]])
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 6], ['penjualan.pembayaran', 1]])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } else {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 8], ['penjualan.pembayaran', 1]])
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where([['users.level', 8], ['penjualan.pembayaran', 1]])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            }
        } elseif ($pembayaran == 'tunai-dan-kredit') {
            $title = "TUNAI dan KREDIT";
            if (auth()->user()->level == 4) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('users.level', 4)
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('users.level', 4)
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                }                    
            } elseif (auth()->user()->level == 5) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where('users.level', 5)
                          ->orWhere('users.level', 8);
                    })
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where('users.level', 5)
                          ->orWhere('users.level', 8);
                    })
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();

                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                }
                     
            } elseif (auth()->user()->level == 1) {
                $data_penjualan = Penjualan::whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc') 
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                }           
            } elseif (auth()->user()->level == 2) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where('users.level', 2)
                          ->orWhere('users.level', 6);
                    })
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where(function ($q) {
                        $q->where('users.level', 2)
                          ->orWhere('users.level', 6);
                    })
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($penjualan as $item) {
                    $total += $item->subtotal;
                } 
            } elseif (auth()->user()->level == 6) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('users.level', 6)
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('users.level', 6)
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            } elseif  (auth()->user()->level == 8) {
                $data_penjualan = Penjualan::join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('users.level', 8)
                    ->whereBetween('penjualan.created_at', [$awal, $akhir])
                    ->select('penjualan.*')
                    ->orderBy('penjualan.created_at', 'asc')
                    ->get();
                $penjualan = PenjualanDetail::join('penjualan', 'penjualan_detail.id_penjualan', '=', 'penjualan.id_penjualan')
                    ->join('users', 'penjualan.id_user', '=', 'users.id')
                    ->where('users.level', 8)
                    ->select('penjualan_detail.*', 'penjualan.*')
                    ->whereBetween('penjualan_detail.created_at', [$awal, $akhir])
                    ->orderBy('penjualan_detail.created_at', 'asc')
                    ->get();
                foreach ($data_penjualan as $item) {
                    $total += $item->bayar;
                } 
            }
        }

        return view('penjualan.pdf', ['awal' => $awal, 'akhir' => $akhir, 'penjualan' => $penjualan, 'total' => $total, 'title' => $title, 'data_penjualan' => $data_penjualan, 'no' => $no]);
    }

    public function selesai()
    {
        $setting = Setting::first();

        return view('penjualan.selesai', compact('setting'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        $waktu = Carbon::parse(date(now()))->translatedFormat('d F Y H:i:s');
        if (!$penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail', 'waktu'));
    }

    public function notaBesar()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (!$penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        return view('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
    }
    
    public function nota($jenis, $id)
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find($id);
        $waktu = Carbon::parse(date(now()))->translatedFormat('d F Y H:i:s');
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $id)
            ->get();

        if ($jenis == 'tunai') {
            return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail', 'waktu'));
        } else {
            return view('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        }
    }
}
