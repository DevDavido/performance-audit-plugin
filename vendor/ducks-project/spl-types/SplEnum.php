<?php

/**
 * Part of SplTypes package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducks\Component\SplTypes;

/**
 * SplEnum gives the ability to emulate and create enumeration objects natively in PHP.
 *
 * @see SplEnum http://php.net/manual/en/class.splenum.php
 */
abstract class SplEnum extends SplType
{
    /**
     * {@inheritdoc}
     */
    public function __construct($initial_value = null, $strict = true)
    {
        if ($initial_value === null) {
            $initial_value = static::__default;
        }
        $class = new \ReflectionClass($this);
        if (!in_array($initial_value, $class->getConstants(), $strict)) {
            throw new \UnexpectedValueException('Value not a const in enum ' . $class->getShortName());
        }
        $this->__default = $initial_value;
    }

    /**
     * Returns all consts (possible values) as an array.
     *
     * @param bool $include_default Whether to include __default property.
     * @return array
     */
    final public function getConstList($include_default = false)
    {
        $class = new \ReflectionClass($this);
        $constants = $class->getConstants();
        if (!$include_default) {
            unset($constants['__default']);
        }
        return $constants;
    }
}
