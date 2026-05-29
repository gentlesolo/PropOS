<?php

namespace App\Domain\AI\Contracts;

interface TextGenerationInterface
{
    /**
     * Generate text from a prompt.
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Generate structured JSON from a prompt using a schema.
     */
    public function generateStructured(string $prompt, array $schema, array $options = []): array;

    /**
     * Stream response tokens from a prompt.
     *
     * @return iterable<string>
     */
    public function stream(string $prompt, array $options = []): iterable;
}
