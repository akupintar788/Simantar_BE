<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Peminjamans;
use Illuminate\Console\Command;
use App\Models\PeminjamanBarang;

class ExportPeminjamanToCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:peminjaman-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all data from Peminjamans model
        $peminjamanData = Peminjamans::with(['user', 'peminjaman_barangs.barang'])->get();

        // Process data
        $dataset = $peminjamanData->map(function($peminjaman) {
            return $peminjaman->peminjaman_barangs->map(function($peminjamanBarang) {
                $barang = $peminjamanBarang->barang;

                // Calculate usage frequency as total duration of all loans
                $usageFrequency = PeminjamanBarang::where('barang_id', $barang->id)
                    ->whereHas('peminjaman', function ($query) {
                        $query->whereNotNull('tgl_pengembalian');
                    })
                    ->get()
                    ->reduce(function($carry, $item) {
                        $start = Carbon::parse($item->tgl_peminjaman);
                        $end = Carbon::parse($item->tgl_pengembalian);
                        return $carry + $start->diffInDays($end);
                    }, 0);

                // You can replace this null value with the actual logic to get the last maintenance date if available
                $lastMaintenanceDate = null;

                return [
                    'id_barang' => $barang->id,
                    'jumlah_peminjaman' => PeminjamanBarang::where('barang_id', $barang->id)->count(),
                    'jumlah_dipinjam' => $peminjamanBarang->jumlah_dipinjam,
                    'keadaan_barang' => $barang->keadaan_barang,
                    'age' => Carbon::parse($barang->pengadaan)->diffInDays(now()),
                    'last_maintenance_date' => $lastMaintenanceDate,
                    'usage_frequency' => $usageFrequency,
                    'maintenance_needed' => 0 // Set default to 0. Update with your own logic if necessary.
                ];
            });
        })->flatten(1)->toArray();

        // Define the CSV filename
        $csvFileName = 'peminjaman_dataset.csv';
        $headers = ['id_barang', 'jumlah_peminjaman', 'jumlah_dipinjam', 'keadaan_barang', 'age', 'last_maintenance_date', 'usage_frequency', 'maintenance_needed'];

        // Create a file handle
        $handle = fopen(storage_path('app/' . $csvFileName), 'w');
        fputcsv($handle, $headers);

        // Write the data to the CSV file
        foreach ($dataset as $row) {
            fputcsv($handle, $row);
        }

        // Close the file handle
        fclose($handle);

        $this->info("Dataset created successfully in $csvFileName");
    }
}
