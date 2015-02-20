<?hh

namespace Lib {

    class DbQuery<T as Dal> {

        private $_verb;
        private $_where = [];
        private $_sort = [];
        private $_group = [];
        private $_limit = [];
        private $_offset = [];

        public static function create<T as Dal>() {
            return new DbQuery<T>();
        }

        public function eq(string $column, $value) {
            echo get_class(T);
        }

        public function ne(string $column, $value) {

        }

        public function gt(string $column, $value) {

        }

        public function lt(string $column, $value) {

        }

        public function lte(string $column, $value) {

        }

        public function gte(string $column, $value) {

        }

        public function in(string $column, array $values) {

        }

        public function andQuery(DbQuery<T> $query) {

        }

        public function orQuery(DbQuery<T> $query) {

        }

        public function execute() {

        }

    }

}