<?php

namespace App\Jobs;

use App\Models\ProdukImage;
use App\Services\ImageUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The uploaded file
     *
     * @var UploadedFile
     */
    public $file;

    /**
     * Product ID
     *
     * @var int
     */
    public $produkId;

    /**
     * Whether this should be the primary image
     *
     * @var bool
     */
    public $isPrimary;

    /**
     * Create a new job instance.
     *
     * @param UploadedFile $file
     * @param int $produkId
     * @param bool $isPrimary
     * @return void
     */
    public function __construct(UploadedFile $file, int $produkId, bool $isPrimary = false)
    {
        $this->file = $file;
        $this->produkId = $produkId;
        $this->isPrimary = $isPrimary;
    }

    /**
     * Execute the job.
     *
     * @param ImageUploadService $service
     * @return void
     */
    public function handle(ImageUploadService $service): void
    {
        try {
            // Check if product still exists
            $produk = \App\Models\Produk::find($this->produkId);
            if (!$produk) {
                Log::warning('Product not found, skipping image processing', [
                    'produk_id' => $this->produkId,
                ]);
                return;
            }

            // Check max images limit
            $currentCount = ProdukImage::where('produk_id', $this->produkId)->count();
            if ($currentCount >= 10) {
                Log::info('Max images reached, skipping upload', [
                    'produk_id' => $this->produkId,
                    'current_count' => $currentCount,
                ]);
                return;
            }

            // Process and store image
            $image = $service->processAndStore($this->file, $this->produkId, $this->isPrimary);

            Log::info('Image processed successfully', [
                'produk_id' => $this->produkId,
                'image_id' => $image->id,
                'path' => $image->path,
                'is_primary' => $this->isPrimary,
            ]);
        } catch (Throwable $e) {
            Log::error('Image processing failed', [
                'produk_id' => $this->produkId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Image processing job failed permanently', [
            'produk_id' => $this->produkId,
            'original_name' => $this->file->getClientOriginalName(),
            'error' => $exception->getMessage(),
        ]);
    }
}
