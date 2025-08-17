<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PengeluaranController extends Controller
{
    public function index()
    {
        return view('pengeluaran.index');
    }

    public function data()
    {
        if (auth()->user()->level == 4) {
            $pengeluaran = Pengeluaran::join('users', 'id_user', '=', 'users.id')
                ->where('users.level', 4)
                ->orWhere(function ($query) {
                    $query->where('users.level', 1)
                        ->whereIn('pengeluaran.deskripsi', ['Jasa Service', 'Jasa Cuci']);
                })
                ->select('pengeluaran.*')
                ->orderBy('id_pengeluaran', 'desc')
                ->get();
        } elseif (auth()->user()->level == 5) {
            $pengeluaran = Pengeluaran::join('users', 'id_user', '=', 'users.id')
                ->where('users.level', 5)
                ->orWhere('users.level', 8)
                ->select('pengeluaran.*')
                ->orderBy('id_pengeluaran', 'desc')
                ->get();
        } elseif (auth()->user()->level == 1) {
            $pengeluaran = Pengeluaran::orderBy('id_pengeluaran', 'desc')->get();
        } else {
            $pengeluaran = DB::table('pengeluaran')
                ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                ->where('users.level', 2)
                ->orWhere('users.level', 6)
                ->select('pengeluaran.*')
                ->orderBy('id_pengeluaran', 'desc')
                ->get();
        }

        return datatables()
            ->of($pengeluaran)
            ->addIndexColumn()
            ->addColumn('created_at', function ($pengeluaran) {
                return tanggal_indonesia($pengeluaran->created_at, false);
            })
            ->addColumn('nominal', function ($pengeluaran) {
                return format_uang($pengeluaran->nominal);
            })
            ->addColumn('aksi', function ($pengeluaran) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`' . route('pengeluaran.update', $pengeluaran->id_pengeluaran) . '`)" class="btn btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`' . route('pengeluaran.destroy', $pengeluaran->id_pengeluaran) . '`)" class="btn btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['id_user'] = auth()->id();
        $pengeluaran = Pengeluaran::create($request->all());

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pengeluaran = Pengeluaran::find($id);

        return response()->json($pengeluaran);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $pengeluaran = Pengeluaran::find($id)->update($request->all());

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pengeluaran = Pengeluaran::find($id)->delete();

        return response(null, 204);
    }

    public function pdf($text, $awal, $akhir)
    {
        $akhir = Carbon::parse($akhir)->endOfDay();

        if ($text == 'cuci') {
            $title = 'Jasa Cuci';
            if (auth()->user()->level == 4 || auth()->user()->level == 1) {
                $pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['pengeluaran.deskripsi', 'Jasa Cuci']])
                    ->orWhere([['users.level', 1], ['pengeluaran.deskripsi', 'Jasa Cuci']])
                    ->select('pengeluaran.*')
                    ->whereBetween('pengeluaran.created_at', [$awal, $akhir])
                    ->get();
            }
        } elseif ($text == 'service') {
            $title = 'Jasa Service';
            if (auth()->user()->level == 4 || auth()->user()->level == 1) {
                $pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['pengeluaran.deskripsi', 'Jasa Service']])
                    ->orWhere([['users.level', 1], ['pengeluaran.deskripsi', 'Jasa Service']])
                    ->select('pengeluaran.*')
                    ->whereBetween('pengeluaran.created_at', [$awal, $akhir])
                    ->get();
            }
        } elseif ($text == 'operasional') {
            $title = 'Jasa Operasional';
            if (auth()->user()->level == 4 || auth()->user()->level == 1) {
                $pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['pengeluaran.deskripsi', '!=', 'Jasa Service'], ['pengeluaran.deskripsi', '!=', 'Jasa Cuci']])
                    ->select('pengeluaran.*')
                    ->whereBetween('pengeluaran.created_at', [$awal, $akhir])
                    ->get();
            }
        } elseif ($text == 'semua') {
            $title = 'Semua';
            if (auth()->user()->level == 4) {
                $pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where('users.level', 4)
                    ->orWhere(function ($query) {
                        $query->where('users.level', 1)
                            ->whereIn('pengeluaran.deskripsi', ['Jasa Service', 'Jasa Cuci']);
                    })
                    ->whereBetween('pengeluaran.created_at', [$awal, $akhir])
                    ->select('pengeluaran.*')
                    ->get();
            } elseif (auth()->user()->level == 5) {
                $pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where('users.level', 5)
                    ->orWhere('users.level', 8)
                    ->whereBetween('pengeluaran.created_at', [$awal, $akhir])
                    ->select('pengeluaran.*')
                    ->get();
            } elseif (auth()->user()->level == 1) {
                $pengeluaran = Pengeluaran::whereBetween('created_at', [$awal, $akhir])->get();
            } else {
                $pengeluaran = DB::table('pengeluaran')
                    ->join('users', 'pengeluaran.id_user', '=', 'users.id')
                    ->where('users.level', 2)
                    ->orWhere('users.level', 6)
                    ->select('pengeluaran.*')
                    ->whereBetween('pengeluaran.created_at', [$awal, $akhir])
                    ->get();
            }
        }

        $jumlah = 0;
        foreach ($pengeluaran as $item) {
            $jumlah += $item->nominal;
        }
        return view('pengeluaran.pdf', [
            'awal' => $awal, 'akhir' => $akhir, 'pengeluaran' => $pengeluaran, 'jumlah' => $jumlah, 'title' => $title
        ]);
    }
}
