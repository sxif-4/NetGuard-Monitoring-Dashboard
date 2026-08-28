<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Livewire\Settings\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admins_can_see_existing_users(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['name' => 'Jane Operator']);

        Livewire::test(Users::class)
            ->assertSee($user->name);
    }

    public function test_a_user_can_be_created(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'New Operator')
            ->set('email', 'new-operator@example.com')
            ->set('role', UserRole::User->value)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new-operator@example.com',
            'role' => UserRole::User->value,
        ]);
    }

    public function test_creating_a_user_requires_a_unique_email(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Duplicate Email')
            ->set('email', 'taken@example.com')
            ->set('role', UserRole::User->value)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_a_users_role_can_be_updated(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['role' => UserRole::User]);

        Livewire::test(Users::class)
            ->call('updateRole', $user->id, UserRole::Admin->value);

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $onlyAdmin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(Users::class)
            ->call('updateRole', $onlyAdmin->id, UserRole::User->value);

        $this->assertSame(UserRole::Admin, $onlyAdmin->fresh()->role);
    }

    public function test_a_user_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Users::class)
            ->call('updateRole', $admin->id, UserRole::User->value);

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_a_user_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create();

        Livewire::test(Users::class)
            ->call('confirmDelete', $user->id)
            ->call('delete');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_a_user_cannot_delete_their_own_account_here(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Users::class)
            ->call('confirmDelete', $admin->id)
            ->call('delete');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $onlyAdmin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(Users::class)
            ->call('confirmDelete', $onlyAdmin->id)
            ->call('delete');

        $this->assertDatabaseHas('users', ['id' => $onlyAdmin->id]);
    }
}
