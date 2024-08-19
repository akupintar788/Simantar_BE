<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Peminjamans;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KirimPesanPengingat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peminjaman:kirimpesan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('KirimPesanPengingat command dimulai.');
        // Ambil semua peminjaman yang statusnya 'dipinjam' dan mendekati tanggal pengembalian
      // Ambil data peminjaman yang mendekati tanggal pengembalian dan memiliki status 'Dipinjam'
      $peminjamans = Peminjamans::approachingReturnDate()->get();

      // Buat array untuk menampung data hasil
      $data = [];

      // Loop melalui setiap peminjaman
      foreach ($peminjamans as $peminjaman) {
          // Tambahkan data peminjaman, user, dan peminjaman barang ke dalam array
          $data[] = $peminjaman->getDataForNotification();
          
      }

        $this->kirimPesanWhatsApp($data);

        $this->info('Pengiriman pesan pengingat selesai.');
        Log::info('KirimPesanPengingat command selesai.');
    }

    /**
     * Kirim pesan WhatsApp pengingat kepada pengguna.
     *
     * @param \App\Models\Peminjamans $peminjaman
     */
    private function kirimPesanWhatsApp($data)
    {
        // Implementasi pengiriman pesan WhatsApp di sini
        // Anda dapat menggunakan library WhatsApp API atau layanan pengiriman pesan WhatsApp lainnya

        foreach ($data as $item) {
            $namaPengguna = $item['peminjaman']['nama_peminjam'];
            $tanggalPeminjaman = $item['peminjaman']['tgl_peminjaman'];
            $tanggalPengembalian = $item['peminjaman']['tgl_pengembalian'];
            $keperluan = $item['peminjaman']['keperluan'];
            
            // Inisialisasi string kosong untuk menyimpan nama barang
            $namabarang = '';
    
            // Iterasi melalui setiap barang dalam peminjaman
            foreach ($item['peminjaman_barangs'] as $barang) {
                // Tambahkan nama barang ke dalam string $namabarang
                $namabarang .= "." . $barang['barang']['nama_barang'] . "\n";
            }
    
            // Template pesan WhatsApp
            $pesan = "Halo $namaPengguna,\n\n"
                . "Peminjaman Anda akan segera berakhir. Mohon untuk segera mengembalikan barang yang Anda pinjam. Berikut detail peminjaman Anda:\n\n"
                . "- Nama Barang:\n$namabarang" // Tampilkan daftar nama barang
                . "- Tanggal Peminjaman: $tanggalPeminjaman\n"
                . "- Tanggal Pengembalian: $tanggalPengembalian\n"
                . "- Keperluan: $keperluan\n\n"
                . "Mohon pastikan untuk mengembalikan barang tepat waktu.\n\n"
                . "Terima kasih atas kerjasamanya.\n\n"
                . "Salam,\n"
                . "Tim Administrasi";

        
        $nomorHp = $item['user']['no_hp'];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $nomorHp,
                'message' => $pesan,
                'countryCode' => '62', //optional
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: 9mC2SBSxa9HckR6vaDqb'
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Tampilkan informasi bahwa pesan telah dikirim
        $this->info("Pesan pengingat telah dikirim ke $namaPengguna.");

        if ($httpCode == 200) {
            $this->info("Pesan pengingat telah dikirim ke $namaPengguna.");
            Log::info("Pesan pengingat telah dikirim ke $namaPengguna.");
        } else {
            $this->error("Gagal mengirim pesan ke $namaPengguna.");
            Log::error("Gagal mengirim pesan ke $namaPengguna. Response: $response");
        }
}
    }
}
