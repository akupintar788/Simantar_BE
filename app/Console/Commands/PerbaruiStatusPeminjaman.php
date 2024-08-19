<?php

namespace App\Console\Commands;

use App\Models\Peminjamans;
use Illuminate\Console\Command;

class PerbaruiStatusPeminjaman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peminjaman:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memperbarui status peminjaman barang';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ambil semua peminjaman yang statusnya 'disetujui'
        $peminjamanDisetujui = Peminjamans::where('status_peminjaman', 'Disetujui')->get();

        foreach ($peminjamanDisetujui as $peminjaman) {
            // Cek apakah waktu peminjaman sudah dimulai
            if ($peminjaman->tgl_peminjaman <= now()) {
                // Ubah status menjadi 'dipinjam'
                $peminjaman->update(['status_peminjaman' => 'Dipinjam']);
                $this->info("Status peminjaman barang {$peminjaman->id} diperbarui menjadi 'Dipinjam'.");
            }
        }

        $this->info('Pembaruan status peminjaman selesai.');
    }
}
