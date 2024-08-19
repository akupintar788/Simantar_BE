<?php

namespace App\Http\Controllers;

use App\Models\Barangs;
use App\Models\Ruangans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        // $barang = Barangs::all();
        // return response()->json($barang);
        // Filter data barang berdasarkan peran pengguna
    if ($user->role === 'sarpras' || $user->role === 'ketua_program') {
        // Jika peran pengguna adalah sarpras atau ketua_program,
        // hanya ambil data barang yang memiliki user_id sesuai dengan ID pengguna yang login
        $barang = Barangs::where('user_id', $user->id)->with('ruangan', 'jurusan')->get();
    } else {
        // Jika peran pengguna bukan sarpras atau ketua_program,
        // ambil semua data barang
        $barang = Barangs::with('ruangan', 'jurusan')->get();
    }

    return response()->json($barang);
    }

    public function getLapBarang()
    {
        $barang = Barangs::with('ruangan', 'jurusan')->get();
        
        return response()->json($barang);
    }

    public function getInventaris()
    {
        // Mengambil data barang dengan kategori "barang inventaris"
        $barangs = Barangs::where('kategori_barang', 'barang inventaris')->with('ruangan', 'jurusan')->get();
        
        return response()->json($barangs);
    }

    public function getBHP()
    {
        // Mengambil data barang dengan kategori "barang inventaris"
        $barangs = Barangs::where('kategori_barang', 'barang habis pakai')->with('ruangan', 'jurusan')->get();
        
        return response()->json($barangs);
    }

    public function getBarangIdByKode(Request $request)
    {
        $kodeBarang = $request->query('kode');
        $barang = Barangs::where('kode_barang', $kodeBarang)->first();

        if ($barang) {
            return response()->json(['barang_id' => $barang->id, 'user_id' => $barang->user_id]);
        } else {
            return response()->json(['error' => 'Barang tidak ditemukan'], 404);
        }
    }

    public function getBarangUserIdByKode(Request $request)
    {
        $kodeBarang = $request->query('kode');
        $barang = Barangs::where('kode_barang', $kodeBarang)->first();

        if ($barang) {
            return response()->json(['user_id' => $barang->user_id]);
        } else {
            return response()->json(['error' => 'Barang tidak ditemukan'], 404);
        }
    }


    public function store(Request $request)
    {
        $request->validate([
            'jurusan_id' => 'required|exists:jurusans,id',
            'ruangan_id' => 'required|exists:ruangans,id',
            'kode_barang' => 'required|string|unique:barangs',
            'nama_barang' => 'required|string',
            'spesifikasi' => 'nullable|string',
            'pengadaan' => 'nullable|date',
            'jenis_barang' => 'required|in:barang sekolah,barang jurusan',
            'kategori_barang' => 'required|in:barang inventaris,barang habis pakai',
            'kuantitas' => 'nullable|integer',
            'keterangan_barang' => 'nullable|string',
            'keadaan_barang' => 'nullable|in:baik,rusak ringan,rusak sedang,rusak berat',
            'barcode' => 'nullable|image|mimes:png|max:2048',
        ]);

        $namaRuangan = Ruangans::findOrFail($request->ruangan_id)->nama_ruangan;

        $barcodeData = [
            'Kode Barang: ' . $request->kode_barang,
            'Nama Barang: ' . $request->nama_barang,
            'Spesifikasi: ' . $request->spesifikasi,
            'Pengadaan: ' . $request->pengadaan,
            'Jenis barang: ' . $request->jenis_barang,
            'Kategori Barang: ' . $request->kategori_barang,
            'Kuantitas: ' . $request->kuantitas,
            'Keterangan Barang: ' . $request->keterangan_barang,
            'Keadaan Barang: ' . $request->keadaan_barang,
            'Letak Barang: ' . $namaRuangan,
        ];

        $barcodeString = implode("\n", $barcodeData);
    
        // $user_id = Auth::id(); // Mendapatkan ID pengguna dari token JWT
        $ruangan_id = $request->ruangan_id; // Mendapatkan ID ruangan dari permintaan
        $jurusan_id = $request->jurusan_id;
        // Buat barang dengan data yang diberikan
        $barang = Barangs::create([
            'user_id' => $request->user_id,
            'ruangan_id' => $ruangan_id,
            'jurusan_id' => $jurusan_id,
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'spesifikasi' => $request->spesifikasi,
            'pengadaan' => $request->pengadaan,
            'jenis_barang' => $request->jenis_barang,
            'kategori_barang' => $request->kategori_barang,
            'kuantitas' => $request->kuantitas,
            'keterangan_barang' => $request->keterangan_barang,
            'keadaan_barang' => $request->keadaan_barang,
            'barcode' => $barcodeString,
        ]);
    
        return response()->json(['message' => 'Barang berhasil ditambahkan', 'data' => $barang], 201);

        // $data = $request->all();


        // $barang = Barangs::create($data);

        // $barang = Barangs::create($request->all());

        // // Mengambil nama ruangan
        // $namaRuangan = Ruangans::find($request->ruangan_id)->nama_ruangan;

        // // Membuat konten QR code
        // $qrCodeContent = [
        //     'kode_barang' => $barang->kode_barang,
        //     'nama_barang' => $barang->nama_barang,
        //     'spesifikasi' => $barang->spesifikasi,
        //     'pengadaan' => $barang->pengadaan,
        //     'harga_barang' => $barang->harga_barang,
        //     'jenis_barang' => $barang->jenis_barang,
        //     'kuantitas' => $barang->kuantitas,
        //     'keterangan_barang' => $barang->keterangan_barang,
        //     'lokasi_ruangan' => $namaRuangan,
        // ];

        // // Menghasilkan QR code dari konten
        // $qrCode = QrCode::size(300)->generate(json_encode($qrCodeContent));

        // // Simpan QR code ke dalam file gambar atau tempat penyimpanan lainnya
        // // Misalnya, Anda dapat menyimpannya dalam direktori public
        // $filePath = 'uploads/qrcodes/' . $barang->kode_barang . '.png';
        // $qrCode->writeFile(public_path($filePath));

        // // Simpan path QR code ke dalam database
        // $barang->barcode = $filePath;

        // $barang->save();

        // return response()->json(['message' => 'Barang berhasil ditambahkan', 'data' => $barang], 201);
    }

    public function show($id)
    {
        $barang = Barangs::findOrFail($id);
        return response()->json($barang);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ruangan_id' => 'required|exists:ruangans,id',
            'user_id' => 'required|exists:users,id',
            'jurusan_id' => 'required|exists:jurusans,id',
            'nama_barang' => 'sometimes|required|string',
            'spesifikasi' => 'nullable|string',
            'pengadaan' => 'nullable|date',
            'jenis_barang' => 'sometimes|required|in:barang sekolah,barang jurusan',
            'kategori_barang' => 'sometimes|required|in:barang inventaris,barang habis pakai',
            'kuantitas' => 'nullable|integer',
            'keterangan_barang' => 'nullable|string',
            'keadaan_barang' => 'nullable|in:baik,rusak ringan,rusak sedang,rusak berat',
            // 'barcode' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $barang = Barangs::findOrFail($id);
        $barang->update($request->all());

        // Mengambil nama ruangan
        $namaRuangan = Ruangans::find($request->ruangan_id)->nama_ruangan;

        // // Membuat konten QR code
        $barcodeData = [
            'Kode Barang: ' . $request->kode_barang,
            'Nama Barang: ' . $request->nama_barang,
            'Spesifikasi: ' . $request->spesifikasi,
            'Pengadaan: ' . $request->pengadaan,
            'Jenis barang: ' . $request->jenis_barang,
            'Kategori Barang: ' . $request->kategori_barang,
            'Kuantitas: ' . $request->kuantitas,
            'Keterangan Barang: ' . $request->keterangan_barang,
            'Keadaan Barang: ' . $request->keadaan_barang,
            'Letak Barang: ' . $namaRuangan,
        ];

        $barcodeString = implode("\n", $barcodeData);
        // Simpan barcode baru
        $barang->barcode = $barcodeString;
        $barang->save();

        // // Menghasilkan QR code dari konten
        // $qrCode = QrCode::size(300)->generate(json_encode($qrCodeContent));

        // // Menghapus QR code lama jika ada
        // if (file_exists(public_path($barang->barcode))) {
        //     unlink(public_path($barang->barcode));
        // }

        // // Simpan QR code ke dalam file gambar atau tempat penyimpanan lainnya
        // $filePath = 'uploads/qrcodes/' . $barang->kode_barang . '.png';
        // $qrCode->writeFile(public_path($filePath));

        // // Simpan path QR code ke dalam database
        // $barang->barcode = $filePath;
        // $barang->save();

        return response()->json(['message' => 'Data barang berhasil diperbarui', 'barang' => $barang], 200);
    }

    public function destroy($id)
    {
        $barang = Barangs::findOrFail($id);
        $barang->delete();

        return response()->json(['message' => 'Barang berhasil dihapus'], 200);
    }
}
