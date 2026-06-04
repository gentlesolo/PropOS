<?php

namespace App\Http\Livewire\Auth;

use App\Application\Identity\Actions\LoginUserAction;
use Livewire\Component;

class LoginPage extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public string $mode = 'login'; // login, forgot_password, forgot_password_success
    public bool $isSuccessful = false;

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function submit(LoginUserAction $loginAction)
    {
        $this->validate();

        $result = $loginAction->execute($this->email, $this->password, $this->remember);

        if ($result === 'ok') {
            $this->isSuccessful = true;
            return;
        }

        if ($result === '2fa') {
            return redirect()->route('two-factor.challenge');
        }

        $this->addError('email', 'Invalid credentials or unauthorized access.');
    }

    public function sendResetLink()
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        // Transition to success state in-place
        $this->mode = 'forgot_password_success';
    }

    public function showForgotPassword()
    {
        $this->resetErrorBag();
        $this->mode = 'forgot_password';
    }

    public function showLogin()
    {
        $this->resetErrorBag();
        $this->mode = 'login';
    }

    public function render()
    {
        return view('livewire.auth.login-page')
            ->layout('layouts.auth');
    }
}
