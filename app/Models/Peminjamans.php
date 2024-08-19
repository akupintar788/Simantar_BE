<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Peminjamans extends Model
{
    use HasFactory;
    protected $table = 'peminjamans';

    protected $fillable = [	
        'user_id',
        'nama_peminjam',	
        'tgl_peminjaman',	
        'tgl_pengembalian',
        'keperluan',
        // 'status_peminjaman',
        // 'status_pengajuan'		
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function barangs()
{
    return $this->belongsToMany(Barangs::class, 'peminjaman_barangs', 'peminjaman_id', 'barang_id', 'jumlah_dipinjam', 'status_peminjaman', 'catatan',);
}

public function peminjaman_barangs()
    {
        return $this->hasMany(PeminjamanBarang::class, 'peminjaman_id');
    }


    public function scopeApproachingReturnDate($query)
    {
        return $query->whereHas('peminjaman_barangs', function ($query) {
            $query->whereIn('status_peminjaman', ['Dipinjam', 'Terlambat']);
        })->where('tgl_pengembalian', '<=', Carbon::now()->addDays(5));
    }

    public function getDataForNotification()
{
    return [
        'peminjaman' => $this->toArray(),
        'user' => $this->user->toArray(),
        'peminjaman_barangs' => $this->peminjaman_barangs->whereIn('status_peminjaman', ['Dipinjam', 'Terlambat'])->map(function ($peminjamanBarang) {
            return [
                'id' => $peminjamanBarang->id,
                'barang_id' => $peminjamanBarang->barang_id,
                'peminjaman_id' => $peminjamanBarang->peminjaman_id,
                'jumlah_dipinjam' => $peminjamanBarang->jumlah_dipinjam,
                'status_peminjaman' => $peminjamanBarang->status_peminjaman,
                'catatan' => $peminjamanBarang->catatan,
                'barang' => $peminjamanBarang->barang->toArray() // Informasi barang
            ];
        })->toArray()
    ];
}



    public $timestamps = false;

}