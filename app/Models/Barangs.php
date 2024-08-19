<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangs extends Model
{
    use HasFactory;

    protected $table = 'barangs';

    protected $fillable = [
        'ruangan_id',
        'user_id',
        'jurusan_id',
        'kode_barang',
        'nama_barang',
        'spesifikasi',
        'pengadaan',
        'jenis_barang',
        'kategori_barang',
        'kuantitas',
        'keterangan_barang',
        'keadaan_barang',
        'status_ketersediaan',
        'barcode',
    ];
     // Mendefinisikan relasi "belongsTo" dengan model Ruangan
     public function ruangan()
     {
         return $this->belongsTo(Ruangans::class, 'ruangan_id', 'id');
     }

     public function jurusan()
    {
        return $this->belongsTo(Jurusans::class, 'jurusan_id', 'id');
    }

    public function peminjamans()
    {
        return $this->belongsToMany(Peminjamans::class, 'peminjaman_barangs');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public $timestamps = false;

    
}
