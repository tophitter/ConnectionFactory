<?php
    /**
     * User: Jason Townsend
     * Date: 25/12/2015 04:06
     */

    namespace AmaranthNetwork\Enums;
    
    use ReflectionClass;
    use InvalidArgumentException;
    use LogicException;

    abstract class Enum {
        /**
         * The selected enumerator value
         *
         * @var null|bool|int|float|string
         */
        private $value;

        /**
         * The ordinal number of the enumerator
         *
         * @var null|int
         */
        private $ordinal;

        /**
         * An array of available constants by class
         *
         * @var array ["$class" => ["$name" => $value, ...], ...]
         */
        private static $constants = array();

        /**
         * Already instantiated enumerators
         *
         * @var array ["$class" => ["$name" => $instance, ...], ...]
         */
        private static $instances = array();

        /**
         * Constructor
         *
         * @param null|bool|int|float|string $value   The value of the enumerator
         * @param int|null                   $ordinal The ordinal number of the enumerator
         */
        private function __construct($value, $ordinal = null) {
            $this->value   = $value;
            $this->ordinal = $ordinal;
        }

        /**
         * Get the name of the enumerator
         * @return string
         * @see getName()
         * @throws \ReflectionException
         */
        public function __toString() { return $this->getName(); }

        /**
         * @throws LogicException Enums are not cloneable
         *                        because instances are implemented as singletons
         */
        private function __clone() { throw new LogicException('Enums are not cloneable'); }

        /**
         * @throws LogicException Enums are not serializable
         *                        because instances are implemented as singletons
         */
        public function __sleep()  { throw new LogicException('Enums are not serializable'); }

        /**
         * @throws LogicException Enums are not serializable
         *                        because instances are implemented as singletons
         */
        public function __wakeup() { throw new LogicException('Enums are not serializable'); }

        /**
         * Get the value of the enumerator
         *
         * @return null|bool|int|float|string
         */
        final public function getValue() { return $this->value; }

        /**
         * Get the name of the enumarator
         *
         * @param bool $spaces
         *
         * @return string
         * @throws \ReflectionException
         */
        final public function getName($spaces = false) {
            if($spaces == true){
                $name = array_search($this->value, self::detectConstants(get_called_class()), true);
                $name = str_replace('_', ' ', $name);
                return $name;
            }
            else
                return array_search($this->value, self::detectConstants(get_called_class()), true);
        }

        /**
         * Get the name of the enumarator in lowercase
         *
         * @param bool $spaces
         *
         * @return string
         * @throws \ReflectionException
         */
        final public function getNameTolower($spaces = false) {
            if($spaces === true){
                $name = array_search($this->value, self::detectConstants(get_called_class()), true);
                $name = str_replace('_', ' ', $name);
                return strtolower($name);
            }
            else
                return strtolower(array_search($this->value, self::detectConstants(get_called_class()), true));
        }

        /**
         * Get the ordinal number of the enumerator
         * @return int
         * @throws \ReflectionException
         */
        final public function getOrdinal() {
            if ($this->ordinal !== null)
                return $this->ordinal;

            // detect ordinal
            $ordinal = 0;
            $value   = $this->value;

            foreach (self::detectConstants(get_called_class()) as $constValue) {
                if ($value === $constValue)
                    break;

                ++$ordinal;
            }

            $this->ordinal = $ordinal;
            return $ordinal;
        }

        /**
         * Compare this enumerator against another and check if it's the same.
         *
         * @param mixed $enumerator
         * @return bool
         */
        final public function is($enumerator)  { return $this === $enumerator || $this->value === $enumerator; }

        /**
         * Get an enumerator instance of the given value or instance
         *
         * @param static|null|bool|int|float|string $value
         *
         * @return static
         * @throws InvalidArgumentException On an unknwon or invalid value
         * @throws LogicException           On ambiguous constant values*@throws \ReflectionException
         * @throws \ReflectionException
         */
        final public static function get($value) {
            if ($value instanceof static && get_class($value) === get_called_class())
                return $value;

            $class     = get_called_class();
            $constants = self::detectConstants($class);
            $name      = array_search($value, $constants, true);
            if ($name === false) {
                $message = is_scalar($value)
                    ? 'Unknown value ' . var_export($value, true)
                    : 'Invalid value of type ' . (is_object($value) ? get_class($value) : gettype($value));
                throw new InvalidArgumentException($message);
            }

            if (!isset(self::$instances[$class][$name]))
                self::$instances[$class][$name] = new $class($constants[$name]);

            return self::$instances[$class][$name];
        }

        /**
         * Get an enumarator instance by the given name
         *
         * @param string $name The name of the enumerator
         * @return static
         * @throws InvalidArgumentException On an invalid or unknown name
         * @throws LogicException           On ambiguous values
         */
        final public static function getByName($name) {
            $name  = (string) $name;
            $class = get_called_class();
            if (isset(self::$instances[$class][$name]))
                return self::$instances[$class][$name];

            $const = $class . '::' . $name;
            if (!defined($const))
                throw new InvalidArgumentException($const . ' not defined');

            return self::$instances[$class][$name] = new $class(constant($const));
        }

        /**
         * Get an enumeration instance by the given ordinal number
         *
         * @param int $ordinal The ordinal number or the enumerator
         *
         * @return static
         * @throws InvalidArgumentException On an invalid ordinal number
         * @throws LogicException           On ambiguous values*@throws \ReflectionException
         */
        final public static function getByOrdinal($ordinal) {
            $ordinal   = (int) $ordinal;
            $class     = get_called_class();
            $constants = self::detectConstants($class);
            $item      = array_slice($constants, $ordinal, 1, true);

            if (empty($item)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid ordinal number, must between 0 and %s',
                    count($constants) - 1
                ));
            }

            $name = key($item);
            if (isset(self::$instances[$class][$name]))
                return self::$instances[$class][$name];

            return self::$instances[$class][$name] = new $class(current($item), $ordinal);
        }

        /**
         * Clear all instantiated enumerators of the called class
         *
         * NOTE: This can break singleton behavior ... use it with caution!
         *
         * @return void
         */
        final public static function clear(){
            $class = get_called_class();
            unset(self::$instances[$class], self::$constants[$class]);
        }

        /**
         * Get a list of enumerator instances ordered by ordinal number
         * @return static[]
         * @throws \ReflectionException
         */
        final public static function getEnumerators() { return array_map('self::getByName', array_keys(self::detectConstants(get_called_class()))); }

        /**
         * Get all available constants of the called class
         * @return array
         * @throws \ReflectionException
         */
        final public static function getConstants() { return self::detectConstants(get_called_class()); }

        /**
         * Is the given enumerator part of this enumeration
         *
         * @param static|null|bool|int|float|string $value
         *
         * @return bool
         * @throws \ReflectionException
         */
        final public static function has($value) {
            if ($value instanceof static && get_class($value) === get_called_class()) {
                return true;
            }

            $class     = get_called_class();
            $constants = self::detectConstants($class);

            return in_array($value, $constants, true);
        }

        /**
         * Detect all available constants by the given class
         *
         * @param string $class
         *
         * @return array
         * @throws \ReflectionException
         */
        private static function detectConstants($class) {
            if (!isset(self::$constants[$class])) {
                $reflection = new ReflectionClass($class);
                $constants  = $reflection->getConstants();

                // values needs to be unique
                $ambiguous = array();
                foreach ($constants as $value) {
                    $names = array_keys($constants, $value, true);

                    if (count($names) > 1)
                        $ambiguous[var_export($value, true)] = $names;
                }
                if (!empty($ambiguous)) {
                    throw new LogicException(
                        'All possible values needs to be unique. The following are ambiguous: '
                        . implode(', ', array_map(function ($names) use ($constants) {
                            return implode('/', $names) . '=' . var_export($constants[$names[0]], true);
                        }, $ambiguous))
                    );
                }

                // This is required to make sure that constants of base classes will be the first
                while (($reflection = $reflection->getParentClass()) && $reflection->name !== __CLASS__)
                    $constants = $reflection->getConstants() + $constants;

                self::$constants[$class] = $constants;
            }

            return self::$constants[$class];
        }

        /**
         * Get an enumarator instance by the given name.
         *
         * This will be called automatically on calling a method
         * with the same name of a defined enumerator.
         *
         * @param string $method The name of the enumeraotr (called as method)
         * @param array  $args   There should be no arguments
         * @return static
         * @throws InvalidArgumentException On an invalid or unknown name
         * @throws LogicException           On ambiguous constant values
         */
        final public static function __callStatic($method, array $args) { return self::getByName($method); }
    }
