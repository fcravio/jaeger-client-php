<?php

namespace Jaeger\Codec;

class CodecUtility
{

    /**
     * Incoming trace/span IDs are hex representations of 64-bit values. PHP
     * represents ints internally as signed 32- or 64-bit values, but base_convert
     * converts to string representations of arbitrarily large positive numbers.
     * This means at least half the incoming IDs will be larger than PHP_INT_MAX.
     *
     * Thrift, while building a binary representation of the IDs, performs bitwise
     * operations on the string values, implicitly casting to int and capping them
     * at PHP_INT_MAX. So, incoming IDs larger than PHP_INT_MAX will be serialized
     * and sent to the agent as PHP_INT_MAX, breaking trace/span correlation.
     *
     * This method therefore, on 64-bit architectures, splits the hex string into
     * high and low values, converts them separately to ints, and manually combines
     * them into a proper signed int. This int is then handled properly by the
     * Thrift package.
     *
     * On 32-bit architectures, it falls back to base_convert.
     *
     * @param string $hex
     * @return string|int
     */
    public static function hexToInt64($hex)
    {
        // If we're on a 32-bit architecture, fall back to base_convert.
        if (PHP_INT_SIZE === 4) {
            return base_convert($hex, 16, 10);
        }

        $hi = intval(substr($hex, -16, -8), 16);
        $lo = intval(substr($hex, -8, 8), 16);

        return $hi << 32 | $lo;
    }
}
