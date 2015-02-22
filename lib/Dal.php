<?php

namespace Lib {

    use Exception;

    abstract class Dal {

        private $_where = [];
        private $_params = [];
        private $_sort = [];

        /**
         * Constructor
         */
        public function __construct($obj = null) {

            if (is_numeric($obj)) {
                $this->_getById($obj);
            } else if (is_object($obj) || is_array($obj)) {
                $this->copyFromDbRow($obj);
            }

        }

        /**
         * Getter for _dbTable
         */
        public static function getDbTable() {
            return static::$_dbTable;
        }

        /**
         * Getter for _dbPrimaryKey
         */
        public static function getDbPrimaryKey() {
            return isset(static::$_dbPrimaryKey) ? static::$_dbPrimaryKey : null;
        }

        /**
         * Getter for _dbMap
         */
        public static function getDbMap() {
            return static::$_dbMap;
        }

        /**
         * Syncs the current object to the database
         */
        public function sync($forceInsert = false) {

            $retVal = 0;
            $dbParams = [];

            $table = static::getDbTable();
            $map = static::getDbMap();

            // Determine if a primary key was set
            $primaryKey = static::getDbPrimaryKey();
            $primaryKeyValue = 0;
            if ($primaryKey) {
                $primaryKeyValue = (int) $this->$primaryKey;
            }

            // If the primary key value is non-zero, do an UPDATE
            $method = $primaryKeyValue !== 0 && !$forceInsert ? 'UPDATE' : 'INSERT';
            $parameters = [];

            foreach ($map as $property => $column) {
                // Primary only gets dropped in for UPDATEs
                if (($primaryKey === $property && 'UPDATE' === $method) || $primaryKey !== $property) {
                    $paramName = ':' . $property;

                    // Serialize objects going in as JSON
                    $value = $this->$property;
                    if (is_object($value)) {
                        $value = json_encode($value);
                    }
                    $params[$paramName] = $value;

                    if ('INSERT' === $method) {
                        $parameters[] = $paramName;
                    } else if ($primaryKey != $property) {
                        $parameters[] = '`' . $column . '` = ' . $paramName;
                    }
                }
            }

            // Build and execute the query
            $query = $method;
            if ('INSERT' === $method) {
                $query .= ' INTO `' . $table . '` (`' . implode('`,`', $map) . '`) VALUES (' . implode(',', $parameters) . ')';
                $query = str_replace('`' . $map[$primaryKey] . '`,', '', $query);
            } else {
                $query .= ' `' . $table . '` SET ' . implode(',', $parameters) . ' WHERE `' . $map[$primaryKey] . '` = :' . $primaryKey;
            }

            $retVal = Db::Query($query, $params);

            // Save the ID for insert
            if ('INSERT' === $method && isset($retVal->insertId)) {
                $this->$primaryKey = $retVal->insertId;
                $retVal = $retVal->count;
            }

            return $retVal > 0;

        }

        public static function query() {
            return new DbQuery(self::getDbTable(), self::getDbMap(), self::getDbPrimaryKey());
        }

        /**
         * Creates an object from a database row
         */
        public static function createFromDbRow($row) {
            $obj = self::_instantiateThisObject();
            $obj->copyFromDbRow($row);
            return $obj;
        }

        /**
         * Copies properties to an object from a database returned row
         */
        public function copyFromDbRow($row) {
            $map = static::getDbMap();
            if ($map && (is_object($row) || is_array($row))) {

                $row = (object) $row;
                $primaryKey = $map[static::getDbPrimaryKey()];

                foreach($map as $property => $column) {
                    if (property_exists($row, $column) && property_exists($this, $property)) {
                        $this->$property = $row->$column;
                        if ($column === $primaryKey) {
                            $this->$property = (int) $this->$property;
                        }
                    }
                }
            }
        }

        /**
         * Deletes the object from the database
         */
        public function delete() {

            $retVal = false;

            $table = static::getDbTable();
            $map = static::getDbMap();
            $primaryKey = static::getDbPrimaryKey();
            if ($this->$primaryKey) {
                $query = 'DELETE FROM `' . $table . '` WHERE `' . $map[$primaryKey] . '` = :id';
                $params = array( ':id' => $this->$primaryKey );
                $retVal = Db::Query($query, $params);
            }

            return $retVal;

        }

        /**
         * Deletes an object record from the database based on ID
         */
        public static function deleteById($id) {
            $retVal = self::_instantiateThisObject();
            $retVal->id = $id;
            return $retVal->delete();
        }

        public static function getById($id) {
            $obj = self::_instantiateThisObject();
            $obj->_getById($id);
            return $obj;
        }

        /**
         * Gets a record from the database by the primary key
         */
        private function _getById($id) {

            $retVal = null;

            if (is_numeric($id)) {

                $table = static::getDbTable();
                $map = static::getDbMap();
                $primaryKey = static::getDbPrimaryKey();

                $cacheKey = 'Lib:Dal:' . $table . '_getById_' . $id;
                $retVal = Cache::Get($cacheKey);

                if (!$retVal) {
                    $query  = 'SELECT `' . implode('`, `', $map) . '` FROM `' . $table . '` ';
                    $query .= 'WHERE `' . $map[$primaryKey] . '` = :id LIMIT 1';

                    $result = Db::Query($query, [ ':id' => $id ]);
                    if ($result && $result->count) {
                        $this->copyFromDbRow(Db::Fetch($result));
                    }
                    Cache::Set($cacheKey, $retVal);
                }
            } else {
                throw new Exception('ID must be a number');
            }

        }

        /**
         * Instantiates an object of the current class and returns it
         */
        private static function _instantiateThisObject() {
            $className = get_called_class();
            return new $className();
        }

    }

}