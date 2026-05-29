<?php

namespace App\Http\Livewire\Auth;

use App\Application\Identity\Actions\RegisterUserAction;
use App\Application\Identity\DTOs\RegisterUserData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class RegisterPage extends Component
{
    public string $agency_name = '';
    public string $slug = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';

    protected array $rules = [
        'agency_name' => 'required|string|max:255',
        'slug' => 'required|string|alpha_dash|max:255|unique:agencies,slug',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'phone' => 'nullable|string|max:20',
        'password' => 'required|string|min:8',
    ];

    public function updatedAgencyName()
    {
        $this->slug = Str::slug($this->agency_name);
    }

    public function submit(RegisterUserAction $registerAction)
    {
        $this->validate();

        $dto = RegisterUserData::fromArray([
            'agency_name' => $this->agency_name,
            'slug' => $this->slug,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
        ]);

        $user = $registerAction->execute($dto);

        // Login
        Auth::login($user);

        // Set team context
        setPermissionsTeamId($user->agency_id);

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.register-page')
            ->layout('layouts.auth');
    }
}
