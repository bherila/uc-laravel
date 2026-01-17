<?php

namespace Tests\Feature;

use App\Models\K1\K1Company;
use App\Models\K1\OwnershipInterest;
use App\Models\K1\OutsideBasis;
use App\Models\K1\ObAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutsideBasisTest extends TestCase
{
    use RefreshDatabase;

    public function test_basis_walk_calculation_flow()
    {
        // 1. Setup Data
        // Create companies
        $owner = K1Company::create(['name' => 'Owner Corp']);
        $owned = K1Company::create(['name' => 'Owned LLC']);

        // Create ownership interest with inception basis
        $interest = OwnershipInterest::create([
            'owner_company_id' => $owner->id,
            'owned_company_id' => $owned->id,
            'ownership_percentage' => 0.50,
            'inception_basis_year' => 2023,
            'inception_basis_total' => 1000.00,
        ]);

        // Year 2023: Adjustments
        // Increase: 500
        // Decrease: 200
        // Net: +300
        // Ending: 1300
        $this->postJson("/api/ownership-interests/{$interest->id}/basis/2023/adjustments", [
            'adjustment_category' => 'increase',
            'amount' => 500,
            'description' => 'Income'
        ]);

        $this->postJson("/api/ownership-interests/{$interest->id}/basis/2023/adjustments", [
            'adjustment_category' => 'decrease',
            'amount' => 200,
            'description' => 'Distributions'
        ]);

        // Check 2023 Show
        $response2023 = $this->getJson("/api/ownership-interests/{$interest->id}/basis/2023");
        $response2023->assertOk()
            ->assertJson([
                'starting_basis' => 1000.00,
                'total_increases' => 500.00,
                'total_decreases' => 200.00,
                'ending_basis' => 1300.00,
            ]);

        // Year 2024: No adjustments yet
        // Starting: 1300 (from 2023 ending)
        // Ending: 1300
        $response2024 = $this->getJson("/api/ownership-interests/{$interest->id}/basis/2024");
        $response2024->assertOk()
            ->assertJson([
                'starting_basis' => 1300.00,
                'ending_basis' => 1300.00,
            ]);

        // Year 2024: Add adjustments
        // Decrease: 100
        // Ending: 1200
        $this->postJson("/api/ownership-interests/{$interest->id}/basis/2024/adjustments", [
            'adjustment_category' => 'decrease',
            'amount' => 100,
        ]);

        $response2024 = $this->getJson("/api/ownership-interests/{$interest->id}/basis/2024");
        $response2024->assertOk()
            ->assertJson([
                'starting_basis' => 1300.00,
                'ending_basis' => 1200.00,
            ]);

        // Year 2025: Starting 1200
        $response2025 = $this->getJson("/api/ownership-interests/{$interest->id}/basis/2025");
        $response2025->assertOk()
            ->assertJson([
                'starting_basis' => 1200.00,
            ]);

        // Test Override in 2024
        // Override 2024 Ending to 5000
        $this->putJson("/api/ownership-interests/{$interest->id}/basis/2024", [
            'ending_ob' => 5000.00
        ]);

        // Check 2024 (should show override)
        $response2024 = $this->getJson("/api/ownership-interests/{$interest->id}/basis/2024");
        $response2024->assertOk()
            ->assertJson([
                'ending_basis' => 5000.00,
            ]);

        // Check 2025 (should start with 5000)
        $response2025 = $this->getJson("/api/ownership-interests/{$interest->id}/basis/2025");
        $response2025->assertOk()
            ->assertJson([
                'starting_basis' => 5000.00,
            ]);
    }
}
