<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laporan Pembelian dan Penjualan</title>

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            line-height: normal;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }

        h2 {
            font-size: 14px;
        }

        body {
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            font-weight: 400;
            color: #000000;
            text-align: center;
        }

        td,
        th {
            border: 1px solid #000000;
            text-align: center;
            padding: 5px;
            /* vertical-align: top; */
        }

        td {
            text-align: left;
            word-spacing: 0px;
            vertical-align: top;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }
    </style>
</head>

<body onload="window.print()">
    <h3 class="text-center">LAPORAN PEMBELIAN DAN PENJUALAN</h3>
    <h4 class="text-center">
        Tanggal {{ tanggal_indonesia($awal, false) }}
        s/d
        Tanggal {{ tanggal_indonesia($akhir, false) }}
    </h4>

    <div>
        <img src={{ asset('img/logo.png') }} alt="" style="width: 70px; height: 70px">
        <p style="font-weight:700">KPRI SEJAHTERA </p>
        <p style="margin-top: -0.75rem; font-weight:700">DINKES DAN RSUD KAB CIREBON</p>
    </div>
    <div style="margin-bottom: 4rem"></div>

    <table class="table table-striped">
        <thead>
            <tr>
               <th width="5%">No</th>
                <th>Nama Barang</th>
                <th width="7%">Kode Barang</th>
                <th width="10%">Stok Awal</th>
                <th width="7%">Pembelian</th>
                <th width="7%">Penjualan</th>
                <th width="10%">Stok Sekarang</th>
                <th width="9%">Harga Satuan</th>
                <th width="10%">Total</th>
            </tr>
        </thead>
         <tbody>
            @foreach ($produk as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <!--<td>{{ tanggal_indonesia($row->created_at, false) }}</td>-->
                    <td style="text-align: left">{{ $row->nama_produk }}</td>
                    <td style="text-align: left">{{ $row->kode_produk }}</td>
                    <td>{{ $row->backup_stok_awal }}</td>
                    <td>
                        @if (is_null($row->id_pembelian_detail))
                            0
                        @else
                            {{ $row->total_jumlah_pembelian }}
                        @endif
                    </td>
                    <td>
                        @if (is_null($row->id_penjualan_detail))
                            0
                        @else
                            {{ $row->total_jumlah }}
                        @endif
                    </td>
                    <td>{{ $row->backup_stok_awal + $row->total_jumlah_pembelian - $row->total_jumlah }}</td>
                    <td style="text-align: right">{{ format_uang($row->harga_beli) }}</td>
                    <td style="text-align: right">{{ format_uang($row->harga_beli * ($row->backup_stok_awal + $row->total_jumlah_pembelian - $row->total_jumlah)) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="8"><strong>Total Stok</strong></td>
                <td style="text-align: right"><strong>{{ format_uang($total_penjualan) }}</strong></td>
            </tr>
        </tbody>
    </table>
    <br>
    <div style="text-align: right">
        <p>Cirebon, {{ tanggal_indonesia($akhir, false) }}</p>
        <br>
        <br>
        <br>
        <p>{{ auth()->user()->name }}</p>
    </div>
</body>

</html>
