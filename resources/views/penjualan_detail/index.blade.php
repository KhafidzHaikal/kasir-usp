@extends('layouts.master')

@section('title')
    Transaksi Penjualan
@endsection

@push('css')
    <style>
        .tampil-bayar {
            font-size: 5em;
            text-align: center;
            height: 100px;
        }

        .tampil-terbilang {
            padding: 10px;
            background: #f0f0f0;
        }

        .table-penjualan tbody tr:last-child {
            display: none;
        }

        @media(max-width: 768px) {
            .tampil-bayar {
                font-size: 3em;
                height: 70px;
                padding-top: 5px;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Transaksi Penjaualn</li>
@endsection

@section('content')
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <div class="row">
        <div class="col-lg-12">
            <div class="box">
                <div class="box-body">

                    <div class="form-group row">
                        <label for="kode_produk" class="col-lg-2">Kode Produk</label>
                        <div class="col-lg-5">
                            <div class="input-group">
                                <form class="form-produk">
                                    @csrf
                                    <input type="hidden" name="id_penjualan" id="id_penjualan" value="{{ $id_penjualan }}">
                                    <input type="hidden" name="id_produk" id="id_produk">
                                    <span class="input-group-btn" style="position: absolute">
                                        <button onclick="tampilProduk()" class="btn btn-info btn-flat" type="button"><i
                                                class="fa fa-arrow-right"></i></button>
                                    </span>
                                </form>
                            </div>
                            {{-- <form action="{{ route('search') }}" method="get"> --}}
                            <form id="search-form">
                                <input type="hidden" name="id_penjualan" id="id_penjualan" value="{{ $id_penjualan }}">
                                <input type="text" name="kode_produk" id="kode_produk" class="form-control" autofocus
                                    style="margin-left: 3rem; width: 90%">
                            </form>
                        </div>
                    </div>

                    <table class="table table-stiped table-bordered table-penjualan">
                        <thead>
                            <th width="5%">No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th width="15%">Jumlah</th>
                            <th>Diskon</th>
                            <th>Subtotal</th>
                            <th width="15%"><i class="fa fa-cog"></i></th>
                        </thead>
                    </table>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="tampil-bayar bg-primary"></div>
                            <div class="tampil-terbilang"></div>
                        </div>
                        <div class="col-lg-4">
                            <form action="{{ route('transaksi.simpan') }}" class="form-penjualan" method="post">
                                @csrf
                                <input type="hidden" name="id_penjualan" value="{{ $id_penjualan }}">
                                <input type="hidden" name="total" id="total">
                                <input type="hidden" name="total_item" id="total_item">
                                <input type="hidden" name="bayar" id="bayar">
                                <input type="hidden" name="id_member" id="id_member"
                                    value="{{ $memberSelected->id_member }}">

                                <div class="form-group row">
                                    <label for="totalrp" class="col-lg-2 control-label">Total</label>
                                    <div class="col-lg-8">
                                        <input type="text" id="totalrp" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="kode_member" class="col-lg-2 control-label">Anggota</label>
                                    <div class="col-lg-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="kode_member"
                                                value="{{ $memberSelected->kode_member }}">
                                            <span class="input-group-btn">
                                                <button onclick="tampilMember()" class="btn btn-info btn-flat"
                                                    type="button"><i class="fa fa-arrow-right"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="diskon" class="col-lg-2 control-label">Diskon</label>
                                    <div class="col-lg-8">
                                        <input type="number" name="diskon" id="diskon" class="form-control"
                                            value="{{ !empty($memberSelected->id_member) ? $diskon : 0 }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="bayar" class="col-lg-2 control-label">Bayar</label>
                                    <div class="col-lg-8">
                                        <input type="text" id="bayarrp" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pembayaran" class="col-lg-2 control-label">Pembayaran</label>
                                    <div class="col-lg-8">
                                        <select name="pembayaran" id="pembayaran" class="form-control" required>
                                            <option value="">Pilih Pembayaran</option>
                                            <option value="0">Tunai</option>
                                            <option value="1">Kredit</option>
                                        </select>
                                        <span class="help-block with-errors"></span>
                                    </div>
                                </div>
                                <div class="form-group row cicilan-group">
                                    <label for="cicilan" class="col-lg-2 control-label">Kredit Cicilan</label>
                                    <div class="col-lg-8">
                                        <select name="cicilan" id="cicilan" class="form-control" required>
                                            <option value="">Pilih Kredit Cicilan</option>
                                            <option value="2">x 2 Bulan</option>
                                            <option value="3">x 3 Bulan</option>
                                            <option value="4">x 4 Bulan</option>
                                            <option value="5">x 5 Bulan</option>
                                            <option value="6">x 6 Bulan</option>
                                            <option value="8">x 8 Bulan</option>
                                            <option value="9">x 9 Bulan</option>
                                            <option value="10">x 10 Bulan</option>
                                            <option value="11">x 11 Bulan</option>
                                            <option value="12">x 12 Bulan</option>
                                            <option value="24">x 24 Bulan</option>
                                        </select>
                                        <span class="help-block with-errors"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="diterima" class="col-lg-2 control-label">Diterima</label>
                                    <div class="col-lg-8">
                                        <input type="number" id="diterima" class="form-control" name="diterima"
                                            value="{{ $penjualan->diterima ?? 0 }}">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="kembali" class="col-lg-2 control-label">Kembali</label>
                                    <div class="col-lg-8">
                                        <input type="text" id="kembali" name="kembali" class="form-control"
                                            value="0" readonly>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <button type="button" class="btn btn-danger btn-sm btn-flat pull-left btn-batal"><i
                            class="fa fa-times"></i> Batal Transaksi</button>
                    <button type="submit" class="btn btn-primary btn-sm btn-flat pull-right btn-simpan"><i
                            class="fa fa-floppy-o"></i> Simpan Transaksi</button>
                </div>
            </div>
        </div>
    </div>

    @includeIf('penjualan_detail.produk')
    @includeIf('penjualan_detail.member')
@endsection

@push('scripts')
    <script>
        let table, table2, table3;

        $('#pembayaran').on('change', function() {
            var pembayaranValue = $(this).val();
            if (pembayaranValue == 1) {
                $('.cicilan-group').show();
            } else {
                $('.cicilan-group').hide();
            }
        });

        // Initialize the visibility of .cicilan-group based on the initial value of #pembayaran
        if ($('#pembayaran').val() == 1) {
            $('.cicilan-group').show();
        } else {
            $('.cicilan-group').hide();
        }

        $(document).ready(function() {
            $('#search-form').submit(function(event) {
                event.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: 'GET',
                    url: "{{ route('search') }}",
                    data: formData,
                    success: function(response) {
                        if (response.success == 200) {
                            $('#kode_produk').val("");
                            $('#kode_produk').focus();
                            table.ajax.reload(() => loadForm($('#diskon').val()));
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'Produk Tidak Ditemukan';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errorMessage = xhr.responseText;
                        }
                        alert(errorMessage);
                        $('#kode_produk').val("");
                        $('#kode_produk').focus();
                        return;
                    }
                });
            });
        });

        $(function() {
            $('body').addClass('sidebar-collapse');

            table = $('.table-penjualan').DataTable({
                    processing: true,
                    autoWidth: false,
                    ajax: {
                        url: '{{ route('transaksi.data', $id_penjualan) }}',
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            searchable: false,
                            sortable: false
                        },
                        {
                            data: 'kode_produk'
                        },
                        {
                            data: 'nama_produk'
                        },
                        {
                            data: 'harga_jual'
                        },
                        {
                            data: 'jumlah'
                        },
                        {
                            data: 'diskon'
                        },
                        {
                            data: 'subtotal'
                        },
                        {
                            data: 'aksi',
                            searchable: false,
                            sortable: false
                        },
                    ],
                    dom: 'Brt',
                    bSort: false,
                    paginate: false
                })
                .on('draw.dt', function() {
                    loadForm($('#diskon').val());
                    setTimeout(() => {
                        $('#diterima').trigger('input');
                    }, 300);
                });
            table2 = $('.table-produk').DataTable();            

            $(document).on('input', '.quantity', function() {
                let self = this;
                let id = $(this).data('id');
                let jumlah = parseInt($(this).val());

                $.get($(this).data('url'), function(data) {
                    let stok = parseInt(data.stok);

                    if (jumlah < 1) {
                        $(self).val(1);
                        alert('Jumlah produk ' + data.nama_produk + ' tidak boleh kurang dari 1');
                        return;
                    }
                    if (jumlah > stok) {
                        $(self).val(stok);
                        alert('Jumlah produk pada produk ' + data.nama_produk +
                            ' stok yang tersedia ' + stok + '');
                        return;
                    }
                    if (jumlah > 10000) {
                        $(self).val(10000);
                        alert('Jumlah produk ' + data.nama_produk +
                            ' tidak boleh lebih dari 10000');
                        return;
                    }

                    $.post(`{{ url('/transaksi') }}/${id}`, {
                            '_token': $('[name=csrf-token]').attr('content'),
                            '_method': 'put',
                            'jumlah': jumlah
                        })
                        .done(response => {
                            $(self).on('mouseout', function() {
                                table.ajax.reload(() => loadForm($('#diskon').val()));
                            });
                        })
                        .fail(errors => {
                            let errorMessage = 'Tidak dapat menyimpan data';
                            if (errors.responseJSON && errors.responseJSON.message) {
                                errorMessage = errors.responseJSON.message;
                            } else if (errors.responseText) {
                                errorMessage = errors.responseText;
                            }
                            alert(errorMessage);
                            return;
                        });
                });
            });

            $(document).on('input', '#diskon', function() {
                if ($(this).val() == "") {
                    $(this).val(0).select();
                }

                loadForm($(this).val());
            });

            $('#diterima').on('input', function() {
                if ($(this).val() == "") {
                    $(this).val(0).select();
                }

                loadForm($('#diskon').val(), $(this).val());
            }).focus(function() {
                $(this).select();
            });

            $('.btn-simpan').on('click', function() {
                $('.form-penjualan').submit();
            });
            
            $('.btn-batal').on('click', function() {
                if (confirm('Yakin ingin membatalkan transaksi ini?')) {
                    $.ajax({
                        url: '{{ route('transaksi.batal', $id_penjualan) }}',
                        type: 'DELETE',
                        data: {
                            '_token': $('[name=csrf-token]').attr('content')
                        },
                        success: function(response) {
                            alert('Transaksi berhasil dibatalkan');
                            window.location.href = '{{ route('dashboard') }}';
                        },
                        error: function(xhr, status, error) {
                            let errorMessage = 'Tidak dapat membatalkan transaksi';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                errorMessage = xhr.responseText;
                            }
                            alert(errorMessage);
                        }
                    });
                }
            });
        });

        function tampilProduk() {
            $('#modal-produk').modal('show');
        }

        function hideProduk() {
            $('#modal-produk').modal('hide');
        }

        function pilihProduk(id, kode) {
            $('#id_produk').val(id);
            $('#kode_produk').val(kode);
            hideProduk();
            tambahProduk();
        }

        function tambahProduk() {
            $.post('{{ route('transaksi.store') }}', $('.form-produk').serialize())
                .done(response => {
                    $('#kode_produk').focus();
                    table.ajax.reload(() => loadForm($('#diskon').val()));
                })
                .fail(errors => {
                    let errorMessage = 'Tidak dapat menyimpan data';
                    if (errors.responseJSON && errors.responseJSON.message) {
                        errorMessage = errors.responseJSON.message;
                    } else if (errors.responseText) {
                        errorMessage = errors.responseText;
                    }
                    alert(errorMessage);
                    return;
                });
        }

        function tampilMember() {
            $('#modal-member').modal('show');
            table3 = $('.table-member').DataTable();
        }

        function pilihMember(id, kode) {
            $('#id_member').val(id);
            $('#kode_member').val(kode);
            $('#diskon').val('{{ $diskon }}');
            loadForm($('#diskon').val());
            $('#diterima').val(0).focus().select();
            hideMember();
        }

        function hideMember() {
            $('#modal-member').modal('hide');
        }

        function deleteData(url) {
            if (confirm('Yakin ingin menghapus data terpilih?')) {
                $.post(url, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'delete'
                    })
                    .done((response) => {
                        table.ajax.reload(() => loadForm($('#diskon').val()));
                    })
                    .fail((errors) => {
                        alert('Tidak dapat menghapus data');
                        return;
                    });
            }
        }

        function loadForm(diskon = 0, diterima = 0) {
            $('#total').val($('.total').text());
            $('#total_item').val($('.total_item').text());

            $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
                .done(response => {
                    $('#totalrp').val('Rp. ' + response.totalrp);
                    $('#bayarrp').val('Rp. ' + response.bayarrp);
                    $('#bayar').val(response.bayar);
                    $('.tampil-bayar').text('Bayar: Rp. ' + response.bayarrp);
                    $('.tampil-terbilang').text(response.terbilang);

                    $('#kembali').val('Rp.' + response.kembalirp);
                    if ($('#diterima').val() != 0) {
                        $('.tampil-bayar').text('Kembali: Rp. ' + response.kembalirp);
                        $('.tampil-terbilang').text(response.kembali_terbilang);
                    }
                })
                .fail(errors => {
                    alert('Tidak dapat menampilkan data');
                    return;
                })
        }
    </script>
@endpush
