<?php

// Test database lib

namespace Lib {

    use stdClass;

    class Db {

        private static $_queries = [];

        public static function Connect() { } // NOOP

        public static function Query($query, $params = null) {

            $retVal = (object)[
                'query' => $query,
                'params' => $params
            ];

            self::$_queries[] = $retVal;

            // Action specific returns
            switch (strtolower(current(explode(' ', $query)))) {
                case 'select':
                    $retVal->count = 0;
                    break;
                case 'insert':
                    $retVal->insertId = 1;
                    $retVal->count = 1;
                    break;
                case 'update':
                case 'delete':
                    $retVal = 1;
                    break;
            }

            return $retVal;
        }

        public static function Fetch($resource) {
            return $resource;
        }

        public static function getLastResult() {
            return end(self::$_queries);
        }

    }

}