<?php

namespace App\Application\Marketing\Actions;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\ListingGraphic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateListingGraphicAction
{
    /** Canvas dimensions per format [width, height] */
    private const FORMATS = [
        'square'    => [1080, 1080],
        'landscape' => [1200, 630],
        'story'     => [1080, 1920],
    ];

    /** Ordered font search paths — first found wins */
    private const FONT_PATHS = [
        // Bundled project font (recommended — add resources/fonts/Inter-Bold.ttf)
        'resources/fonts/Inter-Bold.ttf',
        // Windows development
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/arial.ttf',
        // Debian/Ubuntu
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        // macOS
        '/Library/Fonts/Arial Bold.ttf',
        '/System/Library/Fonts/Helvetica.ttc',
    ];

    public function execute(Listing $listing, string $format = 'square'): ListingGraphic
    {
        [$canvasW, $canvasH] = self::FORMATS[$format] ?? self::FORMATS['square'];

        $property = $listing->property;
        $agency   = $listing->agency;

        // 1. Create canvas
        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // 2. Fill with agency primary colour as base background
        $bgHex      = ltrim($agency->primary_color ?? '#1E40AF', '#');
        $bgR        = hexdec(substr($bgHex, 0, 2));
        $bgG        = hexdec(substr($bgHex, 2, 2));
        $bgB        = hexdec(substr($bgHex, 4, 2));
        $bgColor    = imagecolorallocate($canvas, $bgR, $bgG, $bgB);
        imagefill($canvas, 0, 0, $bgColor);

        // 3. Composite cover photo
        $coverPhoto = $listing->coverPhoto ?? $listing->media()->where('file_type', 'image')->first();
        if ($coverPhoto) {
            $photoPath = storage_path('app/public/' . $coverPhoto->file_path);
            if (file_exists($photoPath)) {
                $this->compositePhoto($canvas, $photoPath, $canvasW, $canvasH, $format);
            }
        }

        // 4. Gradient overlay (bottom portion)
        $this->drawGradientOverlay($canvas, $canvasW, $canvasH, $bgR, $bgG, $bgB, $format);

        // 5. Top-left agency name strip
        $this->drawAgencyStrip($canvas, $agency, $canvasW, $bgR, $bgG, $bgB);

        // 6. Property details (bottom panel)
        $this->drawPropertyDetails($canvas, $listing, $property, $agency, $canvasW, $canvasH, $format);

        // 7. Save
        $dir      = "listing-graphics/{$listing->id}";
        $filename = "{$format}-" . now()->format('YmdHis') . '.jpg';
        $fullDir  = storage_path("app/public/{$dir}");

        if (! is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $fullPath = "{$fullDir}/{$filename}";
        imagejpeg($canvas, $fullPath, 90);
        imagedestroy($canvas);

        $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

        return ListingGraphic::updateOrCreate(
            ['listing_id' => $listing->id, 'format' => $format, 'channel' => $this->formatToChannel($format)],
            [
                'agency_id' => $listing->agency_id,
                'file_path' => "{$dir}/{$filename}",
                'width'     => $canvasW,
                'height'    => $canvasH,
                'file_size' => $fileSize,
            ]
        );
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function compositePhoto(\GdImage $canvas, string $photoPath, int $cW, int $cH, string $format): void
    {
        try {
            $mime = mime_content_type($photoPath);
            $src  = match (true) {
                str_contains($mime, 'jpeg') => imagecreatefromjpeg($photoPath),
                str_contains($mime, 'png')  => imagecreatefrompng($photoPath),
                str_contains($mime, 'webp') => imagecreatefromwebp($photoPath),
                default                     => null,
            };

            if (! $src) {
                return;
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);

            // Cover-fit: scale to fill the canvas, then centre-crop
            $scale  = max($cW / $srcW, $cH / $srcH);
            $scaledW = (int) ($srcW * $scale);
            $scaledH = (int) ($srcH * $scale);
            $offsetX = (int) (($scaledW - $cW) / 2);
            $offsetY = (int) (($scaledH - $cH) / 2);

            // For story format, show upper portion of image (more sky/exterior)
            if ($format === 'story') {
                $offsetY = 0;
            }

            imagecopyresampled($canvas, $src, 0, 0, $offsetX, $offsetY, $cW, $cH, (int)($cW / $scale), (int)($cH / $scale));
            imagedestroy($src);
        } catch (\Exception $e) {
            Log::warning('Graphic compositor: photo load failed', ['error' => $e->getMessage()]);
        }
    }

    private function drawGradientOverlay(\GdImage $canvas, int $cW, int $cH, int $r, int $g, int $b, string $format): void
    {
        // Gradient covers bottom 45% of canvas (more for story)
        $gradientStart = $format === 'story' ? (int) ($cH * 0.55) : (int) ($cH * 0.52);
        $gradientH     = $cH - $gradientStart;

        for ($y = $gradientStart; $y < $cH; $y++) {
            $progress = ($y - $gradientStart) / $gradientH;
            $alpha    = (int) (80 + $progress * 47); // 80–127 (max opacity = 127 in GD)
            $color    = imagecolorallocatealpha($canvas, $r, $g, $b, 127 - $alpha);
            imageline($canvas, 0, $y, $cW, $y, $color);
        }
    }

    private function drawAgencyStrip(\GdImage $canvas, Agency $agency, int $cW, int $r, int $g, int $b): void
    {
        $stripH    = 52;
        $stripColor = imagecolorallocatealpha($canvas, $r, $g, $b, 20); // near-opaque

        imagefilledrectangle($canvas, 0, 0, $cW, $stripH, $stripColor);

        $white  = imagecolorallocate($canvas, 255, 255, 255);
        $name   = strtoupper($agency->name ?? 'AGENCY');
        $font   = $this->findFont();

        if ($font) {
            imagettftext($canvas, 15, 0, 24, 34, $white, $font, $name);
        } else {
            imagestring($canvas, 5, 24, 16, $name, $white);
        }
    }

    private function drawPropertyDetails(
        \GdImage $canvas,
        Listing  $listing,
        $property,
        Agency   $agency,
        int      $cW,
        int      $cH,
        string   $format,
    ): void {
        $white  = imagecolorallocate($canvas, 255, 255, 255);
        $yellow = imagecolorallocate($canvas, 255, 214, 0);
        $light  = imagecolorallocate($canvas, 220, 235, 255);
        $font   = $this->findFont();

        $panelH = $format === 'story' ? 380 : 220;
        $panelY = $cH - $panelH;

        // Price — large and bold
        $price     = number_format((float) $listing->listing_price);
        $currency  = $agency->currency ?? 'NGN';
        $priceText = "{$currency} {$price}";
        $pricePx   = $format === 'story' ? 52 : ($format === 'square' ? 44 : 38);
        $priceY    = $panelY + ($format === 'story' ? 58 : 46);

        if ($font) {
            imagettftext($canvas, $pricePx, 0, 30, $priceY, $yellow, $font, $priceText);
        } else {
            imagestring($canvas, 5, 30, $priceY - 12, $priceText, $yellow);
        }

        // Address
        $address   = ($property->address_line_1 ?? '') . ', ' . ($property->city ?? '');
        $addrPx    = $format === 'story' ? 28 : 22;
        $addrY     = $priceY + ($format === 'story' ? 66 : 52);

        if ($font) {
            imagettftext($canvas, $addrPx, 0, 30, $addrY, $white, $font, $this->truncate($address, 42));
        } else {
            imagestring($canvas, 4, 30, $addrY - 8, $this->truncate($address, 42), $white);
        }

        // Specs line: beds · baths · sqm
        $specs = collect([
            $property->bedrooms   ? "{$property->bedrooms} Beds"   : null,
            $property->bathrooms  ? "{$property->bathrooms} Baths"  : null,
            $property->floor_area_sqm ? "{$property->floor_area_sqm} m²" : null,
            ucfirst($property->property_type ?? ''),
        ])->filter()->implode('  ·  ');

        $specsPx = $format === 'story' ? 22 : 17;
        $specsY  = $addrY + ($format === 'story' ? 52 : 38);

        if ($font) {
            imagettftext($canvas, $specsPx, 0, 30, $specsY, $light, $font, $specs);
        } else {
            imagestring($canvas, 3, 30, $specsY - 6, $specs, $light);
        }

        // Mandate badge (top-right)
        $badgeText = strtoupper($listing->mandate_type ?? 'FOR SALE');
        $badgePx   = 14;
        $badgeW    = strlen($badgeText) * 10 + 24;
        $badgeX    = $cW - $badgeW - 24;
        $badgeY    = $cH - ($format === 'story' ? 340 : 188);

        $hexBrand = ltrim($agency->primary_color ?? '#1E40AF', '#');
        $bR = hexdec(substr($hexBrand, 0, 2));
        $bG = hexdec(substr($hexBrand, 2, 2));
        $bB = hexdec(substr($hexBrand, 4, 2));
        $badgeBg = imagecolorallocate($canvas, $bR, $bG, $bB);

        imagefilledrectangle($canvas, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + 30, $badgeBg);
        if ($font) {
            imagettftext($canvas, $badgePx, 0, $badgeX + 12, $badgeY + 21, $white, $font, $badgeText);
        } else {
            imagestring($canvas, 3, $badgeX + 8, $badgeY + 7, $badgeText, $white);
        }
    }

    private function findFont(): ?string
    {
        foreach (self::FONT_PATHS as $path) {
            $resolved = str_starts_with($path, '/') || str_starts_with($path, 'C:')
                ? $path
                : base_path($path);

            if (file_exists($resolved)) {
                return $resolved;
            }
        }
        return null;
    }

    private function truncate(string $text, int $maxChars): string
    {
        return mb_strlen($text) > $maxChars
            ? mb_substr($text, 0, $maxChars - 1) . '…'
            : $text;
    }

    private function formatToChannel(string $format): string
    {
        return match ($format) {
            'square'    => 'instagram',
            'landscape' => 'facebook',
            'story'     => 'instagram',
            default     => 'general',
        };
    }
}
