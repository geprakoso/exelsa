<?php

namespace App\Services;

use App\Models\ProdukImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadService
{
    protected string $disk;
    protected ImageManager $manager;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'public');
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Upload multiple images for a product
     *
     * @param array $files Array of UploadedFile
     * @param int $produkId Product ID
     * @return Collection Collection of ProdukImage
     */
    public function uploadMultiple(array $files, int $produkId): Collection
    {
        $uploaded = collect();
        $currentCount = ProdukImage::where('produk_id', $produkId)->count();

        foreach ($files as $index => $file) {
            // Check max limit (10 images)
            if ($currentCount + $index >= 10) {
                break;
            }

            $isPrimary = ($index === 0 && $currentCount === 0);
            $uploaded->push($this->processAndStore($file, $produkId, $isPrimary));
        }

        return $uploaded;
    }

    /**
     * Process and store a single image
     *
     * @param UploadedFile $file
     * @param int $produkId
     * @param bool $isPrimary
     * @return ProdukImage
     */
    public function processAndStore(UploadedFile $file, int $produkId, bool $isPrimary = false): ProdukImage
    {
        // Generate folder structure: produk/{id}/YYYY/MM
        $folder = "produk/{$produkId}/" . date('Y/m');
        $basename = Str::uuid()->toString();
        $filename = "{$basename}.webp";
        $path = "{$folder}/{$filename}";

        // Process image: Convert to WebP with 85% quality (Intervention Image v4)
        $image = $this->manager->decodePath($file->getPathname());
        $encoded = $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: 85));

        // Store to disk
        Storage::disk($this->disk)->put($path, (string) $encoded);

        // Get file size
        $size = strlen((string) $encoded);

        // Save to database
        return ProdukImage::create([
            'produk_id' => $produkId,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $this->disk,
            'path' => $path,
            'size' => $size,
            'is_primary' => $isPrimary,
            'sort_order' => ProdukImage::where('produk_id', $produkId)->count(),
        ]);
    }

    /**
     * Delete an image and its file
     *
     * @param ProdukImage $image
     * @return void
     */
    public function delete(ProdukImage $image): void
    {
        // Delete file from storage
        Storage::disk($image->disk)->delete($image->path);

        // Delete from database
        $image->delete();

        // If this was primary, set the first remaining image as primary
        if ($image->is_primary) {
            $firstImage = ProdukImage::where('produk_id', $image->produk_id)
                ->first();
            
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }
    }

    /**
     * Set an image as primary and unset others
     *
     * @param ProdukImage $image
     * @return void
     */
    public function setPrimary(ProdukImage $image): void
    {
        // Unset primary for all images of this product
        ProdukImage::where('produk_id', $image->produk_id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $image->update(['is_primary' => true]);
    }

    /**
     * Reorder images
     *
     * @param int $produkId
     * @param array $imageIds Ordered array of image IDs
     * @return void
     */
    public function reorder(int $produkId, array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            ProdukImage::where('id', $imageId)
                ->where('produk_id', $produkId)
                ->update(['sort_order' => $index]);
        }
    }
}
