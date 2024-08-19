<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Barangs;
use App\Models\Peminjamans;
use Illuminate\Http\Request;
use App\Models\PeminjamanBarang;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class PeminjamanController extends Controller
{
    public function index()
    {
        try {
            // Mengambil data peminjaman berserta detail barang yang sedang dipinjam dan informasi nama barang
            $peminjamans = Peminjamans::with(['peminjaman_barangs', 'peminjaman_barangs.barang'])->get();
    
            return response()->json($peminjamans, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mendapatkan data peminjaman', 'error' => $e->getMessage()], 500);
        }
    }

    public function getData()
{
    try {
        // Mengambil data peminjaman barang beserta data peminjaman dan data barang yang terkait
        $peminjamanBarangs = PeminjamanBarang::with(['peminjaman', 'barang'])->get();

        return response()->json($peminjamanBarangs, 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Gagal mendapatkan data peminjaman barang', 'error' => $e->getMessage()], 500);
    }
}

public function notif()
    {
        // Ambil data peminjaman yang mendekati tanggal pengembalian dan memiliki status 'Dipinjam'
        $peminjamans = Peminjamans::approachingReturnDate()->get();

        // Buat array untuk menampung data hasil
        $data = [];

        // Loop melalui setiap peminjaman
        foreach ($peminjamans as $peminjaman) {
            // Tambahkan data peminjaman, user, dan peminjaman barang ke dalam array
            $data[] = $peminjaman->getDataForNotification();
        }

        // Tampilkan data dalam format JSON
        return response()->json($data);
    }


    public function store(Request $request)
{
    // Validasi input data
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'nama_peminjam' => 'required|string',
        'tgl_peminjaman' => 'required|date_format:Y-m-d H:i:s',
        'tgl_pengembalian' => 'nullable|date_format:Y-m-d H:i:s',
        'keperluan' => 'nullable|string',
        'barangs' => 'required|array', // Barang yang akan dipinjam
        'barangs.*.id' => 'required|exists:barangs,id', // Validasi barang_id
        'barangs.*.user_id' => 'required|exists:users,id',
        'barangs.*.jumlah_dipinjam' => 'required|integer|min:1', // Validasi jumlah_dipinjam
    ]);

    // // Memeriksa apakah ada lebih dari satu pengguna yang berbeda dalam daftar barang yang ingin dipinjam
    // $uniqueUserIds = collect($request->barangs)->pluck('user_id')->unique();
    // if ($uniqueUserIds->count() > 1) {
    //     return response()->json(['message' => 'Anda tidak diizinkan untuk meminjam barang ini secara bersamaan'], 400);
    // }

    // Mulai transaksi
    DB::beginTransaction();

    try {
        // Buat peminjaman baru
        $peminjaman = Peminjamans::create([
            'user_id' => $request->user_id,
            'nama_peminjam' => $request->nama_peminjam,
            'tgl_peminjaman' => $request->tgl_peminjaman,
            'tgl_pengembalian' => $request->tgl_pengembalian,
            'keperluan' => $request->keperluan,
            

        ]);

        // Ambil ID peminjaman yang baru dibuat
        $peminjamanId = $peminjaman->id;

        $nomorTeleponBarang = [];
        $namauser = [];
        $barangsPerUser = [];

        // Iterasi melalui barang yang ingin dipinjam
        foreach ($request->barangs as $barangData) {
            $barangId = $barangData['id'];
            $jumlahDipinjam = $barangData['jumlah_dipinjam'];
        
            // Dapatkan data barang dari database
            $barang = Barangs::findOrFail($barangId);
        
            // Validasi stok barang
            if ($barang->kuantitas < $jumlahDipinjam) {
                // Jika stok tidak mencukupi, batalkan proses
                DB::rollBack();
                return response()->json(['message' => 'Stok barang tidak mencukupi'], 400);
            }

            // Kelompokkan barang-barang berdasarkan pengguna yang sama
            $userId = $barang->user_id;
            $barangsPerUser[$userId][] = $barangData;

             // Simpan nomor telepon pengguna (user) yang terkait dengan barang yang dipinjam
             $nomorTeleponBarang[] = $barang->user->no_hp;
             $namauser[] = $barang->user->nama_user;

        
            // Tambahkan data ke tabel pivot
            $peminjaman->barangs()->attach($barangId, [
                'jumlah_dipinjam' => $jumlahDipinjam,
                'peminjaman_id' => $peminjamanId,
                'notifikasi' => 'Terdapat peminjaman baru: ' . $request->nama_peminjam . ' - ' . $barang->nama_barang . '. Segera lakukan persetujuan.'
            ]);
        }
        

        // Komit transaksi jika semua berhasil
        DB::commit();

        // Kirim pesan notifikasi atau pemberitahuan ke setiap pengguna yang meminjam barang
        foreach ($barangsPerUser as $userId => $barangs) {
            $user = User::findOrFail($userId);
            $this->sendNotificationToUser($user, $peminjaman, $barangs);
        }

        return response()->json(['message' => 'Peminjaman berhasil ditambahkan', 'peminjaman' => $peminjaman], 201);
    } catch (\Exception $e) {
        // Batalkan transaksi jika terjadi error
        DB::rollBack();

        return response()->json(['message' => 'Gagal menambahkan peminjaman', 'error' => $e->getMessage()], 500);
    }
}

// Metode untuk mengirim pesan notifikasi ke nomor telepon pengguna (user)
private function sendNotificationToUser($user, $peminjaman, $barangs)
{
        $namaPeminjam = $peminjaman->nama_peminjam;
        $tglPeminjaman = $peminjaman->tgl_peminjaman;
        $keperluan = $peminjaman->keperluan;

        $message = "Hallo $user->nama_user,\n\n";
        $message .= "Ada peminjaman baru yang perlu diverifikasi:\n";
        $message .= "Nama Peminjam: $namaPeminjam\n";
        $message .= "Tanggal Peminjaman: $tglPeminjaman\n";
        $message .= "Keperluan: $keperluan\n\n";
        $message .= "Mohon untuk segera melakukan verifikasi terhadap peminjaman ini.\n\n";
        $message .= "Terimakasih\n\n";

        // Kirim pesan notifikasi menggunakan cURL
        $this->sendWhatsAppMessage($user->no_hp, $message);
}

// Metode untuk mengirim pesan WhatsApp menggunakan cURL
private function sendWhatsAppMessage($nomorHp, $pesan)
{
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
    curl_close($curl);
}


public function show($id)
{
    try {
        // Mengambil data peminjaman berdasarkan ID
        $peminjaman = Peminjamans::findOrFail($id);
        
        // Mengambil semua barang yang terkait dengan peminjaman
        $peminjamanBarangs = PeminjamanBarang::where('peminjaman_id', $id)->get();
        
        // Inisialisasi array untuk menyimpan data barang terkait
        $barangTerkait = [];
        
        // Loop melalui setiap objek PeminjamanBarang untuk mendapatkan data barang
        foreach ($peminjamanBarangs as $peminjamanBarang) {
            // Mengakses data barang terkait
            $barang = $peminjamanBarang->barang;
            
            // Menambahkan data barang ke dalam array
            $barangTerkait[] = [
                'nama_barang' => $barang->nama_barang,
                'jumlah_dipinjam' => $peminjamanBarang->jumlah_dipinjam
            ];
        }
        
        // Mengembalikan data peminjaman beserta data barang terkait
        return response()->json([
            'peminjaman' => $peminjaman,
            'barang_terkait' => $barangTerkait
        ]);
    } catch (\Exception $e) {
        // Mengembalikan pesan error jika terjadi kesalahan
        return response()->json(['message' => 'Gagal mendapatkan data peminjaman', 'error' => $e->getMessage()], 500);
    }
}

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status_peminjaman' => 'required|in:Diajukan,Disetujui,Tidak Disetujui,Dipinjam,Dikembalikan,Terlambat',
        'catatan' => 'nullable|string'
    ]);

    $peminjamanBarang = PeminjamanBarang::findOrFail($id);
    $oldStatus = $peminjamanBarang->status_peminjaman;

    // $peminjamanBarang->status_peminjaman = $request->status_peminjaman;
    // $peminjamanBarang->save();

    // Peroleh nomor telepon pengguna yang meminjam
    $user = User::findOrFail($peminjamanBarang->peminjaman->user_id);
    $no_hp = $user->no_hp;
    $namaPeminjam = $peminjamanBarang->peminjaman->nama_peminjam;
    $namaBarang = $peminjamanBarang->barang->nama_barang;
    $tglPeminjaman = $peminjamanBarang->peminjaman->tgl_peminjaman;
    $tglPengembalian = $peminjamanBarang->peminjaman->tgl_pengembalian;

    // Deklarasikan variabel notifikasiMessage dengan nilai awal
    $notifikasiMessage = '';

    $tglPengembalianRealTime = Carbon::now()->toDateString();


    // Kirim pesan WhatsApp jika status berubah menjadi Disetujui atau Tidak Disetujui
    if ($request->status_peminjaman === 'Disetujui' && $oldStatus !== 'Disetujui') {
        $barang = $peminjamanBarang->barang;

        if ($barang->status_ketersediaan === 'terpakai') {
            return response()->json(['message' => 'Barang tidak tersedia untuk dipinjam. Silakan tolak peminjaman tersebut.'], 400);
        } else {
            $barang->kuantitas -= $peminjamanBarang->jumlah_dipinjam;
            if ($barang->kuantitas < 0) {
                return response()->json(['message' => 'Kuantitas barang tidak mencukupi. Silakan tolak peminjaman tersebut.'], 400);
            } else {
                $barang->save();
                if ($barang->kuantitas === 0) {
                    $barang->status_ketersediaan = 'terpakai';
                    $barang->save();
                }
                $peminjamanBarang->status_peminjaman = 'Disetujui';
                $notifikasiMessage = "Peminjaman barang telah disetujui: $namaBarang untuk tanggal $tglPeminjaman";
                $this->sendWhatsAppNotification($no_hp, $peminjamanBarang->peminjaman, true);
            }
        }
    } elseif ($request->status_peminjaman === 'Tidak Disetujui' && $oldStatus !== 'Tidak Disetujui') {
        $peminjamanBarang->catatan = $request->catatan;
        $peminjamanBarang->save();
        $peminjamanBarang->status_peminjaman = 'Tidak Disetujui';
        $notifikasiMessage = "Peminjaman barang tidak disetujui: $namaBarang untuk tanggal $tglPeminjaman";
        $peminjamanBarang->notifikasi = $notifikasiMessage;
        $peminjamanBarang->update();
        $this->sendWhatsAppNotification($no_hp, $peminjamanBarang->peminjaman, false);
    } elseif ($request->status_peminjaman === 'Dikembalikan' && $oldStatus !== 'Dikembalikan') {
        $barang = $peminjamanBarang->barang;
        $barang->status_ketersediaan = 'tersedia';
        $barang->save();
        // Memperbarui kuantitas barang dengan menambah 1
        $barang->kuantitas += 1;
        $barang->save();

        if ($oldStatus === 'Terlambat') {
            $peminjamanBarang->catatan = "Dikembalikan tetapi terlambat, dikembalikan pada $tglPengembalianRealTime";
        }
        $peminjamanBarang->status_peminjaman = 'Dikembalikan';
        $notifikasiMessage = "Barang $namaBarang telah berhasil dikembalikan: Terima kasih telah mengembalikan barang";
        $peminjamanBarang->notifikasi = $notifikasiMessage;
        $peminjamanBarang->update();
        $this->sendWhatsAppNotificationPengembalian($no_hp, $peminjamanBarang->peminjaman, true); // Kirim pesan untuk pengembalian juga
    } elseif ($request->status_peminjaman === 'Dipinjam' && $oldStatus !== 'Dipinjam') {
        $peminjamanBarang->status_peminjaman = 'Dipinjam';
        $notifikasiMessage = "Anda sedang meminjaman barang: $namaBarang sejak tanggal $tglPeminjaman.";
        
    } elseif ($request->status_peminjaman === 'Terlambat' && $oldStatus !== 'Terlambat') {
        $peminjamanBarang->status_peminjaman = 'Terlambat';
        $notifikasiMessage = "Peminjaman barang telah melewati batas waktu: Mohon segera mengembalikannya.";
  
    }

    // Perbarui kolom notifikasi di tabel pivot
    if ($notifikasiMessage) {
        $peminjamanBarang->update(['notifikasi' => $notifikasiMessage, 'updated_at' => now()]);
    }

    return response()->json(['message' => 'Status peminjaman berhasil diperbarui', 'peminjaman' => $peminjamanBarang], 200);
}


