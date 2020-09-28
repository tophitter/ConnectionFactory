<?php
    /**
     * User: Jason Townsend
     * Date: 25/12/2015 04:08
     */

    namespace AmaranthNetwork\Enums;

    use RuntimeException;
    use LogicException;

    trait EnumSerializableTrait {
        /**
         * The method will be defined by MabeEnum\Enum
         * @return null|bool|int|float|string
         */
        abstract public function getValue();
        /**
         * Serialized the value of the enumeration
         * This will be called automatically on `serialize()` if the enumeration implements the `Serializable` interface
         * @return string
         */
        public function serialize()  { return serialize($this->getValue()); }

        /**
         * Unserializes a given serialized value and push it into the current instance
         * This will be called automatically on `unserialize()` if the enumeration implements the `Serializable` interface
         * @param string $serialized
         * @throws RuntimeException On an unknown or invalid value
         * @throws LogicException   On changing numeration value by calling this directly
         */
        public function unserialize($serialized)
        {
            $value     = unserialize($serialized);
            $constants = self::getConstants();
            $name      = array_search($value, $constants, true);

            if ($name === false) {
                $message = is_scalar($value)
                    ? 'Unknown value ' . var_export($value, true)
                    : 'Invalid value of type ' . (is_object($value) ? get_class($value) : gettype($value));
                throw new RuntimeException($message);
            }

            $class      = get_class($this);
            $enumerator = $this;

            $closure    = function () use ($class, $name, $value, $enumerator) {
                if ($this->value !== null && $value !== null)
                    throw new LogicException('Do not call this directly - please use unserialize($enum) instead');

                $this->value = $value;

                if (!isset(self::$instances[$class][$name]))
                    self::$instances[$class][$name] = $enumerator;
            };
            $closure = $closure->bindTo($this, 'MabeEnum\Enum');
            $closure();
        }
    }
