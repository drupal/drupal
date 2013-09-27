<?php

namespace Gliph;

/**
 * A class that acts as a vertex for more convenient use in tests.
 */
class TestVertex {

    protected $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function __toString() {
        return $this->name;
    }
}