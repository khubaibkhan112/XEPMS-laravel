<?php

namespace App\Services;

use App\Models\Property;
use App\Models\TaxRate;
use Illuminate\Support\Carbon;

class TaxService
{
    /**
     * Calculate taxes for an amount
     */
    public function calculateTaxes(
        int $propertyId,
        float $subtotal,
        ?Carbon $date = null,
        ?int $roomTypeId = null,
        array $context = []
    ): array {
        $date = $date ?? Carbon::now();
        
        // Get applicable tax rates for the property
        $taxRates = TaxRate::where('property_id', $propertyId)
            ->active()
            ->applicableForDate($date)
            ->byPriority()
            ->get();

        $taxes = [];
        $totalTax = 0;
        $compoundBase = $subtotal;

        foreach ($taxRates as $taxRate) {
            if (!$this->taxApplies($taxRate, $roomTypeId, $subtotal, $context)) {
                continue;
            }

            $taxAmount = $this->calculateTaxAmount($taxRate, $compoundBase);

            $taxes[] = [
                'tax_rate_id' => $taxRate->id,
                'name' => $taxRate->name,
                'code' => $taxRate->code,
                'rate' => $taxRate->rate,
                'tax_type' => $taxRate->tax_type,
                'amount' => round($taxAmount, 2),
                'calculation_type' => $taxRate->calculation_type,
                'is_compound' => $taxRate->is_compound,
            ];

            if ($taxRate->is_compound) {
                // Compound taxes are calculated on subtotal + previous taxes
                $compoundBase += $taxAmount;
            }

            $totalTax += $taxAmount;
        }

        return [
            'tax_breakdown' => $taxes,
            'total_tax' => round($totalTax, 2),
            'subtotal' => $subtotal,
            'grand_total' => round($subtotal + $totalTax, 2),
        ];
    }

    /**
     * Calculate taxes for inclusive pricing (price includes tax)
     */
    public function calculateTaxesInclusive(
        int $propertyId,
        float $totalInclusive,
        ?Carbon $date = null,
        ?int $roomTypeId = null,
        array $context = []
    ): array {
        $date = $date ?? Carbon::now();
        
        $taxRates = TaxRate::where('property_id', $propertyId)
            ->where('calculation_type', 'inclusive')
            ->active()
            ->applicableForDate($date)
            ->byPriority()
            ->get();

        $taxes = [];
        $totalTax = 0;
        $baseAmount = $totalInclusive;

        // For inclusive taxes, calculate backwards from the total
        foreach ($taxRates as $taxRate) {
            if (!$this->taxApplies($taxRate, $roomTypeId, $totalInclusive, $context)) {
                continue;
            }

            // Calculate what portion of the total is tax
            if ($taxRate->tax_type === 'percentage') {
                // For inclusive: tax = total * (rate / (100 + rate))
                $taxAmount = $totalInclusive * ($taxRate->rate / (100 + $taxRate->rate));
            } else {
                $taxAmount = $taxRate->fixed_amount ?? 0;
            }

            $taxes[] = [
                'tax_rate_id' => $taxRate->id,
                'name' => $taxRate->name,
                'code' => $taxRate->code,
                'rate' => $taxRate->rate,
                'tax_type' => $taxRate->tax_type,
                'amount' => round($taxAmount, 2),
                'calculation_type' => $taxRate->calculation_type,
                'is_compound' => $taxRate->is_compound,
            ];

            $totalTax += $taxAmount;
        }

        $subtotal = $totalInclusive - $totalTax;

        return [
            'tax_breakdown' => $taxes,
            'total_tax' => round($totalTax, 2),
            'subtotal' => round($subtotal, 2),
            'grand_total' => $totalInclusive,
        ];
    }

    /**
     * Check if a tax rate applies to the given context
     */
    protected function taxApplies(
        TaxRate $taxRate,
        ?int $roomTypeId,
        float $amount,
        array $context
    ): bool {
        $conditions = $taxRate->conditions ?? [];

        // Check room type condition
        if (isset($conditions['room_type_ids']) && $roomTypeId) {
            if (!in_array($roomTypeId, $conditions['room_type_ids'])) {
                return false;
            }
        }

        // Check minimum amount condition
        if (isset($conditions['minimum_amount']) && $amount < $conditions['minimum_amount']) {
            return false;
        }

        // Check maximum amount condition
        if (isset($conditions['maximum_amount']) && $amount > $conditions['maximum_amount']) {
            return false;
        }

        return true;
    }

    /**
     * Calculate tax amount for a single tax rate
     */
    protected function calculateTaxAmount(TaxRate $taxRate, float $baseAmount): float
    {
        if ($taxRate->tax_type === 'fixed_amount') {
            return $taxRate->fixed_amount ?? 0;
        }

        // Percentage tax
        return $baseAmount * ($taxRate->rate / 100);
    }

    /**
     * Get tax rates for a property
     */
    public function getTaxRates(int $propertyId, ?Carbon $date = null): \Illuminate\Database\Eloquent\Collection
    {
        $date = $date ?? Carbon::now();

        return TaxRate::where('property_id', $propertyId)
            ->active()
            ->applicableForDate($date)
            ->byPriority()
            ->get();
    }
}


