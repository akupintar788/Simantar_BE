<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeminjamanBarang extends Model
{
    use HasFactory;
    protected $table = 'peminjaman_barangs'; // Nama tabel
    protected $fillable = [
        'peminjaman_id',
        'barang_id',
        'jumlah_dipinjam',
        'status_peminjaman',
        'catatan',
        'notifikasi',
    ]; // Bidang yang dapat diisi

    public $timestamps = true;

    public function peminjaman()
    {
        return $this->belongsTo(Peminjamans::class, 'peminjaman_id', 'id');
    }

    public function barang()
    {
        return $this->belongsTo(Barangs::class, 'barang_id', 'id');
    }
}
