<?php

namespace Api {

  use Lib;

  class DbTest extends Lib\Dal {

    protected static $_dbTable = 'test_table';

    protected static $_dbMap = [
      'id' => 'table_id',
      'date' => 'table_date',
      'name' => 'table_name'
    ];

  }

}