<?php

namespace App\Domain\AI\Contracts;

interface ImageAnalysisInterface
{
    /**
     * Analyze the quality of a property photo.
     *
     * @return array{quality_score: int, feedback: string}
     */
    public function analyzeQuality(string $imagePath): array;

    /**
     * Generate a natural language description of an image.
     */
    public function describeImage(string $imagePath): string;
}
