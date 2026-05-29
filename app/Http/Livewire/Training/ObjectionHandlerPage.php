<?php

namespace App\Http\Livewire\Training;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use Livewire\Component;

class ObjectionHandlerPage extends Component
{
    public string $objectionCategory = 'price';
    public string $customObjection = '';
    public ?array $response = null;
    public bool $generating = false;
    public array $history = [];

    public array $commonObjections = [
        'price' => [
            'The price is too high.',
            'I can get it cheaper elsewhere.',
            'The asking price doesn\'t match market value.',
            'You\'re asking too much commission.',
        ],
        'timing' => [
            'I want to wait until the market improves.',
            'I need to wait for interest rates to drop.',
            'I\'m not ready to buy/sell yet.',
            'Let me think about it and come back.',
        ],
        'competition' => [
            'Another agent offered me a better deal.',
            'I\'m already working with a different agency.',
            'Why should I use you over your competitors?',
        ],
        'uncertainty' => [
            'The property needs too much work.',
            'I\'m not sure the area is right for me.',
            'What if I can\'t sell when I want to move on?',
            'The market is too uncertain right now.',
        ],
    ];

    public function handleObjection(string $objection, AiCompletionServiceInterface $ai): void
    {
        $this->generating = true;

        $systemPrompt = "You are an expert real estate sales coach. Provide a structured objection-handling response for a real estate agent. Format as JSON with keys: empathy (1 sentence acknowledging the concern), reframe (1-2 sentences shifting perspective), response (2-3 sentences — the actual talking points), close (1 sentence to re-engage). Be specific, confident, and practical.";

        $userPrompt = "Client objection in category '{$this->objectionCategory}': \"{$objection}\". Provide the best response for a real estate agent.";

        $raw = $ai->generate($systemPrompt, $userPrompt);
        $parsed = json_decode($raw, true);

        $this->response = $parsed ?: [
            'empathy' => 'I completely understand that concern — you\'re not alone in feeling this way.',
            'reframe' => 'Let me offer you a different perspective based on what we\'re seeing in the current market.',
            'response' => 'Based on recent comparable sales and our marketing reach, the data supports this position. Let me walk you through the numbers that led us here, and then we can discuss what adjustment, if any, makes sense.',
            'close' => 'Does that help clarify the reasoning, and are you comfortable moving forward from here?',
        ];

        $this->history[] = [
            'objection' => $objection,
            'category' => $this->objectionCategory,
            'response' => $this->response,
        ];

        $this->generating = false;
    }

    public function handleCustomObjection(AiCompletionServiceInterface $ai): void
    {
        $this->validate(['customObjection' => 'required|string|min:5|max:500']);
        $this->handleObjection($this->customObjection, $ai);
        $this->customObjection = '';
    }

    public function clearHistory(): void
    {
        $this->history = [];
        $this->response = null;
    }

    public function render()
    {
        return view('livewire.training.objection-handler-page')->layout('layouts.app');
    }
}
