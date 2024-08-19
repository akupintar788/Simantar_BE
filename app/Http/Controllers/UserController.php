<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users)->header('Access-Control-Allow-Origin', 'http://localhost:3000');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'jurusan_id' => 'required|exists:jurusans,id',
            'username' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
            'nama_user' => 'required|string',
            'nip' => 'required|string',
            'no_hp' => 'nullable|string',
            'ttd' => 'nullable|image|mimes:png|max:2048', // Format PNG, maksimum ukuran 2MB
            'role' => 'required|string|in:admin,sarpras,ketua_program,kepsek,guru,siswa',
        ], [
            'jurusan_id.required' => 'Jurusan harus dipilih.',
            'username.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'nama_user.required' => 'Nama user harus diisi.',
            'nip.required' => 'NIP harus diisi.',
            // 'email.required' => 'Email harus diisi.',
            // 'email.email' => 'Format email tidak valid.',
            'ttd.image' => 'TTD harus berupa file gambar.',
            'ttd.mimes' => 'Format TTD harus PNG.',
            'ttd.max' => 'Ukuran TTD tidak boleh lebih dari 2MB.',
            'role.required' => 'Role harus dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        try {
            $data = $request->all();

            // Jika ada file gambar yang diunggah
            if ($request->hasFile('ttd')) {
                // Simpan gambar ke direktori yang telah ditentukan
                $file = $request->file('ttd');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = '/uploads/ttd/' . $fileName;
                $file->move(public_path('uploads/ttd'), $fileName);

                // Simpan path penyimpanan gambar di dalam kolom 'ttd' di database
                $data['ttd'] = $filePath;

                // URL gambar untuk ditampilkan di frontend
                $imageUrl = url($filePath);

            }

            // Simpan data user ke dalam database
            // Buat pengguna baru dengan data yang divalidasi
            $user = User::create($data);

            // Kembalikan respon JSON
            return response()->json([
                'message' => 'Data user berhasil ditambahkan',
                'user' => $user,
                'ttd_url' => isset($data['ttd']) ? url($data['ttd']) : null,
            ], 201);
        } catch (\Exception $e) {
            // Tangani pengecualian jika terjadi kesalahan dalam penyimpanan data
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user)->header('Access-Control-Allow-Origin', 'http://localhost:3000');
    }

    public function showttd($filename)
    {
        try {
            // Ambil file gambar dari storage atau direktori yang sesuai
            $file = public_path() . $filename;
    
            // Periksa apakah file gambar ada
            if (!file_exists($file)) {
                // Jika file tidak ditemukan, kembalikan respons error 404 berserta informasi tambahan
                return response()->json(['error' => 'File TTD tidak ditemukan', 'file_path' => $file], 404);
            }
    
            // Baca isi file gambar
            $fileContents = file_get_contents($file);
    
            // Kembalikan file gambar sebagai respons dengan tipe konten yang benar
            return response($fileContents, 200)->header('Content-Type', 'image/png'); // Sesuaikan dengan tipe gambar yang digunakan
        } catch (\Exception $e) {
            // Tangani pengecualian jika terjadi kesalahan saat mengambil atau mengirim file gambar
            return response()->json(['error' => 'Terjadi kesalahan saat memuat file TTD', 'exception_message' => $e->getMessage()], 500);
        }
    }
    








    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'jurusan_id' => 'required|exists:jurusans,id',
            'username' => 'sometimes|required|string|unique:users,username,'.$id,
            'nama_user' => 'sometimes|required|string',
            'nip' => 'sometimes|required|string',
            'no_hp' => 'sometimes|string',
            'ttd' => 'nullable|image|mimes:png|max:2048',
            'role' => 'sometimes|required|string|in:admin,sarpras,ketua_program,kepsek,guru,siswa',
        ]);

        $user = User::findOrFail($id);
        if ($request->has('password')) {
            $request->validate([
                'password' => 'required|string|min:6',
            ]);
            // Enkripsi dan simpan password baru jika ada input password baru
            $user->password = bcrypt($request->password);
        } else {
            // Biarkan password tetap sama jika tidak ada input password baru
            $user->password = $user->password;
        }

        // Jika ada file gambar yang diunggah
        if ($request->hasFile('ttd')) {
            // Simpan gambar ke direktori yang telah ditentukan
            $file = $request->file('ttd');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = '/uploads/ttd/' . $fileName;
            $file->move(public_path('uploads/ttd'), $fileName);

            // Simpan path penyimpanan gambar di dalam kolom 'ttd' di database
            $user->ttd = $filePath;

            // URL gambar untuk ditampilkan di frontend
            $imageUrl = url($filePath);
        } else {
            // Jika tidak ada file gambar yang diunggah, gunakan gambar TTD yang lama
            $imageUrl = $user->ttd;
        }


        $user->jurusan_id = $request->jurusan_id;
        $user->username = $request->username;
        $user->nama_user = $request->nama_user;
        $user->nip = $request->nip;
        $user->no_hp = $request->no_hp;
        $user->role = $request->role;

        // Coba simpan perubahan data user
        if ($user->save()) {
            return response()->json(['message' => 'Data user berhasil diperbarui', 'user' => $user, 'ttd_url' => $imageUrl], 200);
        } else {
            return response()->json(['message' => 'Gagal memperbarui data user'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        if ($user) {
            return response()->json(['message' => 'Data user berhasil dihapus'], 200);
        } else {
            return response()->json(['message' => 'Gagal menghapus data user'], 400);
        }
    }

    public function updateSettings(Request $request, $id)
{
    $request->validate([
        'username' => 'sometimes|string|unique:users,username,'.$id,
        'nama_user' => 'required|string',
        'nip' => 'sometimes|string',
        'no_hp' => 'nullable|string',
        'password' => 'sometimes|string|min:6',
        'newPassword' => 'nullable|string|min:6|different:password',
        'confirmPassword' => 'nullable|required_with:newPassword|same:newPassword|min:6',
    ], [
        'username.required' => 'Username harus diisi.',
        'nama_user.required' => 'Nama user harus diisi.',
        'password.required' => 'Password harus diisi.',
        'password.min' => 'Password minimal terdiri dari 6 karakter.',
        'newPassword.min' => 'Password baru minimal terdiri dari 6 karakter.',
        'newPassword.different' => 'Password baru harus berbeda dengan password lama.',
        'confirmPassword.required_with' => 'Konfirmasi password baru harus diisi.',
        'confirmPassword.same' => 'Konfirmasi password baru harus sama dengan password baru.',
        'confirmPassword.min' => 'Konfirmasi password baru minimal terdiri dari 6 karakter.',
    ]);

    try {
        $user = User::findOrFail($id);

        $data = $request->only(['username', 'nama_user', 'no_hp']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->input('password'));
        }

        if ($request->filled('newPassword')) {
            $data['password'] = bcrypt($request->input('newPassword'));
        }

        $user->update($data);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui',
            'user' => $user
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat memperbarui data pengguna',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
