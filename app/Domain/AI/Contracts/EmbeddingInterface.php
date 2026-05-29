<?php

namespace App\Domain\AI\Contracts;

interface EmbeddingInterface
{
    /**
     * Generate an embedding vector for the given text.
     *
     * @return array<float>
     */
    public function embed(string $text): array;

    /**
     * Generate embedding vectors for a batch of texts.
     *
     * @param array<string> $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts): array;
}
