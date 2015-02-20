<?php

/**
 * DX API Abstraction Layer
 */

namespace Lib {

    use Api;
    use Exception;
    use Lib;

    // Check for a composer autoloader and require it if found
    if (is_readable('./vendor/autoload.php')) {
        require_once('./vendor/autoload.php');
    }

    class Bootstrap {

        private static $_initialized = false;

        public static function initialize() {
            if (!self::$_initialized) {
                spl_autoload_register('Lib\\Bootstrap::classLoader');
                self::$_initialized = true;
            }
        }

        /**
         * Class auto loader
         */
        private static function classLoader($library) {

            $library = explode('\\', $library);

            // Pop off the actual class name to preserve casing
            $fileName = array_pop($library);

            // Find the path to the namespace
            $filePath = '.';
            foreach ($library as $namespace) {
                $filePath .= '/' . strtolower($namespace);
            }

            $filePath .= '/' . $fileName . '.php';
            if (is_readable($filePath)) {
                require_once($filePath);
            }

        }

    }

    Bootstrap::initialize();

}
