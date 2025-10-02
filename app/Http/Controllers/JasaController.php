<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use App\Models\Setting;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JasaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('jasa.index');
    }

    public function data()
    {
        if (auth()->user()->level == 4) {
            $jasa = Jasa::join('users', 'id_user', '=', 'users.id')
                ->where('users.level', 4)
                ->orWhere('users.level', 1)
                ->select('jasas.*')
                ->orderBy('id_jasa', 'desc')
                ->get();
        } elseif (auth()->user()->level == 5) {
            $jasa = Jasa::join('users', 'id_user', '=', 'users.id')
                ->where('users.level', 5)
                ->select('jasas.*')
                ->orderBy('id_jasa', 'desc')
                ->get();
        } else {
            $jasa = Jasa::orderBy('id_jasa', 'desc')->get();
        }

        return datatables()
            ->of($jasa)
            ->addIndexColumn()
            ->addColumn('created_at', function ($jasa) {
                return tanggal_indonesia($jasa->created_at, false);
            })
            ->addColumn('nominal', function ($jasa) {
                return format_uang($jasa->nominal);
            })
            ->addColumn('aksi', function ($jasa) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`' . route('jasa.update', $jasa->id_jasa) . '`)" class="btn btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="nota(`' . route('transaksi.jasa', $jasa->id_jasa) . '`)" class="btn btn-warning btn-flat"><i class="fa fa-print"></i></button>
                    <button type="button" onclick="deleteData(`' . route('jasa.destroy', $jasa->id_jasa) . '`)" class="btn btn-danger btn-flat"><i class="fa fa-trash"></i></button>
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
     * @param  \App\Http\Requests\StoreJasaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['id_user'] = auth()->id();
        Jasa::create($request->all());
        
        $pengeluaran = new Pengeluaran();
        $pengeluaran->deskripsi = $request->deskripsi;
        $pengeluaran->nominal   = $request->nominal * (1 - $request->persen / 100);
        $pengeluaran->id_user   = auth()->id();
        $pengeluaran->save();

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Jasa  $jasa
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $jasa = Jasa::find($id);

        return response()->json($jasa);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Jasa  $jasa
     * @return \Illuminate\Http\Response
     */
    public function edit(Jasa $jasa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateJasaRequest  $request
     * @param  \App\Models\Jasa  $jasa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $jasa = Jasa::find($id);
        $jasa->update($request->all());

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Jasa  $jasa
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $jasa = Jasa::find($id);
        $jasa->delete();
        
        return response(null, 204);
    }

    public function pdf($text, $awal, $akhir)
    {
        $akhir = Carbon::parse($akhir)->endOfDay();
        if($text == 'cuci'){
            $title = 'Jasa Cuci';
            if (auth()->user()->level == 4) {
                $jasas = DB::table('jasas')
                    ->join('users', 'jasas.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['jasas.deskripsi', 'Jasa Cuci']])
                    ->select('jasas.*')
                    ->whereBetween('jasas.created_at', [$awal, $akhir])
                    ->get();
            } elseif (auth()->user()->level == 5) {
                $jasas = DB::table('jasas')
                    ->join('users', 'jasas.id_user', '=', 'users.id')
                    ->where([['users.level', 5], ['jasas.deskripsi', 'Jasa Cuci']])
                    ->select('jasas.*')
                    ->whereBetween('jasas.created_at', [$awal, $akhir])
                    ->get();
            } else {
                $jasas = DB::table('jasas')
                    ->where('jasas.deskripsi', 'Jasa Cuci')
                    ->select('jasas.*')
                    ->whereBetween('jasas.created_at', [$awal, $akhir])
                    ->get();
            }
        } elseif ($text == 'service') {
            $title = 'Jasa Service';
            if (auth()->user()->level == 4) {
                $jasas = DB::table('jasas')
                    ->join('users', 'jasas.id_user', '=', 'users.id')
                    ->where([['users.level', 4], ['jasas.deskripsi', 'Jasa Service']])
                    ->select('jasas.*')
                    ->whereBetween('jasas.created_at', [$awal, $akhir])
                    ->get();
            } elseif (auth()->user()->level == 5) {
                $jasas = DB::table('jasas')
                    ->join('users', 'jasas.id_user', '=', 'users.id')
                    ->where([['users.level', 5], ['jasas.deskripsi', 'Jasa Service']])
                    ->select('jasas.*')
                    ->whereBetween('jasas.created_at', [$awal, $akhir])
                    ->get();
            } else {
                $jasas = DB::table('jasas')
                    ->where('jasas.deskripsi', 'Jasa Service')
                    ->select('jasas.*')
                    ->whereBetween('jasas.created_at', [$awal, $akhir])
                    ->get();
            }
        }


        $jumlah = 0;
        foreach ($jasas as $item) {
            $jumlah += $item->nominal;
        }
        return view('jasa.pdf', [
            'awal' => $awal, 'akhir' => $akhir, 'jasas' => $jasas, 'jumlah' => $jumlah, 'title' => $title
        ]);
    }
    
    public function nota($id)
    {
        $setting = Setting::first();
        $waktu = Carbon::parse(date(now()))->translatedFormat('d F Y H:i:s');
        $detail = Jasa::find($id);

        return view('jasa.nota', compact('setting', 'detail', 'waktu'));
    }
}
