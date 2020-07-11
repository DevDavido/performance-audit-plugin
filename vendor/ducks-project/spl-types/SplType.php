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
 * Parent class for all SPL types.
 *
 * @see SplType http://php.net/manual/en/class.spltype.php
 */
abstract class SplType {

    /**
     * Default value
     */
    const __default = null;

    /**
     * Internal enum value
     */
    public $__default;

    /**
     * Creates a new value of some type
     *
     * @param mixed $initial_value Type and default value depends on the extension class.
     * @param bool $strict Whether to set the object's sctrictness.
     * @return void
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct($initial_value=null, $strict=null) {
        if ($initial_value === null) {
            $initial_value = static::__default;
        }
        $class = new \ReflectionClass($this);
        if(!in_array($initial_value, $class->getConstants())) {
            throw new \UnexpectedValueException('Value not a const in enum '.$class->getShortName());
        }
        $this->__default = $initial_value;
    }

    /**
     * Stringify object
     *
     * @return string
     */
    final public function __toString() {
        return (string)$this->__default;
    }

    /**
     * Export object
     *
     * @return SplType
     */
    final public static function __set_state($properties) {
        return new static($properties['__default']);
    }

    /**
     * Dumping object (php > 5.6.0)
     *
     * @return array
     */
    final public function __debugInfo() {
        return array( '__default' => $this->__default);
    }

}
