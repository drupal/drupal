<?php

namespace Gliph\Graph;

use Gliph\TestVertex;

class AdjacencyListBase extends \PHPUnit_Framework_TestCase {

    /**
     * Creates a set of vertices and an empty graph for testing.
     */
    public function setUp() {
        $this->v = array(
            'a' => new TestVertex('a'),
            'b' => new TestVertex('b'),
            'c' => new TestVertex('c'),
            'd' => new TestVertex('d'),
            'e' => new TestVertex('e'),
            'f' => new TestVertex('f'),
            'g' => new TestVertex('g'),
        );
    }

    public function doCheckVerticesEqual($vertices, AdjacencyList $graph = null) {
        $found = array();
        $graph = is_null($graph) ? $this->g : $graph;

        $graph->eachVertex(
            function ($vertex) use (&$found) {
                $found[] = $vertex;
            }
        );

        $this->assertEquals($vertices, $found);
    }

    public function doCheckVertexCount($count, AdjacencyList $graph = null) {
        $found = array();
        $graph = is_null($graph) ? $this->g : $graph;

        $graph->eachVertex(
            function ($vertex) use (&$found) {
                $found[] = $vertex;
            }
        );

        $this->assertCount($count, $found);
    }
}