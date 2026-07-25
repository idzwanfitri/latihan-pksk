<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_the_pelajar_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Role::Pelajar, $user->refresh()->role);
        $this->assertTrue($user->isPelajar());
        $this->assertFalse($user->isAdmin());
    }

    public function test_the_role_column_is_not_mass_assignable(): void
    {
        $user = new User;
        $user->fill(['name' => 'Cuba', 'email' => 'cuba@example.com', 'role' => 'admin']);

        $this->assertNotSame(Role::Admin, $user->role);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('ujian.mula'))->assertRedirect(route('login'));
        $this->get(route('admin.soalan-a'))->assertRedirect(route('login'));
    }

    public function test_a_pelajar_cannot_reach_the_admin_pages(): void
    {
        $this->actingAs(User::factory()->pelajar()->create());

        $this->get(route('admin.soalan-a'))->assertForbidden();
        $this->get(route('admin.soalan-b'))->assertForbidden();
    }

    public function test_an_admin_cannot_reach_the_exam_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('ujian.mula'))->assertForbidden();
    }

    public function test_a_pelajar_can_reach_the_exam_start_page(): void
    {
        $this->actingAs(User::factory()->pelajar()->create());

        $this->get(route('ujian.mula'))->assertOk();
    }

    public function test_an_admin_is_sent_to_the_question_bank_after_logging_in(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.soalan-a'));
    }

    public function test_a_pelajar_is_sent_to_the_exam_start_page_after_logging_in(): void
    {
        $pelajar = User::factory()->pelajar()->create();

        $this->post(route('login'), [
            'email' => $pelajar->email,
            'password' => 'password',
        ])->assertRedirect(route('ujian.mula'));
    }
}
