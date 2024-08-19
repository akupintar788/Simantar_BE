<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->constrained('jurusans')->cascadeOnDelete();
            $table->string('username');
            $table->string('password');
            $table->string('nama_user');
            $table->string('nip');
            $table->string('no_hp');
            $table->string('ttd')->nullable(); // Untuk menyimpan tanda tangan user (file gambar)
            $table->enum('role', ['admin', 'sarpras', 'ketua_program', 'kepsek', 'guru', 'siswa']);
            $table->timestamp('created_at')->useCurrent(); // Untuk menyimpan tanggal akun dibuat
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
