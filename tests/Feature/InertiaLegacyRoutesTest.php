<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InertiaLegacyRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_ticket_url_redirects_to_resolbeb(): void
    {
        $user = $this->bareUser();

        $this->actingAs($user, 'web')
            ->get('/tickets/42')
            ->assertRedirect('/resolbeb/tickets/42');
    }

    public function test_legacy_resolvev1_dashboard_redirects_to_resolbeb(): void
    {
        $user = $this->bareUser();

        $this->actingAs($user, 'web')
            ->get('/resolvev1/dashboard')
            ->assertRedirect('/resolbeb');
    }

    public function test_legacy_invitation_accept_redirects_to_register_accept(): void
    {
        $this->get('/invitation/accept?token=test-token')
            ->assertRedirect(route('invitation.accept', ['token' => 'test-token']));
    }

    private function bareUser(): User
    {
        $now = now();
        $areaId = \DB::table('areas')->insertGetId(['name' => 'A', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $positionId = \DB::table('positions')->insertGetId(['name' => 'P', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $siteId = \DB::table('sites')->insertGetId([
            'name' => 'S', 'code' => 'LEG', 'type' => 'physical',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return User::create([
            'first_name' => 'T', 'paternal_last_name' => 'U',
            'email' => 'legacy-route@test.local', 'password' => Hash::make('x'),
            'employee_number' => '999001',
            'area_id' => $areaId, 'position_id' => $positionId, 'sede_id' => $siteId,
            'status' => 'active', 'email_verified_at' => now(),
        ]);
    }
}
