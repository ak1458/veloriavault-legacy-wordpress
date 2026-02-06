<?php

namespace ADP\BaseVersion\Includes\SpecialStrategies;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;

class CompareStrategy
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function formatFloat($f)
    {
        return number_format(floatval($f), $this->context->getPriceDecimals(),".","");
    }

    /**
     * You can't just compare floating point numbers!
     * Only with a certain accuracy.
     *
     * @param string|int|float $a
     * @param string|int|float $b
     *
     * @return bool
     */
    public function floatsAreEqual($a, $b)
    {
        return $this->formatFloat($a) === $this->formatFloat($b);
    }

    /**
     * You can't just compare floating point numbers!
     * Only with a certain accuracy.
     *
     * @param string|int|float $a
     * @param string|int|float $b
     *
     * @return bool
     */
    public function floatLessAndEqual($a, $b)
    {
        return $this->formatFloat($a) <= $this->formatFloat($b);
    }

    /**
     * You can't just compare floating point numbers!
     * Only with a certain accuracy.
     *
     * @param string|int|float $a
     * @param string|int|float $b
     *
     * @return bool
     */
    public function floatLess($a, $b)
    {
        return $this->formatFloat($a) < $this->formatFloat($b);
    }

    /**
     * @param string|int|bool $string
     *
     * @return bool
     */
    public function isStringBool($string)
    {
        if (is_bool($string)) {
            return $string;
        }

        if (is_int($string)) {
            return 1 === $string;
        }

        if (is_string($string)) {
            $string = strtolower($string);

            return 'yes' === $string || 'true' === $string || '1' === $string || 'on' === $string;
        }

        return false;
    }
}
