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
                $this->getById($obj);
            } else if (is_object($obj)) {
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

            if (property_exists($this, '_dbTable') && property_exists($this, '_dbMap')) {

                $dbParams = array();

                // Determine if a primary key was set
                $primaryKey = property_exists($this, '_dbPrimaryKey') ? $this->_dbPrimaryKey : false;
                $primaryKeyValue = 0;
                if ($primaryKey) {
                    $primaryKeyValue = (int) $this->$primaryKey;
                }

                // If the primary key value is non-zero, do an UPDATE
                $method = $primaryKeyValue !== 0 && !$forceInsert ? 'UPDATE' : 'INSERT';
                $parameters = [];

                foreach ($this->_dbMap as $property => $column) {
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
                    $query .= ' INTO `' . $this->_dbTable . '` (`' . implode('`,`', $this->_dbMap) . '`) VALUES (' . implode(',', $parameters) . ')';
                    $query = str_replace('`' . $this->_dbMap[$primaryKey] . '`,', '', $query);
                } else {
                    $query .= ' `' . $this->_dbTable . '` SET ' . implode(',', $parameters) . ' WHERE `' . $this->_dbMap[$primaryKey] . '` = :' . $primaryKey;
                }

                $retVal = Db::Query($query, $params);

                // Save the ID for insert
                if ('INSERT' === $method && isset($retVal->insertId)) {
                    $this->$primaryKey = $retVal->insertId;
                    $retVal = $retVal->count;
                }

            }

            return $retVal > 0;

        }

        public static function query() {
            return new DbQuery(self::getDbTable(), self::getDbMap(), self::getDbPrimaryKey());
        }

        /**
         * Creates an object from the passed database row
         */
        public function copyFromDbRow($obj) {
            if (property_exists($this, '_dbMap') && is_object($obj)) {
                foreach($this->_dbMap as $property => $column) {
                    if (property_exists($obj, $column) && property_exists($this, $property)) {
                        $this->$property = $obj->$column;
                        if ($column === $this->_dbPrimaryKey) {
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

            if ($this->_verifyProperties()) {
                $primaryKey = $this->_dbPrimaryKey;
                if ($this->$primaryKey) {
                    $query = 'DELETE FROM `' . $this->_dbTable . '` WHERE ' . $this->_dbMap[$primaryKey] . ' = :id';
                    $params = array( ':id' => $this->$primaryKey );
                    $retVal = Db::Query($query, $params);
                }
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
            if (self::_verifyProperties($this)) {
                if (is_numeric($id)) {
                    $cacheKey = 'Lib:Dal:' . $this->_dbTable . '_getById_' . $id;
                    $retVal = Cache::Get($cacheKey);

                    if (!$retVal) {
                        $query  = 'SELECT `' . implode('`, `', $this->_dbMap) . '` FROM `' . $this->_dbTable . '` ';
                        $query .= 'WHERE `' . $this->_dbMap[$this->_dbPrimaryKey] . '` = :id LIMIT 1';

                        $result = Db::Query($query, [ ':id' => $id ]);
                        if (null !== $result && $result->count === 1) {
                            $this->copyFromDbRow(Db::Fetch($result));
                        }
                        Cache::Set($cacheKey, $retVal);
                    }
                } else {
                    throw new Exception('ID must be a number');
                }

            } else {
                throw new Exception('Class must have "_dbTable", "_dbMap", and "_dbPrimaryKey" properties to use method "getById"');
            }
        }

        /**
         * Instantiates an object of the current class and returns it
         */
        private static function _instantiateThisObject() {
            $className = get_called_class();
            return new $className();
        }

        /**
         * Ensures that the class has all the properties needed for these methods to work
         */
        private static function _verifyProperties($obj = null) {
            $obj = null === $obj ? self::_instantiateThisObject() : $obj;
            return property_exists($obj, '_dbTable') && property_exists($obj, '_dbMap') && property_exists($obj, '_dbPrimaryKey');
        }

    }

}