<?php

// Test database lib

namespace Lib {

    use stdClass;

    class Db {

        private static $_queries = [];
        private static $_results = [];

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
                    $retVal->count = self::_getQueryResultCount($query);
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
            $retVal = null;

            if (isset(self::$_results[$resource->query])) {
                $result = self::$_results[$resource->query];
                $retVal = $result->pointer < count($result->results) ? $result->results[$result->pointer] : null;
                $result->pointer++;
            }

            return $retVal;
        }

        public static function addResultForQuery($query, $row) {
            if (!isset(self::$_results[$query])) {
                self::$_results[$query] = (object)[
                    'pointer' => 0,
                    'results' => []
                ];
            }

            self::$_results[$query]->results[] = $row;
        }

        public static function getLastResult() {
            return end(self::$_queries);
        }

        private static function _getQueryResultCount($query) {
            return isset(self::$_results[$query]) ? count(self::$_results[$query]->results) : 0;
        }

    }

}