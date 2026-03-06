<?php

namespace App\Libraries;

use App\Models\Core\Currency;

class FinancialUitls
{
    /**
     * Format a number as currency. If no currency is provided,  Euro will be used.
     *
     * @param int|float|string|null $value
     * @param Currency|null $currency
     *
     * @return string
     */
    public static function currency(int|float|string|null $value, null|Currency|string $currency = null): string
    {
        if (is_null($value) || $value == '') {
            return '';
        }

        if (is_string($currency)) {
            $currency = Currency::getCurrencyByCode($currency);
        }

        $pre = '';
        if ($value < 0) {
            $pre = '- ';
            $value = abs($value);
        }

        if ($value == (int) $value) {
            $formatted = number_format($value, 0);
        } else {
            $formatted = number_format($value, 2);
        }

        $symbolPre = '';
        $symbolPost = '';

        if ($currency) {
            if ($currency->symbol) {
                $symbolPre = $currency->symbol;
            } else {
                $symbolPost = $currency->code;
            }
        } else {
            $symbolPre = '€';
        }

        return $pre . $symbolPre . $formatted . ($symbolPost ? ' ' . $symbolPost : '');
    }
    /**
     * Calculate the gross amount from a nett amount.
     *
     * @param int|float $nett
     *
     * @return float
     */
    public static function calculateGross(int|float $nett): float
    {
        return round(self::addPct($nett, config('app.vat')), 2);
    }
    /**
     * Calculate the VAT part of a nett amount.
     *
     * @param int|float $nett
     *
     * @return float
     */
    public static function calculateVAT(int|float $nett): float
    {
        return round($nett * config('app.vat') / 100, 2);
    }
    /**
     * Calculate the nett amount from a gross amount.
     *
     * @param int|float $gross
     *
     * @return float
     */
    public static function calculateNett(int|float $gross): float
    {
        return round(self::removePct($gross, config('app.vat')), 2);
    }
    /**
     * Add a certain % to an amount.
     *
     * @param int|float $gross
     *
     * @return float
     */
    public static function addPct(int|float $figure, int|float $pct): float
    {
        return $figure + (($pct / 100) * $figure);
    }
    /**
     * Remove a certain % from an amount.
     *
     * @param int|float $figure
     * @param int|float $pct
     *
     * @return float
     */
    public static function removePct(int|float $figure, int|float $pct): float
    {
        return $figure / ((100 + $pct) / 100);
    }
    /**
     * Get the original amount from a figure that has a certain % added.
     *
     * @param int|float $figure
     * @param int|float $pct
     *
     * @return float
     */
    public static function deductPct(int|float $figure, int|float $pct): float
    {
        return $figure - (($pct / 100) * $figure);
    }

}