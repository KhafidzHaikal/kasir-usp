<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form">
    <div class="modal-dialog modal-lg" role="document">
        <form action="" method="post" class="form-horizontal">
            @csrf
            @method('post')

            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label for="name" class="col-lg-3 col-lg-offset-1 control-label">Nama</label>
                        <div class="col-lg-6">
                            <input type="text" name="name" id="name" class="form-control" required autofocus>
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="level" class="col-lg-3 col-lg-offset-1 control-label">Tipe</label>
                        <div class="col-lg-6">
                            <select name="level" id="level" class="form-control" required>
                                <option value="">Pilih Tipe</option>
                                 @if (auth()->user()->level == 4)
                                    <option value="4">Bengkel</option>
                                @elseif (auth()->user()->level == 5)
                                    <option value="5">Fotocopy Admin</option>
                                    <option value="8">Kasir Fotocopy</option>
                                @elseif (auth()->user()->level == 1)
                                    <option value="1">Admin</option>
                                    <option value="2">Waserda Admin</option>
                                    <option value="6">Kasir</option>
                                    <option value="3">Gudang</option>
                                    <option value="4">Bengkel</option>
                                    <option value="5">Fotocopy Admin</option>
                                    <option value="8">Kasir Fotocopy</option>
                                @else
                                    <option value="2">Waserda Admin</option>
                                    <option value="6">Kasir</option>
                                    <option value="3">Gudang</option>
                                @endif
                            </select>
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="email" class="col-lg-3 col-lg-offset-1 control-label">Email</label>
                        <div class="col-lg-6">
                            <input type="email" name="email" id="email" class="form-control" required>
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="password" class="col-lg-3 col-lg-offset-1 control-label">Password</label>
                        <div class="col-lg-6">
                            <input type="password" name="password" id="password" class="form-control" required
                                minlength="6">
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="password_confirmation" class="col-lg-3 col-lg-offset-1 control-label">Konfirmasi
                            Password</label>
                        <div class="col-lg-6">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" required data-match="#password">
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-sm btn-flat btn-primary"><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="btn btn-sm btn-flat btn-warning" data-dismiss="modal"><i
                            class="fa fa-arrow-circle-left"></i> Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