private function sendWhatsAppNotification($no_hp, $peminjaman, $isApproved)
{
    $namaPeminjam = $peminjaman->nama_peminjam;
    $tglPeminjaman = $peminjaman->tgl_peminjaman;
    $tglPengembalian = $peminjaman->tgl_pengembalian;
    $keperluan = $peminjaman->keperluan;

    $message = "Halo $namaPeminjam,\n\n";
    if ($isApproved) {
        $message .= "Kami memberitahu Anda bahwa peminjaman Anda telah disetujui! Berikut detail peminjaman Anda:\n";
    } else {
        $message .= "Mohon maaf, peminjaman Anda telah ditolak. Berikut detail peminjaman yang Anda ajukan:\n";
    }
    $message .= "- Tanggal Peminjaman: $tglPeminjaman\n";
    $message .= "- Tanggal Pengembalian: $tglPengembalian\n";
    $message .= "- Keperluan: $keperluan\n\n";
    if ($isApproved) {
        $message .= "Silakan mengambil barang yang Anda pinjam sebelum peminjaman berakhir.\n";
    } else {
        $message .= "Jangan khawatir anda masih dapat mengajukan peminjaman lainnya\n";
    }
    $message .= "Terima kasih atas kerjasamanya.\n\n";
    $message .= "Salam,\nTim Admin";

    // Kirim pesan WhatsApp menggunakan Twilio
    $this->sendWhatsAppMessage($no_hp, $message);
}

