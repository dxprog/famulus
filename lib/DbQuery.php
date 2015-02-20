<?php

namespace Lib {

    class DbQuery {

        const Ascending = 'ASC';
        const Descending = 'DESC';

        private $_table;
        private $_map;
        private $_primaryKey;

        private $_verb;
        private $_select = [];
        private $_where = [];
        private $_sort = [];
        private $_group = [];
        private $_limit = [];
        private $_offset = [];
        private $_params = [];

        public function __construct($table, array $map, $primaryKey = null) {
            $this->_table = $table;
            $this->_map = $map;
            $this->_primaryKey = $primaryKey;
        }

        /**
         * Defines columns to return from the query
         * @param array|string Columns to fetch
         * @return DbQuery $this
         */
        public function select($columns) {
            $columns = is_array($columns) ? $columns : [ $columns ];

            foreach ($columns as $column) {
                $column = $this->_getColumnNameFromProperty($column);
                if ($column) {
                    $this->_select[$column] = true;
                }
            }

            return $this;
        }

        /**
         * Retrieves columns matching value
         * @param string $column The column to query
         * @param mixed $value The value to check against
         * @return DbQuery $this
         */
        public function eq($column, $value) {
            return $this->_genericComparison($column, $value, '=');
        }

        /**
         * Retrieves columns not equaling value
         * @param string $column The column to query
         * @param mixed $value The value to check against
         * @return DbQuery $this
         */
        public function ne($column, $value) {
            return $this->_genericComparison($column, $value, '!=');
        }

        /**
         * Retrieves columns greater than value
         * @param string $column The column to query
         * @param int $value The value to check against
         * @return DbQuery $this
         */
        public function gt($column, $value) {
            return $this->_genericComparison($column, $value, '>');
        }

        /**
         * Retrieves columns less than value
         * @param string $column The column to query
         * @param int $value The value to check against
         * @return DbQuery $this
         */
        public function lt($column, $value) {
            return $this->_genericComparison($column, $value, '<');
        }

        /**
         * Retrieves columns less than or equal to value
         * @param string $column The column to query
         * @param int $value The value to check against
         * @return DbQuery $this
         */
        public function lte($column, $value) {
            return $this->_genericComparison($column, $value, '<=');
        }

        /**
         * Retrieves columns greater than or equal to value
         * @param string $column The column to query
         * @param int $value The value to check against
         * @return DbQuery $this
         */
        public function gte($column, $value) {
            return $this->_genericComparison($column, $value, '>=');
        }

        /**
         * Performs a LIKE operation on the column
         * @param string $column The column to query
         * @param string $value The value to check against
         * @return DbQuery $this
         */
        public function like($column, $value) {
            return $this->_genericComparison($column, $value, 'LIKE');
        }

        /**
         * Retrieves columns matching value
         * @param string $column The column to query
         * @param mixed $value The value to check against
         * @return DbQuery $this
         */
        public function in($column, array $values) {
            $column = $this->_getColumnNameFromProperty($column);
            if ($column) {
                $params = [];
                foreach ($values as $value) {
                    $param = $this->_getParamName();
                    $this->_params[$param] = $value;
                    $params[] = $param;
                }
                $this->_where[] = '`' . $column . '` IN (' . implode(', ', array_values($params)) . ')';
            }
            return $this;
        }

        public function andQuery(DbQuery $query) {

        }

        public function orQuery(DbQuery $query) {

        }

        public function sort($column, $direction) {
            if ($direction !== static::Descending && $direction !== static::Ascending) {
                throw new Exception('Invalid sort direction: "' . $direction . '"');
            }

            $column = $this->_getColumnNameFromProperty($column);
            if ($column) {
                $this->_sort[] = '`' . $column . '` ' . $direction;
            }

            return $this;
        }

        /**
         * Builds the SELECT portion of the query
         */
        protected function _buildSelect() {

            // If no specific columns were selected, just get them all
            $columns = count($this->_select) ? array_keys($this->_select) : array_values($this->_map);

            return 'SELECT `' . implode('`, `', $columns) . '` FROM `' . $this->_table . '`';
        }

        /**
         * Builds the WHERE portion of the query
         */
        protected function _buildWhere() {
            return implode(' AND ', $this->_where);
        }

        protected function _buildSort() {
            return implode(', ', $this->_sort);
        }

        /**
         * Builds the SQL query string
         * @return string
         */
        public function build() {
            $query = $this->_buildSelect();

            if (count($this->_where)) {
                $query .= ' WHERE ' . $this->_buildWhere();
            }

            if (count($this->_sort)) {
                $query .= ' ORDER BY ' . $this->_buildSort();
            }

            return $query;
        }

        public function execute() {

        }

        private function _genericComparison($column, $value, $operator) {
            $column = $this->_getColumnNameFromProperty($column);
            if ($column) {

                // Some special checking for null values
                if ($value === null) {
                    $param = 'NULL';
                    $operator = $operator === '=' ? 'IS' : 'IS NOT';
                } else {
                    $param = $this->_getParamName();
                    $this->_params[$param] = $value;
                }

                $this->_where[] = '`' . $column . '` ' . $operator . ' ' . $param;
            }
            return $this;
        }

        private function _getParamName() {
            return ':param' . count($this->_params);
        }

        private function _getColumnNameFromProperty($property) {
            $retVal = isset($this->_map[$property]) ? $this->_map[$property] : null;
            if (!$retVal) {
                throw new Exception('Mapping for property "' . $property . '" doesn\'t exist in schema map');
            }
            return $retVal;
        }

    }

}