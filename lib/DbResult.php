<?php

namespace Lib {

  class DbResult {

    private $_result;

    public function __construct($result) {
      $this->_result = $result;
    }

    public function results() {
      while ($row = Db::Fetch($this->_result)) {
        yeild $row;
      }
    }

  }

}