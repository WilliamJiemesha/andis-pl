<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_can_be_switched_in_session(): void
    {
        $response = $this->from(route('login'))->post(route('language.switch', 'en'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('locale', 'en');
    }
}
