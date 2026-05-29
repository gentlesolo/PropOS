<?php

namespace App\Infrastructure\AI;

use App\Domain\AI\Contracts\TextGenerationInterface;
use App\Infrastructure\AI\DeepSeek\DeepSeekTextGeneration;
use App\Infrastructure\AI\OpenAi\OpenAiTextGeneration;
use InvalidArgumentException;

class AiServiceManager
{
    /**
     * Resolve the text generation service for a provider.
     */
    public function textGeneration(?string $provider = null): TextGenerationInterface
    {
        $provider = $provider ?? config('ai.default', 'openai');

        return match ($provider) {
            'openai' => new OpenAiTextGeneration(),
            'deepseek' => new DeepSeekTextGeneration(),
            default => throw new InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }
}
