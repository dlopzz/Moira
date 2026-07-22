<?php

namespace Tests\Feature;

use App\Filament\Resources\Marketing\HomeSections\Pages\CreateHomeSection;
use App\Models\HomeSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateHomeSectionSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_creating_a_banner_packs_settings_including_image(): void
    {
        Livewire::test(CreateHomeSection::class)
            ->fillForm([
                'type' => 'banner',
                'sort_order' => 0,
                'is_active' => true,
                'banner_image' => ['home-banners/promo.webp'],
                'banner_title' => 'Ofertas',
                'banner_link' => '/categories/ofertas',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $section = HomeSection::latest('id')->firstOrFail();

        $this->assertSame('home-banners/promo.webp', $section->settings['image']);
        $this->assertSame('Ofertas', $section->settings['title']);
        $this->assertSame('/categories/ofertas', $section->settings['link']);
    }
}
