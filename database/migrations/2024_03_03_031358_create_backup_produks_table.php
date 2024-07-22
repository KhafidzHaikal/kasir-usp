<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackupProduksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backup_produks', function (Blueprint $table) {
            $table->increments('id')->start_from(1);
            $table->foreignId('id_produk')->onDelete('cascade');
            $table->foreignId('id_kategori')->onDelete('cascade');
            $table->string('nama_produk');
            $table->string('satuan');
            $table->integer('harga_beli');
            $table->integer('stok_awal');
            $table->integer('stok_akhir');
            $table->integer('stok_belanja');
            $table->integer('total_belanja');
            $table->date('tanggal_expire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('backup_produks');
    }
}
