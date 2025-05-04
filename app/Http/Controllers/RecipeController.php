<?php

namespace App\Http\Controllers;

use App\Models\AlatResep;
use App\Models\BahanResep;
use App\Models\LangkahResep;
use App\Models\Rating;
use App\Models\Resep;
use App\Models\Saved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function showRecipeNewest()
    {
        $items = DB::table('resep')
            ->leftJoin('rating', 'resep.id_resep', '=', 'rating.id_resep')
            ->select('resep.id_resep', 'resep.judul', 'resep.gambar', DB::raw('CAST(ROUND(AVG(rating.bintang), 1) AS float) as bintang'))
            ->groupBy('resep.id_resep', 'resep.judul', 'resep.gambar')
            ->orderBy('resep.created_at', 'desc')
            ->get();
        return response()->json([
            'data' => $items
        ], 200);
    }
    public function showRecipeBest()
    {
        $items = DB::table('resep')
            ->leftJoin('rating', 'resep.id_resep', '=', 'rating.id_resep')
            ->select('resep.id_resep', 'resep.judul', 'resep.gambar', DB::raw('CAST(ROUND(AVG(rating.bintang), 1) AS float) as bintang'))
            ->groupBy('resep.id_resep', 'resep.judul', 'resep.gambar')
            ->orderBy('bintang', 'desc')->limit(3)
            ->get();
        return response()->json([
            'data' => $items
        ], 200);
    }
    public function showRecipeSelf($id_user)
    {
        $items = DB::table('resep')
            ->join('users', 'resep.id_user', '=', 'users.id_user')
            ->leftJoin('rating', 'resep.id_resep', '=', 'rating.id_resep')
            ->where('resep.id_user', $id_user)
            ->select(
                'resep.id_resep',
                'resep.judul',
                'resep.gambar',
                DB::raw('CAST(ROUND(AVG(rating.bintang), 1) AS float) as bintang')
            )
            ->groupBy(
                'resep.id_resep',
                'resep.judul',
                'resep.gambar',
            )
            ->get();
        return response()->json([
            'data' => $items,
        ], 200);
    }
    public function showDetailRecipe($id_resep)
    {
        // Get base recipe data
        $resep = DB::table('resep')
            ->leftJoin('rating', 'resep.id_resep', '=', 'rating.id_resep')
            ->join('users', 'resep.id_user', '=', 'users.id_user')
            ->where('resep.id_resep', $id_resep)
            ->select(
                'resep.*',
                'users.username',
                DB::raw('CAST(ROUND(AVG(rating.bintang), 1) AS float) as bintang')
            )
            ->groupBy(
                'resep.id_resep',
                'resep.judul',
                'resep.gambar',
                'resep.deskripsi',
                'resep.id_user',
                'resep.created_at',
                'resep.updated_at',
                'users.username',
            )
            ->first();

        // Get related data separately
        $alat = DB::table('alat_resep')
            ->where('id_resep', $id_resep)
            ->get();

        $bahan = DB::table('bahan_resep')
            ->where('id_resep', $id_resep)
            ->get();

        $langkah = DB::table('langkah_resep')
            ->where('id_resep', $id_resep)
            ->orderBy('urutan')
            ->get();

        return response()->json([
            'data' => [
                'resep' => $resep,
                'alat' => $alat,
                'bahan' => $bahan,
                'langkah' => $langkah
            ]
        ], 200);
    }

    public function addRecipe(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'gambar' => 'required|image|max:2048',
            'id_user' => 'required',
            'bahan' => 'required|json',
            'alat' => 'required|json',
            'langkah' => 'required|json',
        ]);

        $imagePath = $request->file('gambar')->store('public/recipes');

        $recipe = Resep::create([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'id_user' => $validated['id_user'],
            'gambar' => str_replace('public/', '', $imagePath),
        ]);

        $bahanList = json_decode($validated['bahan'], true);
        foreach ($bahanList as $bahan) {
            BahanResep::create([
                'id_resep' => $recipe->id_resep,
                'nama_bahan' => $bahan,
            ]);
        }

        $alatList = json_decode($validated['alat'], true);
        foreach ($alatList as $alat) {
            AlatResep::create([
                'id_resep' => $recipe->id_resep,
                'nama_alat' => $alat,
            ]);
        }

        $langkahList = json_decode($validated['langkah'], true);
        foreach ($langkahList as $index => $langkah) {
            LangkahResep::create([
                'id_resep' => $recipe->id_resep,
                'urutan' => $index + 1,
                'cara_langkah' => $langkah,
            ]);
        }

        return response()->json(['message' => 'Upload berhasil'], 200);
    }
    public function showSavedRecipe($id_user)
    {
        $items = DB::table('saved')
            ->join('users', 'saved.id_user', '=', 'users.id_user')
            ->join('resep', 'saved.id_resep', '=', 'resep.id_resep')
            ->leftJoin('rating', 'resep.id_resep', '=', 'rating.id_resep')
            ->where('saved.id_user', $id_user)
            ->select(
                'resep.id_resep',
                'resep.judul',
                'resep.gambar',
                DB::raw('CAST(ROUND(AVG(rating.bintang), 1) AS float) as bintang')
            )
            ->groupBy(
                'resep.id_resep',
                'resep.judul',
                'resep.gambar',
            )
            ->get();
        return response()->json([
            'data' => $items,
        ], 200);
    }

    public function showRecipeSearch($kunci_cari)
    {
        $items = DB::table('resep')
            ->leftJoin('rating', 'resep.id_resep', '=', 'rating.id_resep')
            ->select('resep.id_resep', 'resep.judul', 'resep.gambar', DB::raw('CAST(ROUND(AVG(rating.bintang), 1) AS float) as bintang'))
            ->groupBy('resep.id_resep', 'resep.judul', 'resep.gambar')
            ->where('resep.judul', 'LIKE', '%' . $kunci_cari . '%')
            ->orderBy('bintang', 'desc')
            ->get();
        return response()->json([
            'data' => $items
        ], 200);
    }
    public function updateResep(Request $request, $id_resep)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'bahan' => 'required|json',
            'alat' => 'required|json',
            'langkah' => 'required|json',
        ]);

        try {
            DB::beginTransaction();

            $resep = Resep::findOrFail($id_resep);

            if ($resep->id_user != auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengubah resep ini'
                ], 403);
            }

            $resep->judul = $request->judul;
            $resep->deskripsi = $request->deskripsi;

            if ($request->hasFile('gambar')) {
                if ($resep->gambar && Storage::exists($resep->gambar)) {
                    Storage::delete($resep->gambar);
                }

                $path = $request->file('gambar')->store('resep_images', 'public');
                $resep->gambar = $path;
            }

            $resep->save();

            $bahan = json_decode($request->bahan, true);
            $resep->bahanReseps()->delete();
            foreach ($bahan as $item) {
                if (!empty(trim($item))) {
                    $resep->bahanReseps()->create(['nama_bahan' => $item]);
                }
            }

            $alat = json_decode($request->alat, true);
            $resep->alatReseps()->delete();
            foreach ($alat as $item) {
                if (!empty(trim($item))) {
                    $resep->alatReseps()->create(['nama_alat' => $item]);
                }
            }

            $langkah = json_decode($request->langkah, true);
            $resep->langkahReseps()->delete();
            foreach ($langkah as $index => $item) {
                if (!empty(trim($item))) {
                    $resep->langkahReseps()->create([
                        'cara_langkah' => $item,
                        'urutan' => $index + 1
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Resep berhasil diperbarui',
                'data' => $resep
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui resep',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteResep($id_resep)
    {
        try {
            DB::beginTransaction();

            // Find the recipe
            $resep = Resep::findOrFail($id_resep);

            // Check if user owns the recipe
            if ($resep->id_user != auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus resep ini'
                ], 403);
            }

            // Delete related data first (to maintain foreign key constraints)
            BahanResep::where('id_resep', $id_resep)->delete();
            AlatResep::where('id_resep', $id_resep)->delete();
            LangkahResep::where('id_resep', $id_resep)->delete();
            Rating::where('id_resep', $id_resep)->delete();

            if ($resep->gambar && Storage::exists($resep->gambar)) {
                Storage::delete($resep->gambar);
            }

            $resep->delete();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Resep berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus resep',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
