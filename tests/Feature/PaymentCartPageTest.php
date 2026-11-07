<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_open_cross_unit_payment_cart_mock(): void
    {
        $this->seedRole('orang_tua');
        $guardian = User::factory()->create(['role' => 'orang_tua']);

        $this->actingAs($guardian)
            ->get(route('guardian.payment-cart.index'))
            ->assertOk()
            ->assertSee('Keranjang Pembayaran')
            ->assertSee('Data Simulasi')
            ->assertSee('MI Mambaul Hikmah')
            ->assertSee('Pondok Pesantren Mambaul Hikmah')
            ->assertSee('Rp 650.000')
            ->assertSee('Rp 654.000')
            ->assertSee('Checkout dinonaktifkan selama halaman menampilkan data simulasi.');
    }

    public function test_cashier_cannot_open_guardian_payment_cart(): void
    {
        $this->seedRole('kasir');
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($cashier)
            ->get(route('guardian.payment-cart.index'))
            ->assertForbidden();
    }

    private function seedRole(string $key): void
    {
        Role::updateOrCreate(
            ['key' => $key],
            [
                'name' => Role::DEFAULTS[$key],
                'permissions' => Role::defaultPermissionsFor($key),
                'is_active' => true,
            ],
        );
    }
}
