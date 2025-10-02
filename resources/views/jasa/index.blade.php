@extends('layouts.master')

@section('title')
    Daftar Jasa
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Daftar Jasa</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="box">
                <div class="box-header with-border">
                    <button onclick="addForm('{{ route('jasa.store') }}')" class="btn btn-success btn-flat"><i
                            class="fa fa-plus-circle"></i> Tambah</button>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target=".bd-example-modal-lg"><i
                            class="fa fa-file-excel-o"></i> Laporan</button>
                    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Laporan Jasa</h5>
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Jasa</label>
                                            <div class="col-sm-5">
                                                <select id="jasa" class="form-control" required>
                                                    <option value="">Pilih Tipe</option>
                                                    @if (auth()->user()->level == 1)
                                                        <option value="all">Semua Jasa</option>
                                                        
                                                    @else
                                                        
                                                    @endif
                                                    @if (!in_array(auth()->user()->level, [5, 8]))
                                                        <option value="cuci">Jasa Cuci</option>
                                                        <option value="service">Jasa Service</option>
                                                    @endif
                                                    <option value="jilid">Jasa Jilid</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Tanggal Awal</label>
                                            <div class="col-sm-5">
                                                <input type="date" class="form-control" id="tanggal_awal" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Tanggal Akhir</label>
                                            <div class="col-sm-5">
                                                <input type="date" class="form-control" id="tanggal_akhir" required
                                                    value="{{ request('tanggal_awal') ?? date('Y-m-d') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <a target="_blank"
                                        onclick="this.href='/jasa/'+document.getElementById('jasa').value+ '/' +document.getElementById('tanggal_awal').value+ '/' +document.getElementById('tanggal_akhir').value"
                                        class="btn btn-primary">Cetak</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-stiped table-bordered">
                        <thead>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Nominal</th>
                            <th width="15%"><i class="fa fa-cog"></i></th>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @includeIf('jasa.form')
@endsection

@push('scripts')
    <script>
        let table;

        $(function() {
            table = $('.table').DataTable({
                processing: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('jasa.data') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        searchable: false,
                        sortable: false
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'deskripsi'
                    },
                    {
                        data: 'nominal'
                    },
                    {
                        data: 'aksi',
                        searchable: false,
                        sortable: false
                    },
                ]
            });

            $('#modal-form').validator().on('submit', function(e) {
                if (!e.preventDefault()) {
                    $.post($('#modal-form form').attr('action'), $('#modal-form form').serialize())
                        .done((response) => {
                            $('#modal-form').modal('hide');
                            table.ajax.reload();
                        })
                        .fail((errors) => {
                            alert('Tidak dapat menyimpan data');
                            return;
                        });
                }
            });
        });

        function addForm(url) {
            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Tambah Jasa');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('post');
            $('#modal-form [name=deskripsi]').focus();
        }

        function editForm(url) {
            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Edit Jasa');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('put');
            $('#modal-form [name=deskripsi]').focus();

            $.get(url)
                .done((response) => {
                    $('#modal-form [name=deskripsi]').val(response.deskripsi);
                    $('#modal-form [name=nominal]').val(response.nominal);
                })
                .fail((errors) => {
                    alert('Tidak dapat menampilkan data');
                    return;
                });
        }

        function deleteData(url) {
            if (confirm('Yakin ingin menghapus data terpilih?')) {
                $.post(url, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'delete'
                    })
                    .done((response) => {
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        console.log(errors);
                        alert('Tidak dapat menghapus data');
                        return;
                    });
            }
        }

        function nota(url, title) {
            popupCenter(url, title, 625, 500);
        }

        function popupCenter(url, title, w, h) {
            const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
            const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;

            const width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document
                .documentElement.clientWidth : screen.width;
            const height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document
                .documentElement.clientHeight : screen.height;

            const systemZoom = width / window.screen.availWidth;
            const left = (width - w) / 2 / systemZoom + dualScreenLeft
            const top = (height - h) / 2 / systemZoom + dualScreenTop
            const newWindow = window.open(url, title,
                `
            scrollbars=yes,
            width  = ${w / systemZoom}, 
            height = ${h / systemZoom}, 
            top    = ${top}, 
            left   = ${left}
        `
            );

            if (window.focus) newWindow.focus();
        }
    </script>
@endpush
