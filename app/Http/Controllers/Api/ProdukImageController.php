<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\ProdukImage;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdukImageController extends Controller
{
    protected ImageUploadService $uploadService;

    public function __construct(ImageUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Store multiple images for a product
     *
     * @param Request $request
     * @param Produk $produk
     * @return JsonResponse
     */
    public function store(Request $request, Produk $produk): JsonResponse
    {
        // Check current image count
        $currentCount = ProdukImage::where('produk_id', $produk->id)->count();
        
        if ($currentCount >= 10) {
            return response()->json([
                'error' => 'Maksimal 10 gambar per produk',
                'current_count' => $currentCount,
            ], 422);
        }

        // Validate request
        $maxUpload = 10 - $currentCount;
        
        $validated = $request->validate([
            'images' => 'required|array|max:' . $maxUpload,
            'images.*' => 'file|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
        ]);

        // Dispatch images to queue for processing
        $images = $request->file('images');
        $this->uploadService->uploadMultiple($images, $produk->id);

        return response()->json([
            'message' => 'Gambar sedang diproses',
            'queue' => true,
            'count' => count($images),
        ]);
    }

    /**
     * Delete an image
     *
     * @param ProdukImage $image
     * @return JsonResponse
     */
    public function destroy(ProdukImage $image): JsonResponse
    {
        $this->uploadService->delete($image);

        return response()->json([
            'message' => 'Gambar berhasil dihapus',
        ]);
    }

    /**
     * Set an image as primary
     *
     * @param ProdukImage $image
     * @return JsonResponse
     */
    public function setPrimary(ProdukImage $image): JsonResponse
    {
        $this->uploadService->setPrimary($image);

        return response()->json([
            'message' => 'Gambar utama berhasil diubah',
            'image_id' => $image->id,
        ]);
    }

    /**
     * Reorder images
     *
     * @param Request $request
     * @param Produk $produk
     * @return JsonResponse
     */
    public function reorder(Request $request, Produk $produk): JsonResponse
    {
        $validated = $request->validate([
            'images' => 'required|array',
            'images.*' => 'integer|exists:produk_images,id',
        ]);

        $this->uploadService->reorder($produk->id, $validated['images']);

        return response()->json([
            'message' => 'Urutan gambar berhasil diperbarui',
        ]);
    }

    /**
     * Get all images for a product
     *
     * @param Produk $produk
     * @return JsonResponse
     */
    public function index(Produk $produk): JsonResponse
    {
        $images = ProdukImage::where('produk_id', $produk->id)
            ->ordered()
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                    'original_name' => $image->original_name,
                    'size' => $image->size,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                    'created_at' => $image->created_at,
                ];
            });

        return response()->json([
            'images' => $images,
            'count' => $images->count(),
        ]);
    }
}
