<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Property;
use App\Models\Rate;
use App\Models\RateRule;
use App\Models\RoomFeature;
use App\Models\RoomType;
use Illuminate\Support\Carbon;

class PricingService
{
    public function __construct(
        protected TaxService $taxService
    ) {
    }
    /**
     * Calculate the rate for a room type on a specific date
     */
    public function calculateRate(
        int $propertyId,
        int $roomTypeId,
        Carbon $date,
        ?int $nights = 1,
        ?int $adultCount = 1,
        ?int $childCount = 0,
        ?string $rateType = 'default'
    ): array {
        $roomType = RoomType::find($roomTypeId);
        $baseRate = $roomType?->base_rate ?? 0;
        $currency = $roomType?->currency ?? 'GBP';

        // Check for specific date rate
        $specificRate = Rate::where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->forDate($date)
            ->active()
            ->first();

        $rate = $specificRate ? $specificRate->rate : $baseRate;

        // Apply rate rules
        $rateRules = RateRule::where('property_id', $propertyId)
            ->where(function ($query) use ($roomTypeId) {
                $query->whereNull('room_type_id')
                    ->orWhere('room_type_id', $roomTypeId);
            })
            ->where('rule_type', $rateType)
            ->active()
            ->applicableForDate($date)
            ->byPriority()
            ->get();

        $originalRate = $rate;
        $appliedRules = [];

        foreach ($rateRules as $rule) {
            if ($this->ruleApplies($rule, $date, $nights, $adultCount, $childCount)) {
                $rate = $this->applyRule($rate, $rule);
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_type' => $rule->rule_type,
                    'adjustment' => $this->calculateAdjustment($originalRate, $rule),
                ];
            }
        }

