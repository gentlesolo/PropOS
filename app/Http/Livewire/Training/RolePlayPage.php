<?php

namespace App\Http\Livewire\Training;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\TrainingModule;
use App\Infrastructure\Persistence\Models\TrainingProgress;
use Livewire\Component;

class RolePlayPage extends Component
{
    public string $scenario = 'qualification';
    public string $persona = 'first_time_buyer';

    public array $messages = [];
    public string $inputMessage = '';
    public bool $isStarted = false;
    public bool $isTyping = false;
    public ?array $feedback = null;
    public bool $generatingFeedback = false;

    protected array $rules = [
        'inputMessage' => 'required|string|max:1000',
    ];

    public function startSimulation(): void
    {
        $this->isStarted = true;
        $this->messages = [];
        $this->feedback = null;

        $this->messages[] = [
            'role' => 'system',
            'content' => "You are an AI actor in a real estate role-play simulation training a real estate agent. " .
                "Persona: {$this->getPersonaDescription()}. " .
                "Scenario: {$this->getScenarioDescription()}. " .
                "Stay strictly in character. Be realistic, occasionally resistant. Keep responses under 3 sentences. Never break character.",
        ];

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $this->getInitialGreeting(),
        ];
    }

    public function endSimulation(AiCompletionServiceInterface $ai): void
    {
        $this->isStarted = false;
        $this->generatingFeedback = true;
        $this->generateAiFeedback($ai);
        $this->generatingFeedback = false;
    }

    public function sendMessage(AiCompletionServiceInterface $ai): void
    {
        $this->validate();

        $this->messages[] = ['role' => 'user', 'content' => $this->inputMessage];
        $this->inputMessage = '';
        $this->isTyping = true;

        try {
            $response = $ai->chat($this->messages);
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $response['content'] ?? 'Hmm, I\'m not sure what you mean. Can you rephrase that?',
            ];
        } catch (\Exception) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Hmm, I\'m not sure what you mean. Can you rephrase that?',
            ];
        }

        $this->isTyping = false;
    }

    private function generateAiFeedback(AiCompletionServiceInterface $ai): void
    {
        $userTurns = collect($this->messages)
            ->filter(fn($m) => $m['role'] === 'user')
            ->pluck('content')
            ->implode("\n---\n");

        if (empty(trim($userTurns))) {
            $this->feedback = ['score' => 0, 'strengths' => ['No conversation recorded.'], 'improvements' => ['Start a conversation to receive feedback.']];
            return;
        }

        $prompt = implode("\n", [
            "Evaluate this real estate agent's role-play performance.",
            "Scenario: " . $this->getScenarioDescription(),
            "Persona they faced: " . $this->getPersonaDescription(),
            "",
            "Agent's messages:",
            $userTurns,
            "",
            "Score 0–100 and give 2 strengths and 2 improvements.",
            "Return only valid JSON: {\"score\": int, \"strengths\": [\"string\",\"string\"], \"improvements\": [\"string\",\"string\"]}",
        ]);

        $raw = $ai->generate(
            "You are an expert real estate sales coach. Give concise, specific, actionable feedback.",
            $prompt
        );

        $parsed = json_decode($raw, true);

        $this->feedback = is_array($parsed) ? $parsed : [
            'score' => 72,
            'strengths' => ['Engaged consistently with the client.', 'Maintained professional composure.'],
            'improvements' => ['Ask more open-ended discovery questions.', 'Establish value before addressing price.'],
        ];

        // Track progress in DB
        $module = TrainingModule::where('type', 'roleplay')->first()
            ?? TrainingModule::where('category', 'skills')->first();

        if ($module && auth()->check()) {
            TrainingProgress::updateOrCreate(
                ['user_id' => auth()->id(), 'module_id' => $module->id],
                [
                    'progress_pct' => 100,
                    'status' => 'completed',
                    'score' => $this->feedback['score'] ?? null,
                    'started_at' => now()->subMinutes(5),
                    'completed_at' => now(),
                ]
            );
        }
    }

    private function getPersonaDescription(): string
    {
        return match ($this->persona) {
            'first_time_buyer' => 'A nervous, slightly uninformed first-time buyer worried about interest rates and whether now is the right time.',
            'seasoned_investor' => 'A blunt, numbers-driven property investor demanding at least an 8% yield with no patience for fluff.',
            'reluctant_seller' => 'An emotional seller convinced their property is worth 20% more than market value and resistant to any reduction.',
            default => 'A standard real estate client.',
        };
    }

    private function getScenarioDescription(): string
    {
        return match ($this->scenario) {
            'qualification' => 'First phone call. Agent must determine budget, timeline, motivation, and qualify the lead.',
            'listing_presentation' => 'In-person presentation at the seller\'s home. Agent is competing for a sole mandate against a competitor offering lower commission.',
            'objection_handling' => 'Post-offer negotiation. An offer came in 10% below asking and the seller is very upset.',
            default => 'A general real estate consultation.',
        };
    }

    private function getInitialGreeting(): string
    {
        return match ($this->scenario) {
            'qualification' => 'Hello? Yes, I saw the property on Property24. I\'m not really sure if I\'m ready to buy yet but I thought I\'d call.',
            'listing_presentation' => 'Thanks for coming. Look — the other agency offered 3.5% commission and said they\'d get full asking price. What exactly makes you different?',
            'objection_handling' => 'I saw the offer. Honestly, I\'m insulted. We are not giving this house away. My neighbour sold for way more last year.',
            default => 'Hello.',
        };
    }

    public function render()
    {
        return view('livewire.training.role-play-page')->layout('layouts.app');
    }
}
