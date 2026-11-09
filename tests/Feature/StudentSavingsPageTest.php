<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSavingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_mock_student_savings_list_with_local_search(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.index'));

        $response->assertOk()
            ->assertSee('Tabungan Santri')
            ->assertSee('Data Simulasi')
            ->assertSee('Total Saldo Titipan')
            ->assertSee('Rp 4.150.000')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('SIM-260184')
            ->assertSee('Salwa Azzahra')
            ->assertSee('PONPES')
            ->assertSee('Cari nama, NIS, atau unit')
            ->assertSee('data-student-savings', false)
            ->assertSee('data-student-savings-search', false)
            ->assertSee('data-student-savings-empty', false)
            ->assertSee('href="'.route('finance.student-savings.show', 'SIM-260184').'"', false)
            ->assertSee('href="'.route('finance.student-savings.index').'"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(5, $xpath->query('//*[@data-student-savings-row]'));
    }

    public function test_bendahara_without_transaction_management_cannot_view_student_savings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'bendahara']))
            ->get(route('finance.student-savings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_mock_student_savings_mutations(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.show', 'SIM-260184'));

        $response->assertOk()
            ->assertSee('Mutasi Saldo Santri')
            ->assertSee('Data Simulasi')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('NIS SIM-260184')
            ->assertSee('Saldo Saat Ini')
            ->assertSee('Rp 850.000')
            ->assertSee('Saldo Awal')
            ->assertSee('Rp 600.000')
            ->assertSee('Total Setoran')
            ->assertSee('+Rp 450.000')
            ->assertSee('Total Penarikan')
            ->assertSee('-Rp 200.000')
            ->assertSee('08/11/2026 13.40 WIB')
            ->assertSee('Keperluan kegiatan santri')
            ->assertSee('Setoran loket sekolah')
            ->assertSee('+Rp 300.000')
            ->assertSee('-Rp 100.000')
            ->assertSee('href="'.route('finance.student-savings.index').'"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(4, $xpath->query('//table[contains(@class, "student-savings-mutation-table")]/tbody/tr'));
    }

    public function test_unknown_student_savings_account_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.show', 'SIM-TIDAK-ADA'))
            ->assertNotFound();
    }

    public function test_bendahara_without_transaction_management_cannot_view_student_savings_mutations(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'bendahara']))
            ->get(route('finance.student-savings.show', 'SIM-260184'))
            ->assertForbidden();
    }
}
