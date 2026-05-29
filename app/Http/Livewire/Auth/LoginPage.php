<?php

namespace App\Http\Livewire\Auth;

use App\Application\Identity\Actions\LoginUserAction;
use Livewire\Component;

class LoginPage extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function submit(LoginUserAction $loginAction)
    {
        $this->validate();

        $result = $loginAction->execute($this->email, $this->password, $this->remember);

        if ($result === 'ok') {
            return redirect()->intended(route('dashboard'));
        }

        if ($result === '2fa') {
            return redirect()->route('two-factor.challenge');
        }

        $this->addError('email', 'Invalid credentials or unauthorized access.');
    }

    public function render()
    {
        return view('livewire.auth.login-page')
            ->layout('layouts.auth');
    }
}
