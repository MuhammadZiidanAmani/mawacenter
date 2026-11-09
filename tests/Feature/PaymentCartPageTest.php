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
            ->assertSee('5 tagihan dari 2 anak di 3 unit')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('Aisyah Rahma')
            ->assertSee('MI Mambaul Hikmah')
            ->assertSee('Pondok Pesantren Mambaul Hikmah')
            ->assertSee('RA Mambaul Hikmah')
            ->assertSee('NIS 260184 · Kelas 6A')
            ->assertSee('NIS PP-260184 · Kelas Hifdz 2')
            ->assertSee('NIS 260245 · Kelas B1')
            ->assertSee('Rincian per Anak')
            ->assertSee('4 tagihan · 2 unit')
            ->assertSee('1 tagihan · 1 unit')
            ->assertSee('Dikenakan satu kali per transaksi')
            ->assertSee('Rp 650.000')
            ->assertSee('Rp 90.000')
            ->assertSee('Rp 740.000')
            ->assertSee('Rp 4.000')
            ->assertSee('Rp 744.000')
            ->assertSee('Kanal Pembayaran')
            ->assertSee('BNI Virtual Account')
            ->assertSee('8808 2601 8424 5000')
            ->assertSee('Instruksi BNI Virtual Account')
            ->assertSee('Instruksi QRIS')
            ->assertSee('Kode berikut hanya contoh tampilan.')
            ->assertSee('data-payment-cart-channel', false)
            ->assertSee('data-payment-cart-instructions="qris"', false)
            ->assertSee('Pratinjau Status')
            ->assertSee('Menunggu Pembayaran')
            ->assertSee('Tagihan belum berubah dan belum dianggap lunas sampai pembayaran diterima serta terverifikasi.')
            ->assertSee('Pembayaran Berhasil')
            ->assertSee('Pembayaran Kedaluwarsa')
            ->assertSee('Tidak ada pembayaran yang dicatat dan seluruh tagihan tetap memiliki saldo semula.')
            ->assertSee('data-payment-cart-status="pending"', false)
            ->assertSee('data-payment-cart-result="success"', false)
            ->assertSee('Periksa Pembayaran')
            ->assertSee('Validasi ini hanya simulasi dan tidak membuat transaksi.')
            ->assertSee('data-payment-cart-checkout', false)
            ->assertSee('data-payment-cart-validation', false);
    }

    public function test_guardian_sees_empty_cart_state_and_disabled_mock_validation(): void
    {
        $this->seedRole('orang_tua');
        $guardian = User::factory()->create(['role' => 'orang_tua']);

        $this->actingAs($guardian)
            ->get(route('guardian.payment-cart.index', ['mock_state' => 'empty']))
            ->assertOk()
            ->assertSee('Keranjang pembayaran masih kosong')
            ->assertSee('Pilih Tagihan Anak')
            ->assertSee('Belum ada tagihan dalam ringkasan.')
            ->assertSee('Rp 0')
            ->assertSee('Tambahkan minimal satu tagihan untuk memilih kanal pembayaran.')
            ->assertSee('Pilih tagihan untuk mengaktifkan pemeriksaan pembayaran.')
            ->assertSee('data-payment-cart-checkout disabled', false)
            ->assertDontSee('Instruksi BNI Virtual Account')
            ->assertDontSee('Pratinjau Status');
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
