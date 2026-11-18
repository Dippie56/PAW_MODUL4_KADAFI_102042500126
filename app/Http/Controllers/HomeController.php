<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman katalog buku dengan fitur pencarian, filter, sorting, dan paginasi.
     */
    public function index(Request $request)
    {
        // Memulai query builder dari model Buku
        $query = Buku::query();

        // 1. Pencarian berdasarkan judul, penulis, atau penerbit jika ada input 'search'
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                  ->orWhere('penulis', 'like', '%' . $search . '%')
                  ->orWhere('penerbit', 'like', '%' . $search . '%');
            });
        }

        // 2. Tambahkan filter penerbit dan filter ketersediaan stok
        if ($request->filled('penerbit')) {
            $query->where('penerbit', $request->penerbit);
        }

        if ($request->filled('status')) {
            if ($request->status === 'tersedia') {
                $query->where('stok', '>', 0);
            } elseif ($request->status === 'habis') {
                $query->where('stok', '<=', 0);
            }
        }

        // 3. Tambahkan pengurutan data berdasarkan input 'sort'
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'judul':
                    $query->orderBy('judul', 'asc');
                    break;
                case 'tahun':
                    $query->orderBy('tahun_terbit', 'desc'); // Mengurutkan dari tahun terbaru
                    break;
                case 'stok':
                    $query->orderBy('stok', 'desc'); // Mengurutkan dari stok terbanyak
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            // Default pengurutan (buku terbaru yang ditambahkan ke sistem)
            $query->latest();
        }

        // 4. Lakukan paginasi 8 data dengan withQueryString() agar filter/search tidak hilang saat pindah halaman
        $buku = $query->paginate(8)->withQueryString();

        // 5. Ambil list penerbit unik untuk opsi dropdown filter (membuang data ganda/null)
        $listPenerbit = Buku::select('penerbit')
                            ->distinct()
                            ->whereNotNull('penerbit')
                            ->where('penerbit', '!=', '')
                            ->pluck('penerbit');

       // 6. Return view 'home' dengan data buku dan list penerbit
        return view('home', compact('buku', 'listPenerbit'));
    }

    /**
     * Menampilkan halaman detail informasi sebuah buku untuk publik/mahasiswa.
     */
    public function show(Buku $buku)
    {
        // Mengembalikan view 'home_show' dan membawa data detail buku ($buku)
        return view('home_show', compact('buku'));
    }
}