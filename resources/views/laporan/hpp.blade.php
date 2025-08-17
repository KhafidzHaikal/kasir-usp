<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laporan HPP</title>

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            font-family: 'Roboto', sans-serif;
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
            vertical-align: top;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }
    </style>
</head>

<body>
    <h3 class="text-center">Laporan HPP</h3>
    <h4 class="text-center">
        Tanggal {{ tanggal_indonesia($tanggal_awal, false) }}
        s/d
        Tanggal {{ tanggal_indonesia($tanggal_akhir, false) }}
    </h4>

    <table class="table table-striped">
        <thead>
            <tr>
                <th width="5%" rowspan="2">NO</th>
                <th rowspan="2">NAMA BARANG</th>
                <th colspan="4">STOK AWAL</th>
                <th colspan="4">PEMBELIAN</th>
                <th colspan="4">STOK AKHIR</th>
                <th rowspan="2">HPP</th>
            </tr>
            <tr>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $result)
                @if ($result == null)
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @else
                    <tr>
                        <td width="3%">{{ $loop->iteration }}</td>
                        <td style="text-align: left">{{ $result->nama_produk }}</td>
                        <td width="3%">{{ $result->stok_awal }}</td>
                        <td width="3%">{{ $result->satuan }}</td>
                        <td style="text-align: right" width="7%">{{ format_uang($result->harga_beli) }}</td>
                        <td style="text-align: right" width="7%">
                            {{ format_uang($result->harga_beli * $result->stok_awal) }}</td>
                        <td width="3%">
                            @if ($result->stok_belanja == null)
                                0
                            @else
                                {{ $result->stok_belanja }}
                            @endif
                        </td>
                        <td width="3%">{{ $result->satuan }}</td>
                        <td style="text-align: right" width="7%">{{ format_uang($result->harga_beli) }}</td>
                        <td style="text-align: right" width="7%">
                            {{ format_uang($result->stok_belanja * $result->harga_beli) }}</td>
                        <td width="3%">{{ $result->stok_akhir }}</td>
                        <td width="3%">{{ $result->satuan }}</td>
                        <td style="text-align: right" width="7%">{{ format_uang($result->harga_beli) }}</td>
                        <td style="text-align: right" width="7%">
                            {{ format_uang($result->harga_beli * $result->stok_akhir) }}</td>
                        <td style="text-align: right" width="7%">
                            {{ format_uang((($result->harga_beli * $result->stok_awal) + ($result->stok_belanja * $result->harga_beli)) - $result->harga_beli * $result->stok_akhir) }}
                        </td>
                    </tr>
                @endif
            @endforeach
            <tr>
                <td colspan="5"><strong>TOTAL STOK AWAL</strong></td>
                <td style="text-align: right"><strong>{{ format_uang($totalAwal) }}</strong></td>
                <td colspan="3"><strong>PEMBELIAN</strong></td>
                <td style="text-align: right"><strong>{{ format_uang($totalBeli) }}</strong></td>
                <td colspan="3"><strong>TOTAL STOK AKHIR</strong></td>
                <td style="text-align: right"><strong>{{ format_uang($totalAkhir) }}</strong></td>
                <td style="text-align: right"><strong>{{ format_uang($totalValue) }}</strong></td>
            </tr>
        </tbody>
    </table>
    <div style="text-align: right">
        <p>Cirebon, {{ tanggal_indonesia($tanggal_akhir, false) }}</p>
        <br>
        <br>
        <br>
        <p>{{ auth()->user()->name }}</p>
    </div>
</body>

</html>
