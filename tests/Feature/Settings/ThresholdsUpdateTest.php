<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Thresholds;
use App\Models\Threshold;
use App\Models\User;
use Database\Seeders\ThresholdSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThresholdsUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ThresholdSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('thresholds.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_the_thresholds_page_shows_seeded_defaults(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('thresholds.edit'))
            ->assertOk()
            ->assertSee('75')
            ->assertSee('90');
    }

    public function test_thresholds_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Thresholds::class)
            ->set('thresholds.cpu.warning', 70)
            ->set('thresholds.cpu.critical', 85)
            ->call('save')
            ->assertHasNoErrors();

        $cpu = Threshold::where('metric', 'cpu')->first();

        $this->assertEquals(70, $cpu->warning_threshold);
        $this->assertEquals(85, $cpu->critical_threshold);
    }

    public function test_critical_threshold_must_exceed_warning_threshold(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Thresholds::class)
            ->set('thresholds.cpu.warning', 80)
            ->set('thresholds.cpu.critical', 70)
            ->call('save')
            ->assertHasErrors(['thresholds.cpu.critical']);
    }

    public function test_packet_loss_and_bandwidth_have_no_critical_threshold_after_save(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Thresholds::class)
            ->set('thresholds.packet_loss.warning', 6)
            ->call('save')
            ->assertHasNoErrors();

        $packetLoss = Threshold::where('metric', 'packet_loss')->first();

        $this->assertEquals(6, $packetLoss->warning_threshold);
        $this->assertNull($packetLoss->critical_threshold);
    }
}
