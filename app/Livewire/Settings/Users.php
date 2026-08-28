<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('User management')]
class Users extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public bool $showFormModal = false;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $confirmingDeleteId = null;

    public function create(): void
    {
        $this->reset(['name', 'email', 'role', 'password', 'password_confirmation']);
        $this->role = UserRole::User->value;
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            ...$this->profileRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => $this->passwordRules(),
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        $this->showFormModal = false;

        Flux::toast(variant: 'success', text: __('User added.'));
    }

    public function updateRole(int $userId, string $role): void
    {
        $user = User::findOrFail($userId);
        $newRole = UserRole::from($role);

        if ($user->id === Auth::id()) {
            Flux::toast(variant: 'danger', text: __('You cannot change your own role here.'));

            return;
        }

        if ($user->role === UserRole::Admin && $newRole !== UserRole::Admin && $this->isLastAdmin($user)) {
            Flux::toast(variant: 'danger', text: __('At least one admin must remain.'));

            return;
        }

        $user->update(['role' => $newRole]);

        Flux::toast(variant: 'success', text: __('Role updated for :name.', ['name' => $user->name]));
    }

    public function confirmDelete(int $userId): void
    {
        $this->confirmingDeleteId = $userId;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $user = User::findOrFail($this->confirmingDeleteId);

        if ($user->id === Auth::id()) {
            Flux::toast(variant: 'danger', text: __('You cannot remove your own account here.'));
            $this->confirmingDeleteId = null;

            return;
        }

        if ($user->role === UserRole::Admin && $this->isLastAdmin($user)) {
            Flux::toast(variant: 'danger', text: __('At least one admin must remain.'));
            $this->confirmingDeleteId = null;

            return;
        }

        $user->delete();
        $this->confirmingDeleteId = null;

        Flux::toast(variant: 'success', text: __('User removed.'));
    }

    private function isLastAdmin(User $user): bool
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }

    public function render(): View
    {
        return view('livewire.settings.users', [
            'users' => User::query()->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }
}
