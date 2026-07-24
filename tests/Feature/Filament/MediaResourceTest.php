<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_renders_and_requires_an_upload(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = User::factory()->create();
        $editor->assignRole('editor');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($editor)
            ->test(CreateMedia::class)
            ->assertOk()
            ->call('create')
            ->assertHasFormErrors(['upload' => 'required']);
    }
}
