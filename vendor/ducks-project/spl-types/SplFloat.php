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
 * The SplFloat class is used to enforce strong typing of the float type.
 *
 * @see http://php.net/manual/en/class.splfloat.php
 */
class SplFloat  extends SplType {

    /**
     * @var float
     */
    const __default = 0;

    /**
     * Creates a new value of some type
     *
     * @param mixed $initial_value Type and default value depends on the extension class.
     * @param bool $strict Whether to set the object's sctrictness.
     * @return void
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct($initial_value, $strict=null) {
        $class = new \ReflectionClass($this);
        if(!is_float($initial_value) && !is_int($initial_value)) {
            throw new \UnexpectedValueException('Value not a float');
        }
        $this->__default = $initial_value;
    }

}
