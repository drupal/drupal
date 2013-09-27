<?php

namespace Gliph\Graph;


class UndirectedAdjacencyListTest extends AdjacencyListBase {

    /**
     * @var UndirectedAdjacencyList
     */
    protected $g;

    /**
     * Creates a set of vertices and an empty graph for testing.
     */
    public function setUp() {
        parent::setUp();
        $this->g = new UndirectedAdjacencyList();
    }

    public function testAddUndirectedEdge() {
        $this->g->addEdge($this->v['a'], $this->v['b']);

        $this->doCheckVerticesEqual(array($this->v['a'], $this->v['b']));
    }

    public function testRemoveVertex() {
        $this->g->addEdge($this->v['a'], $this->v['b']);

        $this->g->removeVertex(($this->v['a']));
        $this->doCheckVertexCount(1);
    }

    public function testRemoveEdge() {
        $this->g->addEdge($this->v['a'], $this->v['b']);
        $this->g->addEdge($this->v['b'], $this->v['c']);

        $this->g->removeEdge($this->v['b'], $this->v['c']);
        $this->doCheckVertexCount(3);

        $found = array();
        $this->g->eachAdjacent($this->v['a'], function($adjacent) use (&$found) {
            $found[] = $adjacent;
        });

        $this->assertEquals(array($this->v['b']), $found);
    }

    public function testEachEdge() {
        $this->g->addEdge($this->v['a'], $this->v['b']);
        $this->g->addEdge($this->v['b'], $this->v['c']);

        $found = array();
        $this->g->eachEdge(function ($edge) use (&$found) {
            $found[] = $edge;
        });

        $this->assertCount(2, $found);
        $this->assertEquals(array($this->v['a'], $this->v['b']), $found[0]);
        $this->assertEquals(array($this->v['b'], $this->v['c']), $found[1]);

        // Ensure bidirectionality of created edges
        $found = array();
        $this->g->eachAdjacent($this->v['b'], function($adjacent) use (&$found) {
            $found[] = $adjacent;
        });

        $this->assertCount(2, $found);
    }

    /**
     * @expectedException Gliph\Exception\NonexistentVertexException
     */
    public function testRemoveNonexistentVertex() {
        $this->g->removeVertex($this->v['a']);
    }
}
