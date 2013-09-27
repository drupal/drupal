<?php

namespace Gliph\Traversal;


use Gliph\Exception\NonexistentVertexException;
use Gliph\Graph\DirectedAdjacencyList;
use Gliph\TestVertex;
use Gliph\Visitor\DepthFirstNoOpVisitor;

class DepthFirstTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DirectedAdjacencyList
     */
    protected $g;
    protected $v;

    public function setUp() {
        $this->g = new DirectedAdjacencyList();
        $this->v = array(
            'a' => new TestVertex('a'),
            'b' => new TestVertex('b'),
            'c' => new TestVertex('c'),
            'd' => new TestVertex('d'),
            'e' => new TestVertex('e'),
            'f' => new TestVertex('f'),
        );
        extract($this->v);

        $this->g->addDirectedEdge($a, $b);
        $this->g->addDirectedEdge($b, $c);
        $this->g->addDirectedEdge($a, $c);
        $this->g->addDirectedEdge($b, $d);
    }

    public function testBasicAcyclicDepthFirstTraversal() {
        $visitor = $this->getMock('Gliph\\Visitor\\DepthFirstNoOpVisitor');
        $visitor->expects($this->exactly(4))->method('onInitializeVertex');
        $visitor->expects($this->exactly(0))->method('onBackEdge');
        $visitor->expects($this->exactly(4))->method('onStartVertex');
        $visitor->expects($this->exactly(4))->method('onExamineEdge');
        $visitor->expects($this->exactly(4))->method('onFinishVertex');

        DepthFirst::traverse($this->g, $visitor);
    }

    public function testDirectCycleDepthFirstTraversal() {
        extract($this->v);

        $this->g->addDirectedEdge($d, $b);

        $visitor = $this->getMock('Gliph\\Visitor\\DepthFirstNoOpVisitor');
        $visitor->expects($this->exactly(1))->method('onBackEdge');

        DepthFirst::traverse($this->g, $visitor);
    }

    public function testIndirectCycleDepthFirstTraversal() {
        extract($this->v);

        $this->g->addDirectedEdge($d, $a);

        $visitor = $this->getMock('Gliph\\Visitor\\DepthFirstNoOpVisitor');
        $visitor->expects($this->exactly(1))->method('onBackEdge');

        DepthFirst::traverse($this->g, $visitor, $a);
    }

    /**
     * @covers Gliph\Traversal\DepthFirst::traverse
     * @expectedException Gliph\Exception\RuntimeException
     */
    public function testExceptionOnEmptyTraversalQueue() {
        extract($this->v);

        // Create a cycle that ensures there are no source vertices
        $this->g->addDirectedEdge($d, $a);
        DepthFirst::traverse($this->g, new DepthFirstNoOpVisitor());
    }

    /**
     * @covers Gliph\Traversal\DepthFirst::traverse
     */
    public function testProvideQueueAsStartPoint() {
        extract($this->v);

        $queue = new \SplQueue();
        $queue->push($a);
        $queue->push($e);

        $this->g->addVertex($a);
        $this->g->addVertex($e);

        DepthFirst::traverse($this->g, new DepthFirstNoOpVisitor(), $queue);
    }

    /**
     * @covers \Gliph\Traversal\DepthFirst::toposort
     * @expectedException Gliph\Exception\RuntimeException
     *   Thrown by the visitor after adding a cycle to the graph.
     */
    public function testToposort() {
        extract($this->v);

        $this->assertEquals(array($c, $d, $b, $a), DepthFirst::toposort($this->g, $a));

        $this->g->addDirectedEdge($d, $a);
        DepthFirst::toposort($this->g, $a);
    }
}
