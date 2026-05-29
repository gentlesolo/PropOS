<?php

namespace App\Domain\AI\Contracts;

interface AiCompletionServiceInterface
{
    /**
     * Generate a completion based on a system prompt and user input.
     */
    public function generate(string $systemPrompt, string $userPrompt, array $context = []): string;

    /**
     * Have a multi-turn conversation with tool/function calling support.
     */
    public function chat(array $messages, array $tools = []): array;
}
