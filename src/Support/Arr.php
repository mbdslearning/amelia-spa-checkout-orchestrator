<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Support;

final class Arr
{
    /** @param array<mixed> $array */
    public static function deepFindString(array $array, array $keys): string
    {
        foreach ($keys as $k) {
            $found = self::findByKey($array, (string) $k);
            if (is_string($found) && $found !== '') {
                return $found;
            }
        }
        return '';
    }

    /** @param array<mixed> $array */
    private static function findByKey(array $array, string $key): mixed
    {
        foreach ($array as $k => $v) {
            if (is_string($k) && $k === $key) {
                return $v;
            }
            if (is_array($v)) {
                $found = self::findByKey($v, $key);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }
}
