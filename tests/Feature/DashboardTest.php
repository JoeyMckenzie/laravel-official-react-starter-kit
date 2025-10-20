<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    #[Test]
    public function guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_users_can_visit_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertOk();
    }
}