        // Apply additional rate rules based on date conditions (weekend, weekday, etc.)
        $dateRules = $this->getDateBasedRules($propertyId, $roomTypeId, $date);
        foreach ($dateRules as $rule) {
            if ($this->ruleApplies($rule, $date, $nights, $adultCount, $childCount)) {
                $rate = $this->applyRule($rate, $rule);
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_type' => $rule->rule_type,
                    'adjustment' => $this->calculateAdjustment($originalRate, $rule),
                ];
            }
        }

        return [
            'rate' => round($rate, 2),
            'base_rate' => $baseRate,
            'original_rate' => $originalRate,
            'currency' => $currency,
            'applied_rules' => $appliedRules,
            'rate_source' => $specificRate ? 'date_specific' : 'base_rate',
        ];
    }

    /**
     * Calculate total price for a date range
     */
    public function calculateTotalPrice(
        int $propertyId,
        int $roomTypeId,
        Carbon $checkIn,
        Carbon $checkOut,
        ?int $adultCount = 1,
        ?int $childCount = 0,
        ?string $rateType = 'default',
        ?array $selectedFeatures = [],
        ?string $discountCode = null,
        ?int $userId = null
    ): array {
        $nights = $checkIn->diffInDays($checkOut);
        $dateRange = $this->generateDateRange($checkIn, $checkOut);

        $dailyRates = [];
        $totalAmount = 0;

        foreach ($dateRange as $date) {
            $rateData = $this->calculateRate(
                $propertyId,
                $roomTypeId,
                $date,
                $nights,
                $adultCount,
                $childCount,
                $rateType
            );

            $dailyRates[] = [
                'date' => $date->toDateString(),
                'rate' => $rateData['rate'],
                'currency' => $rateData['currency'],
                'applied_rules' => $rateData['applied_rules'],
            ];

            $totalAmount += $rateData['rate'];
        }

        // Calculate feature pricing
        $featurePricing = $this->calculateFeaturePricing(
            propertyId: $propertyId,
            roomTypeId: $roomTypeId,
            checkIn: $checkIn,
            checkOut: $checkOut,
            nights: $nights,
            adultCount: $adultCount,
            childCount: $childCount,
            selectedFeatures: $selectedFeatures
        );

        $subtotal = $totalAmount + $featurePricing['total'];

        // Apply discount if provided
        $discountApplied = null;
        $discountAmount = 0;
        if ($discountCode) {
            $discountResult = $this->applyDiscount(
                propertyId: $propertyId,
                discountCode: $discountCode,
                roomTypeId: $roomTypeId,
                checkIn: $checkIn,
                checkOut: $checkOut,
                nights: $nights,
                adultCount: $adultCount,
                childCount: $childCount,
                subtotal: $subtotal,
                userId: $userId
            );

            if ($discountResult['applied']) {
                $discountApplied = $discountResult['discount'];
                $discountAmount = $discountResult['amount'];
                $subtotal = $subtotal - $discountAmount;
            }
        }

        // Calculate taxes on subtotal after discount (room + features - discount)
        $firstDate = $checkIn;
        $taxCalculation = $this->taxService->calculateTaxes(
            propertyId: $propertyId,
            subtotal: $subtotal,
            date: $firstDate,
            roomTypeId: $roomTypeId
        );

        return [
            'subtotal' => round($subtotal, 2),
            'room_subtotal' => round($totalAmount, 2),
            'features_subtotal' => round($featurePricing['total'], 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code' => $discountCode,
            'discount' => $discountApplied ? [
                'id' => $discountApplied->id,
                'code' => $discountApplied->code,
                'name' => $discountApplied->name,
                'type' => $discountApplied->type,
                'amount' => round($discountAmount, 2),
            ] : null,
            'total_tax' => $taxCalculation['total_tax'],
            'total_amount' => $taxCalculation['grand_total'],
            'tax_breakdown' => $taxCalculation['tax_breakdown'],
            'nights' => $nights,
            'daily_rates' => $dailyRates,
            'features' => $featurePricing['features'],
            'currency' => $dailyRates[0]['currency'] ?? 'GBP',
            'average_rate' => $nights > 0 ? round($totalAmount / $nights, 2) : 0,
        ];
    }

    /**
     * Check if a rate rule applies to the given parameters
     */
    protected function ruleApplies(
        RateRule $rule,
        Carbon $date,
        int $nights,
        int $adultCount,
        int $childCount
    ): bool {
        $conditions = $rule->conditions ?? [];

        // Check day of week conditions
        if (isset($conditions['days_of_week'])) {
            $dayOfWeek = $date->dayOfWeek;
            if (!in_array($dayOfWeek, $conditions['days_of_week'])) {
                return false;
            }
        }

        // Check length of stay conditions
        if (isset($conditions['min_stay']) && $nights < $conditions['min_stay']) {
            return false;
        }
        if (isset($conditions['max_stay']) && $nights > $conditions['max_stay']) {
            return false;
        }

        // Check occupancy conditions
        $totalGuests = $adultCount + $childCount;
        if (isset($conditions['min_occupancy']) && $totalGuests < $conditions['min_occupancy']) {
            return false;
        }
        if (isset($conditions['max_occupancy']) && $totalGuests > $conditions['max_occupancy']) {
            return false;
        }

        // Check month conditions
        if (isset($conditions['months'])) {
            if (!in_array($date->month, $conditions['months'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply a rate rule to the current rate
     */
    protected function applyRule(float $currentRate, RateRule $rule): float
    {
        switch ($rule->adjustment_type) {
            case 'fixed_rate':
                return $rule->fixed_rate ?? $currentRate;

            case 'fixed_amount':
                return $currentRate + ($rule->rate_adjustment ?? 0);

            case 'percentage':
            default:
                $multiplier = $rule->rate_multiplier ?? 1.0;
                return $currentRate * $multiplier;
        }
    }

    /**
     * Calculate adjustment amount for a rule
     */
    protected function calculateAdjustment(float $originalRate, RateRule $rule): float
    {
        switch ($rule->adjustment_type) {
            case 'fixed_rate':
                return ($rule->fixed_rate ?? $originalRate) - $originalRate;

            case 'fixed_amount':
                return $rule->rate_adjustment ?? 0;

            case 'percentage':
            default:
                $multiplier = $rule->rate_multiplier ?? 1.0;
                return ($originalRate * $multiplier) - $originalRate;
        }
    }

    /**
     * Get date-based rules (weekend, weekday, etc.)
     */
    protected function getDateBasedRules(int $propertyId, int $roomTypeId, Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

        $ruleType = $isWeekend ? 'weekend' : 'weekday';

        return RateRule::where('property_id', $propertyId)
            ->where(function ($query) use ($roomTypeId) {
                $query->whereNull('room_type_id')
                    ->orWhere('room_type_id', $roomTypeId);
            })
            ->where('rule_type', $ruleType)
            ->active()
            ->applicableForDate($date)
            ->byPriority()
            ->get();
    }

    /**
     * Calculate feature pricing
     */
    public function calculateFeaturePricing(
        int $propertyId,
        int $roomTypeId,
        Carbon $checkIn,
        Carbon $checkOut,
        int $nights,
        int $adultCount,
        int $childCount,
        array $selectedFeatures = []
    ): array {
        $totalFeaturesPrice = 0;
        $features = [];
        $personCount = $adultCount + $childCount;

        // Get available features for this property/room type
        $availableFeatures = RoomFeature::forProperty($propertyId)
            ->forRoomType($roomTypeId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        foreach ($availableFeatures as $feature) {
            // Check if feature applies to this booking
            if (!$feature->appliesTo($roomTypeId, $nights, $checkIn)) {
                continue;
            }

            // Get quantity from selected features, or use 1 if required
            $quantity = $selectedFeatures[$feature->id] ?? ($feature->is_required ? 1 : 0);

            if ($quantity <= 0) {
                continue;
            }

            // Check max quantity
            if ($feature->max_quantity !== null && $quantity > $feature->max_quantity) {
                $quantity = $feature->max_quantity;
            }

            // Calculate price for this feature
            $featurePrice = $feature->calculatePrice($quantity, $nights, $personCount);

            $totalFeaturesPrice += $featurePrice;

            $features[] = [
                'id' => $feature->id,
                'name' => $feature->name,
                'code' => $feature->code,
                'type' => $feature->type,
                'description' => $feature->description,
                'quantity' => $quantity,
                'price' => round($feature->price, 2),
                'pricing_type' => $feature->pricing_type,
                'total_price' => round($featurePrice, 2),
                'currency' => $feature->currency,
            ];
        }

        return [
            'total' => round($totalFeaturesPrice, 2),
            'features' => $features,
        ];
    }

    /**
     * Get available features for a booking
     */
    public function getAvailableFeatures(
        int $propertyId,
        ?int $roomTypeId = null,
        ?Carbon $checkIn = null,
        ?Carbon $checkOut = null
    ): array {
        $nights = $checkIn && $checkOut ? $checkIn->diffInDays($checkOut) : null;

        $features = RoomFeature::forProperty($propertyId)
            ->forRoomType($roomTypeId)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->filter(function ($feature) use ($roomTypeId, $nights, $checkIn) {
                return $feature->appliesTo($roomTypeId, $nights, $checkIn);
            })
            ->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'code' => $feature->code,
                    'type' => $feature->type,
                    'description' => $feature->description,
                    'price' => round($feature->price, 2),
                    'pricing_type' => $feature->pricing_type,
                    'currency' => $feature->currency,
                    'max_quantity' => $feature->max_quantity,
                    'is_required' => $feature->is_required,
                ];
            })
            ->values()
            ->toArray();

        return $features;
    }

    /**
     * Apply discount code to pricing
     */
    public function applyDiscount(
        int $propertyId,
        string $discountCode,
        ?int $roomTypeId = null,
        ?Carbon $checkIn = null,
        ?Carbon $checkOut = null,
        ?int $nights = null,
        ?int $adultCount = null,
        ?int $childCount = null,
        ?float $subtotal = null,
        ?int $userId = null
    ): array {
        $discount = Discount::forProperty($propertyId)
            ->byCode($discountCode)
            ->active()
            ->first();

        if (!$discount) {
            return [
                'applied' => false,
                'message' => 'Discount code not found or invalid',
            ];
        }

        // Check if discount applies
        if (!$discount->appliesTo(
            $roomTypeId,
            $checkIn,
            $checkOut,
            $nights,
            $adultCount,
            $childCount,
            $subtotal,
            $userId
        )) {
            return [
                'applied' => false,
                'message' => 'Discount code does not apply to this booking',
                'discount' => $discount,
            ];
        }

        // Calculate discount amount
        $discountAmount = $discount->calculateDiscount($subtotal ?? 0, $nights ?? 1);

        return [
            'applied' => true,
            'discount' => $discount,
            'amount' => $discountAmount,
            'message' => 'Discount applied successfully',
        ];
    }

    /**
     * Validate discount code
     */
    public function validateDiscountCode(
        int $propertyId,
        string $discountCode,
        ?int $roomTypeId = null,
        ?Carbon $checkIn = null,
        ?Carbon $checkOut = null,
        ?int $nights = null,
        ?int $adultCount = null,
        ?int $childCount = null,
        ?float $subtotal = null,
        ?int $userId = null
    ): array {
        $result = $this->applyDiscount(
            $propertyId,
            $discountCode,
            $roomTypeId,
            $checkIn,
            $checkOut,
            $nights,
            $adultCount,
            $childCount,
            $subtotal,
            $userId
        );

        if ($result['applied']) {
            return [
                'valid' => true,
                'discount' => [
                    'id' => $result['discount']->id,
                    'code' => $result['discount']->code,
                    'name' => $result['discount']->name,
                    'type' => $result['discount']->type,
                    'discount_value' => $result['discount']->discount_value,
                    'estimated_savings' => $result['amount'],
                ],
                'message' => $result['message'],
            ];
        }

        return [
            'valid' => false,
            'message' => $result['message'],
        ];
    }

    /**
     * Generate date range between two dates
     */
    protected function generateDateRange(Carbon $start, Carbon $end): array
    {
        $dates = [];
        $current = $start->copy();

        while ($current->lt($end)) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }
}

