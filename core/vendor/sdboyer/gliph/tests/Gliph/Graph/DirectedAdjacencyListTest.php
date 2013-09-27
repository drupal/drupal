<?php

namespace Gliph\Graph;

class DirectedAdjacencyListTest extends AdjacencyListBase {

    /**
     * @var DirectedAdjacencyList
     */
    protected $g;

    public function setUp() {
        parent::setUp();
        $this->g = new DirectedAdjacencyList();
    }


    public function testAddDirectedEdge() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);

        $this->doCheckVerticesEqual(array($this->v['a'], $this->v['b']), $this->g);
    }

    public function testRemoveVertex() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->doCheckVertexCount(2);

        $this->g->removeVertex($this->v['b']);
        $this->doCheckVertexCount(1);

        // Ensure that b was correctly removed from a's outgoing edges
        $found = array();
        $this->g->eachAdjacent($this->v['a'], function($to) use (&$found) {
            $found[] = $to;
        });

        $this->assertEquals(array(), $found);
    }


    public function testRemoveEdge() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->doCheckVerticesEqual(array($this->v['a'], $this->v['b']), $this->g);

        $this->g->removeEdge($this->v['a'], $this->v['b']);
        $this->doCheckVertexCount(2);

        $this->assertTrue($this->g->hasVertex($this->v['a']));
        $this->assertTrue($this->g->hasVertex($this->v['b']));
    }

    public function testEachAdjacent() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->g->addDirectedEdge($this->v['a'], $this->v['c']);

        $found = array();
        $this->g->eachAdjacent($this->v['a'], function($to) use (&$found) {
            $found[] = $to;
        });

        $this->assertEquals(array($this->v['b'], $this->v['c']), $found);
    }

    public function testEachEdge() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->g->addDirectedEdge($this->v['a'], $this->v['c']);

        $found = array();
        $this->g->eachEdge(function($edge) use (&$found) {
            $found[] = $edge;
        });

        $this->assertCount(2, $found);
        $this->assertEquals(array($this->v['a'], $this->v['b']), $found[0]);
        $this->assertEquals(array($this->v['a'], $this->v['c']), $found[1]);
    }

    public function testTranspose() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->g->addDirectedEdge($this->v['a'], $this->v['c']);

        $transpose = $this->g->transpose();

        $this->doCheckVertexCount(3, $transpose);
        $this->doCheckVerticesEqual(array($this->v['b'], $this->v['a'], $this->v['c']), $transpose);
    }

    /**
     * @expectedException Gliph\Exception\NonexistentVertexException
     */
    public function testRemoveNonexistentVertex() {
        $this->g->removeVertex($this->v['a']);
    }

    /**
     * @covers \Gliph\Graph\DirectedAdjacencyList::isAcyclic()
     */
    public function testIsAcyclic() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->g->addDirectedEdge($this->v['b'], $this->v['c']);
        $this->assertTrue($this->g->isAcyclic());

        $this->g->addDirectedEdge($this->v['c'], $this->v['a']);
        $this->assertFalse($this->g->isAcyclic());
    }

    /**
     * @covers \Gliph\Graph\DirectedAdjacencyList::getCycles()
     */
    public function testGetCycles() {
        $this->g->addDirectedEdge($this->v['a'], $this->v['b']);
        $this->g->addDirectedEdge($this->v['b'], $this->v['c']);
        $this->g->addDirectedEdge($this->v['c'], $this->v['a']);

        $this->assertEquals(array(array($this->v['c'], $this->v['b'], $this->v['a'])), $this->g->getCycles());
    }
}
