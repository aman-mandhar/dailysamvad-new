<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_inactive_user_cannot_access_the_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('super-admin');

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    #[DataProvider('panelRoleProvider')]
    public function test_panel_access_matches_the_documented_role_rule(string $role, bool $allowed): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertSame($allowed, $user->canAccessPanel(Filament::getPanel('admin')));
    }

    /** @return array<string, array{string, bool}> */
    public static function panelRoleProvider(): array
    {
        return [
            'super-admin' => ['super-admin', true],
            'admin' => ['admin', true],
            'editor' => ['editor', true],
            'reporter' => ['reporter', true],
            'author' => ['author', true],
            'subscriber' => ['subscriber', false],
            'reviewer' => ['reviewer', true],
            'seo-manager' => ['seo-manager', true],
            'media-manager' => ['media-manager', true],
            'analytics-manager' => ['analytics-manager', true],
            'contributor' => ['contributor', true],
        ];
    }
}
