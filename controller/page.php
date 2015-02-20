<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    abstract class Page {

        public abstract static function generate(array $params);

        public static final function render(array $params) {

            // Kick off page specific rendering
            static::generate($params);

        }

    }

}
