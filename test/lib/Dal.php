<?php

class DalObject extends Lib\Dal {

    protected static $_dbTable = 'test';
    protected static $_dbPrimaryKey = 'id';
    protected static $_dbMap = [
        'id' => 'table_id',
        'prop1' => 'table_prop1'
    ];

    public $id;
    public $prop1;

}

class DalTest extends PHPUnit_Framework_TestCase {

    /**
     * @covers Lib\Dal::__construct
     */
    public function testConstructWithObject() {
        $this->_testCreatedObject(function($row) {
            $obj = new DalObject($row);
            return $obj;
        });
    }

    /**
     * @covers Lib\Dal::__construct
     * @covers Lib\Dal::_getById
     * @covers Lib\Dal::_instantiateThisObject
     */
    public function testConstructWithNumber() {
        $idToCheck = 86;
        $obj = new DalObject($idToCheck);
        $query = Lib\Db::getLastResult();
        $this->assertEquals($query->query, 'SELECT `table_id`, `table_prop1` FROM `test` WHERE `table_id` = :id LIMIT 1');
        $this->assertEquals($query->params, [ ':id' => $idToCheck ]);
    }

    /**
     * @covers Lib\Dal::getDbTable
     * @covers Lib\Dal::getDbMap
     * @covers Lib\Dal::getDbPrimaryKey
     */
    public function testGetters() {
        $this->assertEquals(DalObject::getDbTable(), 'test');
        $this->assertEquals(DalObject::getDbMap(), [
            'id' => 'table_id',
            'prop1' => 'table_prop1'
        ]);
        $this->assertEquals(DalObject::getDbPrimaryKey(), 'id');
    }

    /**
     * @covers Lib\Dal::query
     */
    public function testQuery() {
        $query = DalObject::query();
        $this->assertInstanceOf('Lib\DbQuery', $query, '::query should return an instance of Lib\\DbQuery');
    }

    /**
     * @covers Lib\Dal::sync
     */
    public function testInsertWithSync() {
        $row = new DalObject();
        $row->prop1 = 'dude';
        $this->_testInsert($row);
    }

    /**
     * @covers Lib\Dal::sync
     */
    public function testInsertObjectWithSync() {
        $row = new DalObject();
        $row->prop1 = (object)[
            'thing' => 'the'
        ];
        $row->sync();
        $query = Lib\Db::getLastResult();
        $this->assertEquals($query->params, [
            ':prop1' => '{"thing":"the"}'
        ]);
    }

    /**
     * @covers Lib\Dal::sync
     */
    public function testForceInsertWithSync() {
        $row = new DalObject();
        $row->id = 119;
        $row->prop1 = 'dude';
        $this->_testInsert($row, true);
    }

    /**
     * @covers Lib\Dal::sync
     */
    public function testUpdateWithSync() {

        $row = new DalObject();
        $row->id = 5;
        $row->prop1 = 'dude';
        $result = $row->sync();

        $query = Lib\Db::getLastResult();

        // Should correctly generate the query
        $this->assertEquals($query->query, 'UPDATE `test` SET `table_prop1` = :prop1 WHERE `table_id` = :id');

        // Should have the correct parameters
        $this->assertCount(2, $query->params);
        $this->assertEquals($query->params[':prop1'], $row->prop1);
        $this->assertEquals($query->params[':id'], $row->id);

        // Should return true for successful update
        $this->assertTrue($result);

    }

    /**
     * @covers Lib\Dal::copyFromDbRow
     */
    public function testCopyFromDbRow() {
        $this->_testCreatedObject(function($row) {
            $obj = new DalObject();
            $obj->copyFromDbRow($row);
            return $obj;
        });
    }

    /**
     * @covers Lib\Dal::createFromDbRow
     */
    public function testCreateFromDbRow() {
        $this->_testCreatedObject(function($row) {
            return DalObject::createFromDbRow($row);
        });
    }

    /**
     * @covers Lib\Dal::getById
     * @covers Lib\Dal::_getById
     */
    public function testGetById() {
        $idToCheck = 86;
        $sql = 'SELECT `table_id`, `table_prop1` FROM `test` WHERE `table_id` = :id LIMIT 1';
        Lib\Db::addResultForQuery($sql, (object)[
            'table_id' => $idToCheck,
            'table_prop1' => 'first'
        ]);

        // Verify that the object was instantiated and populated
        $obj = DalObject::getById($idToCheck);
        $this->assertInstanceOf('DalObject', $obj);
        $this->assertEquals($obj->id, $idToCheck);
        $this->assertEquals($obj->prop1, 'first');

        // Validate the generated SQL query
        $query = Lib\Db::getLastResult();
        $this->assertEquals($query->query, $sql);
        $this->assertEquals($query->params, [ ':id' => $idToCheck ]);
    }

    /**
     * @expectedException Exception
     * @covers Lib\Dal::getById
     * @covers Lib\Dal::_getById
     */
    public function testGetByIdFailure() {
        $obj = DalObject::getById('One one three eight');
    }

    /**
     * @covers Lib\Dal::delete
     * @covers Lib\Dal::deleteById
     * @covers Lib\Dal::_instantiateThisObject
     */
    public function testDelete() {
        $idToDelete = 97;
        DalObject::deleteById($idToDelete);
        $query = Lib\Db::getLastResult();
        $this->assertEquals($query->query, 'DELETE FROM `test` WHERE `table_id` = :id');
        $this->assertEquals($query->params, [
            ':id' => $idToDelete
        ]);
    }

    private function _testCreatedObject($createFunc) {
        $row = (object)[
            'table_id' => '3',
            'table_prop1' => 'dude'
        ];

        $obj = $createFunc($row);

        $this->assertEquals($obj->id, $row->table_id);
        $this->assertTrue(is_int($obj->id), 'primary key should be an integer');
        $this->assertEquals($obj->prop1, $row->table_prop1);
    }

    private function _testInsert($row, $forceInsert = false) {
        $result = $row->sync($forceInsert);

        $query = Lib\Db::getLastResult();

        // Should correctly generate the query
        $this->assertEquals($query->query, 'INSERT INTO `test` (`table_prop1`) VALUES (:prop1)');

        // Should have the correct parameters
        $this->assertCount(1, $query->params);
        $this->assertEquals($query->params, [ ':prop1' => $row->prop1 ]);

        // Should return true for a successful insert
        $this->assertTrue($result);

        // Should assign the insert ID to the primary key
        $this->assertEquals(1, $row->id);
    }

}