private function sendWhatsAppNotificationPengembalian($no_hp, $peminjaman)
{
    $namaPeminjam = $peminjaman->nama_peminjam;

    $message = "Hallo $namaPeminjam,\n\n";
    $message .= "Terima kasih telah melakukan pengembalian tepat waktu.\n";
    $message .= "Semoga barang yang Anda pinjam bermanfaat.\n\n";
    $message .= "Salam,\nTim Admin";

    // Kirim pesan WhatsApp menggunakan Twilio
    $this->sendWhatsAppMessage($no_hp, $message);
}


    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nama_peminjam' => 'sometimes|required|string',
            'tgl_peminjaman' => 'sometimes|required|date',
            'tgl_pengembalian' => 'sometimes|required|date',
            'keterangan' => 'nullable|string',
            'jumlah_dipinjam' => 'nullable|integer',
            'status_peminjaman' => 'nullable|required|in:Diajukan,Disetujui,Tidak Disetujui,Dipinjam,Dikembalikan',
            // 'status_pengajuan' => 'nullable|required|in:disetujui,tidak disetujui',
        ]);

        $peminjaman = Peminjamans::findOrFail($id);
        $peminjaman->update($request->all());

        return response()->json(['message' => 'Data peminjaman berhasil diperbarui', 'peminjaman' => $peminjaman], 200);
    }

    public function destroy($id)
    {
        $peminjaman = Peminjamans::findOrFail($id);
        $peminjaman->delete();

        return response()->json(['message' => 'Peminjaman berhasil dihapus'], 200);
    }
}