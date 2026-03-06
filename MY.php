<?php

namespace App\Libraries;

use App\Models\Core\Currency;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MY
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
    /**
     * Format a number as a percentage.
     *
     * @param int|float $number
     * @param string $format short|long
     *
     * @return string
     */
    public static function pct(int|float $number, string $format = 'short'): string
    {
        if ($format == 'long' && (int) $number == (float) $number) {
            $format = 'short';
        }

        return number_format($number, $format == 'long' ? 2 : 0) . '%';
    }
    /**
     * Format a large number, with separators for thousands.
     *
     * @param int|float|null $number
     *
     * @return string
     */
    public static function largeNumber(int|float|null $number): string
    {
        if (is_null($number)) {
            return '';
        }

        return number_format($number);
    }
    /**
     * Format a number with 2 decimals.
     *
     * @param int|float $number
     *
     * @return string
     */
    public static function decimals(int|float $number): string
    {
        return number_format($number, 2);
    }

    /**
     * Parse a date from a string to Carbon.
     *
     * @param string $input
     *
     * @return Carbon|null
     */
    public static function parseDate(string $input): ?Carbon
    {
        $format = 'd/m/Y';
        if (strlen($input) == '8') {
            $format = 'd/m/y';
        }

        try {
            return Carbon::createFromFormat($format, $input);
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
        }

        return null;
    }
    /**
     * Parse a date and time from a string to Carbon.
     *
     * @param string $input
     *
     * @return Carbon|null
     */
    public static function parseDT(string $input): ?Carbon
    {
        try {
            return Carbon::createFromFormat(config('app.datetime_format'), $input);
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
        }

        return null;
    }
    /**
     * Format a Carbon date/time as a human-readable string.
     *
     * @param Carbon|null $input
     *
     * @return string|null
     */
    public static function formatDT(?Carbon $input): ?string
    {
        return $input ? $input->format(config('app.datetime_format')) : null;
    }

    /**
     * Format the date in a friendly way for the end user.
     *
     * @param Carbon|null $input
     * @param bool $detailed
     * @return string|null
     */
    public static function formatDateFriendly(?Carbon $input, bool $detailed = false): ?string
    {
        if (! $input) {
            return null;
        }

        if ($detailed) {
            return $input->format('D, jS \o\f F');
        }

        if ($input->isToday()) {
            return __('global.today');
        }

        if ($input->isYesterday()) {
            return __('global.yesterday');
        }

        return $input->format('d/m/Y');
    }

    /**
     * Format a date range composed of 2 Carbon dates.
     *
     * @param array $range
     * @return string
     */
    public static function formatDateRange(array $range): string
    {
        $start = reset($range);
        $end = end($range);

        $endMonth = __('global.' . strtolower($end->format('M')));

        if ($start->isSameMonth($end)) {
            if ($start->day == 1 && $end->day == $end->daysInMonth) {
                return $endMonth . ' ' . $end->format('Y');
            }

            return $start->format('j') . ' - ' . $end->format('j') . ' ' . $endMonth . ' ' . $end->format('Y');
        } elseif ($start->dayOfYear == 1 && $end->dayOfYear == $end->copy()->endOfYear()->dayOfYear) {
            return $start->format('Y');
        } else {
            $startMonth = __('global.' . strtolower($start->format('M')));

            return $start->format('j') . ' ' . $startMonth . ' - ' . $end->format('j') . ' ' . $endMonth . ' ' . $end->format('Y');
        }
    }

    /**
     * Format a Carbon date as a human-readable string.
     *
     * @param Carbon|null $input
     *
     * @return string|null
     */
    public static function formatDate(?Carbon $input, ?string $fallback = null): ?string
    {
        return $input ? $input->format(config('app.date_format')) : $fallback;
    }
    /**
     * Format a Carbon date to MySQL format.
     *
     * @param Carbon|null $input
     *
     * @return string|null
     */
    public static function toMySQLDate(?Carbon $input): ?string
    {
        return $input->format('Y-m-d');
    }
    /**
     * Format a Carbon date/time to MySQL format.
     *
     * @param Carbon|null $input
     *
     * @return string|null
     */
    public static function toMySQLDT(?Carbon $input): ?string
    {
        return $input->format('Y-m-d H:i:s');
    }
    /**
     * Get the number of days between 2 dates.
     *
     * @param Carbon $input
     * @param Carbon|null $ref default to today if not provided
     *
     * @return int
     */
    public static function daysSince(Carbon $input, ?Carbon $ref = null): int
    {
        if (!$ref) {
            $ref = now();
        }

        return $input->diffInDays($ref);
    }
    /**
     * Get a human-readable string for the number of days between 2 dates.
     *
     * @param Carbon $input
     * @param Carbon|null $ref default to today if not provided
     *
     * @return string
     */
    public static function daysAgo(Carbon $input, ?Carbon $ref = null): string
    {
        if (!$ref) {
            $ref = now();
        }

        $num = $input->diffInDays($ref);

        if ($num == 0) {
            if ($input->isSameDay($ref)) {
                return 'Today';
            } else {
                return 'Yesterday';
            }
        }

        if ($num == 1) {
            return 'Yesterday';
        }

        return $num . ' days ago';
    }
    /**
     * Format a date in a compact form.
     *
     * @param Carbon $input
     *
     * @return string
     */
    public static function formatDateCompact(Carbon $input)
    {
        if ($input->isSameDay(now())) {
            return 'Today';
        }

        return $input->format('j M');
    }
    /**
     * Format a time in a human-readable form.
     *
     * @param Carbon $input
     *
     * @return string
     */
    public static function formatTime(Carbon $input)
    {
        return $input->format('g:ia');
    }
    /**
     * Get the size of a file in a human-readable form.
     *
     * @param int|float $size
     * @param string $unit
     *
     * @return string
     */
    public static function humanFileSize(int|float $size, string $unit = ''): string
    {
        if ((!$unit && $size >= 1 << 30) || $unit == 'GB') {
            return number_format($size / (1 << 30), 2) . 'GB';
        }
        if ((!$unit && $size >= 1 << 20) || $unit == 'MB') {
            return number_format($size / (1 << 20), 2) . 'MB';
        }
        if ((!$unit && $size >= 1 << 10) || $unit == 'KB') {
            return number_format($size / (1 << 10), 2) . 'KB';
        }

        return number_format($size) . ' bytes';
    }
    /**
     * Truncate a string to a certain length, without breaking HTML.
     *
     * @param string $text
     * @param int $length
     * @param string $ending
     * @param bool $exact
     * @param bool $considerHtml
     *
     * @return string
     */
    public static function truncateHtml(string $text, int $length = 100, string $ending = '...', bool $exact = true, bool $considerHtml = true): string
    {
        if (is_array($ending)) {
            extract($ending);
        }
        if ($considerHtml) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $totalLength = mb_strlen($ending);
            $openTags = [];
            $truncate = '';
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = mb_substr($text, 0, $length - strlen($ending));
            }
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if (isset($spacepos)) {
                if ($considerHtml) {
                    $bits = mb_substr($truncate, $spacepos);
                    preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                    if (!empty($droppedTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    }
                }
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }

        $truncate .= $ending;

        if ($considerHtml) {
            foreach ($openTags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }
    /**
     * Get a list of times in 5-minute intervals.
     *
     * @return array
     */
    public static function timeList()
    {
        $output = ['' => 'N/A'];
        for ($i = 0; $i < 24; $i++) {
            for ($j = 0; $j < 60; $j += 5) {
                $value = str_pad($i, 2, '0', STR_PAD_LEFT) . ':' . str_pad($j, 2, '0', STR_PAD_LEFT);
                $output[$value] = $value;
            }
        }

        return $output;
    }
    /**
     * Get the nearest 5-minute interval to a given time.
     *
     * @param Carbon $input
     *
     * @return string
     */
    public static function getNearestFiveMin(Carbon $input): string
    {
        $copy = $input->copy();
        $min = $copy->minute;

        if ($min % 5 != 0) {
            $min = ($min - ($min % 5)) + 5;
        }

        $copy->minute = $min;

        return $copy->format('H:i');
    }
    /**
     * Convert an array to a CSV string.
     *
     * @param mixed $input
     * @param string $delimiter
     * @param string $enclosure
     *
     * @return string
     */
    public static function str_putcsv(mixed $input, string $delimiter = ',', string $enclosure = '"'): string
    {
        // Open a memory "file" for read/write...
        $fp = fopen('php://temp', 'r+');
        // ... write the $input array to the "file" using fputcsv()...
        fputcsv($fp, $input, $delimiter, $enclosure);
        // ... rewind the "file" so we can read what we just wrote...
        rewind($fp);
        // ... read the entire line into a variable...
        $data = fread($fp, 1048576);
        // ... close the "file"...
        fclose($fp);

        // ... and return the $data to the caller, with the trailing newline from fgets() removed.
        return $data;
    }
    /**
     * Clean up a filename to make it safe for storage.
     *
     * @param UploadedFile $image
     *
     * @return string
     */
    public static function cleanUpInputFilename(UploadedFile $image): string
    {
        $orig = $image->getClientOriginalName();
        if (strpos($orig, '.') !== false) {
            $orig = substr($orig, 0, strrpos($orig, '.'));
        }

        $ext = $image->getClientOriginalExtension();
        if (!$ext) {
            $ext = $image->extension();
        }

        $filename = strtolower(Str::slug($orig, '_')) . '.' . $ext;

        return $filename;
    }
    /**
     * From an associative array containing counters, increase the one for this given key.
     *
     * @param array $array
     * @param mixed $key
     *
     * @return void
     */
    public static function increaseCounter(array &$array, $key)
    {
        if (!isset($array[$key])) {
            $array[$key] = 1;
        } else {
            $array[$key]++;
        }
    }
    /**
     * Divide a number by another one and if the denominator is 0, return 0.
     *
     * @param int|float $numerator
     * @param int|float $denominator
     *
     * @return float
     */
    public static function divideOrZero(int|float $numerator, int|float $denominator): float
    {
        if ($denominator == 0) {
            return 0;
        }

        return $numerator / $denominator;
    }

    /**
     * Format the duration between 2 dates in human-readable format.
     *
     * @param mixed $date_difference
     *
     * @return string
     */
    public static function formatDuration($date_difference): string
    {
        $time_formatted = $date_difference->format('%d ' . __('global.day(s)'));
        if (0 != $date_difference->y) {
            $time_formatted = $date_difference->format('%y ' . __('global.year(s)') . ' %m ' . __('global.month(s)'));
        }
        if (0 == $date_difference->y && 0 != $date_difference->m) {
            $time_formatted = $date_difference->format('%m ' . __('global.month(s)') . ' %d ' . __('global.day(s)'));
        }

        return $time_formatted;
    }

    /**
     * Convert the string to null if it's empty.
     *
     * @param string|null $str
     *
     * @return string|null
     */
    public static function strNull(string $str = null): ?string
    {
        return trim($str) === '' ? null : $str;
    }

    /**
     * From a URL with several subdomain levels, get the top-level domain.
     *
     * @param string $url
     *
     * @return string
     */
    public static function getTopDomainHost(string $url): string
    {
        $components = parse_url($url, PHP_URL_HOST) ?? '';

        // get the top-level domain name for this host
        $domain = explode('.', $components);
        $tld = array_pop($domain);
        $name = array_pop($domain);

        return $name . '.' . $tld;
    }

    /**
     * Get the DB query log and format it so that it can be easily understood by a programmer.
     *
     * @return string
     */
    public static function readableQueryLog(): string
    {
        $queryLog = \Illuminate\Support\Facades\DB::getQueryLog();

        $lines = '';
        foreach ($queryLog as $index => $item) {
            $addSlashes = str_replace('?', "'?'", $item['query']);
            $query = vsprintf(str_replace('?', '%s', $addSlashes), $item['bindings']);
            $lines .= '-- ' . $index . ' (' . round($item['time'] / 1000, 4) . ' seconds) ' . PHP_EOL . $query . ';' . PHP_EOL . PHP_EOL;
        }

        return $lines;
    }

    /**
     * Prepare the variable to be added as an HTML attribute.
     *
     * @param mixed $input
     *
     * @return string|null
     */
    public static function jsonAttr(mixed $input): ?string
    {
        return htmlspecialchars(json_encode($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Write multiple data attributes that include JSON content.
     *
     * @param mixed $input
     *
     * @return string|null
     */
    public static function jsonAttrs(mixed $input): ?string
    {
        $output = '';

        foreach ($input as $key => $value) {
            $output .= ' data-' . $key . '="' . self::jsonAttr($value) . '"';
        }

        return $output;
    }

    /**
     * Calculate the standard deviation of a sample array.
     *
     * @param array $arr
     *
     * @return float
     */
    public static function stddev(array $arr) : float
    {
        $num_of_elements = count($arr);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float) sqrt($variance / $num_of_elements);
    }

    public static function arrayOrderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    /**
     * Prepare the created/updated timestamps for JSON resources.
     *
     * @param [type] $record
     * @param array $additional
     * @return array
     */
    public static function resourceTimestamps(mixed $record, array $additional = [], $detailed = false): array
    {
        $output = [];

        $fields = ['created_at', 'updated_at', ...$additional];

        foreach ($fields as $field) {
            if (@$record->$field) {
                $output[$field . '_time'] = strtotime($record->$field) * 1000;
                $output[$field . '_display'] = self::formatDateFriendly($record->$field, $detailed);
            } else {
                $output[$field . '_time'] = null;
                $output[$field . '_display'] = null;
            }
        }

        return $output;
    }

    /**
     * This function is taken from Wordpress' implementation.
     *
     * @param string $string
     * @return string
     */
    public static function removeAccents(string $string): string
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = [
        // Decompositions for Latin-1 Supplement
        chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
        chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
        chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
        chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E',
        chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E',
        chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I',
        chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I',
        chr(195) . chr(143) => 'I', chr(195) . chr(145) => 'N',
        chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O',
        chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
        chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U',
        chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
        chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
        chr(195) . chr(159) => 's', chr(195) . chr(160) => 'a',
        chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a',
        chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a',
        chr(195) . chr(165) => 'a', chr(195) . chr(167) => 'c',
        chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
        chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
        chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
        chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
        chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o',
        chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o',
        chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o',
        chr(195) . chr(182) => 'o', chr(195) . chr(185) => 'u',
        chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u',
        chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y',
        chr(195) . chr(191) => 'y',
        // Decompositions for Latin Extended-A
        chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
        chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
        chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
        chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
        chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
        chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
        chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
        chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
        chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
        chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
        chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
        chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
        chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
        chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
        chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
        chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
        chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
        chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
        chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
        chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
        chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
        chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
        chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
        chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
        chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
        chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
        chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
        chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
        chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L',
        chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
        chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
        chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
        chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
        chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
        chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
        chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
        chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N',
        chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
        chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
        chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
        chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
        chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
        chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r',
        chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
        chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r',
        chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
        chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's',
        chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
        chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
        chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
        chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
        chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
        chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
        chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
        chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
        chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
        chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
        chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
        chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
        chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
        chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
        chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
        chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
        chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',
        ];

        $string = strtr($string, $chars);

        return $string;
    }
}
