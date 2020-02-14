<?php

class Conn extends mysqli {
    public function __construct() {
        parent::__construct(DB['HOST'], DB['USER'], DB['PASSWORD'], DB['NAME']);
    }
}
