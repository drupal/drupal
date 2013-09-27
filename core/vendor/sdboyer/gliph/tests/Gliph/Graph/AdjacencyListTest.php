<?php

namespace Gliph\Graph;

class AdjacencyListTest extends AdjacencyListBase {

    protected $v = array();

    /**
     * @var AdjacencyList
     */
    protected $g;

    public function setUp() {
        parent::setUp();
        $this->g = $this->getMockForAbstractClass('\\Gliph\\Graph\\AdjacencyList');
    }

    /**
     * Tests that an exception is thrown if a string vertex is provided.
     *
     * @expectedException \Gliph\Exception\InvalidVertexTypeException
     */
    public function testAddStringVertex() {
        $this->g->addVertex('a');
    }

    /**
     * Tests that an exception is thrown if an integer vertex is provided.
     *
     * @expectedException \Gliph\Exception\InvalidVertexTypeException
     */
    public function testAddIntegerVertex() {
        $this->g->addVertex(1);
    }

    /**
     * Tests that an exception is thrown if a float vertex is provided.
     *
     * @expectedException \Gliph\Exception\InvalidVertexTypeException
     */
    public function testAddFloatVertex() {
        $this->g->addVertex((float) 1);
    }

    /**
     * Tests that an exception is thrown if an array vertex is provided.
     *
     * @expectedException \Gliph\Exception\InvalidVertexTypeException
     */
    public function testAddArrayVertex() {
        $this->g->addVertex(array());
    }

    /**
     * Tests that an exception is thrown if a resource vertex is provided.
     *
     * @expectedException \Gliph\Exception\InvalidVertexTypeException
     */
    public function testAddResourceVertex() {
        $this->g->addVertex(fopen(__FILE__, 'r'));
    }

    public function testAddVertex() {
        $this->g->addVertex($this->v['a']);

        $this->assertTrue($this->g->hasVertex($this->v['a']));
        $this->doCheckVertexCount(1, $this->g);
    }

    public function testAddVertexTwice() {
        // Adding a vertex twice should be a no-op.
        $this->g->addVertex($this->v['a']);
        $this->g->addVertex($this->v['a']);

        $this->assertTrue($this->g->hasVertex($this->v['a']));
        $this->doCheckVertexCount(1, $this->g);
    }

    /**
     * @expectedException Gliph\Exception\NonexistentVertexException
     */
    public function testEachAdjacentMissingVertex() {
        $this->g->eachAdjacent($this->v['a'], function() {});
    }
}
