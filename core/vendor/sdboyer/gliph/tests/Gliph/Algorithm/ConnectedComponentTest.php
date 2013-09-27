<?php

/**
 * @file
 * Contains \Gliph\Algorithm\ConnectedComponentTest.
 */

namespace Gliph\Algorithm;

use Gliph\Graph\DirectedAdjacencyList;
use Gliph\TestVertex;

class ConnectedComponentTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers \Gliph\Algorithm\ConnectedComponent::tarjan_scc()
     */
    public function testTarjanScc() {
        $a = new TestVertex('a');
        $b = new TestVertex('b');
        $c = new TestVertex('c');
        $d = new TestVertex('d');
        $e = new TestVertex('e');
        $f = new TestVertex('f');
        $g = new TestVertex('g');
        $h = new TestVertex('h');

        $graph = new DirectedAdjacencyList();

        $graph->addDirectedEdge($a, $d);
        $graph->addDirectedEdge($a, $b);
        $graph->addDirectedEdge($b, $c);
        $graph->addDirectedEdge($c, $d);
        $graph->addDirectedEdge($d, $a);
        $graph->addDirectedEdge($e, $d);
        $graph->addDirectedEdge($f, $g);
        $graph->addDirectedEdge($g, $h);
        $graph->addDirectedEdge($h, $f);

        $visitor = ConnectedComponent::tarjan_scc($graph);

        $expected_full = array(
            array($c, $b, $d, $a),
            array($e),
            array($h, $g, $f),
        );
        $this->assertEquals($expected_full, $visitor->getComponents());

        $expected_full = array(
            array($c, $b, $d, $a),
            array($h, $g, $f),
        );
        $this->assertEquals($expected_full, $visitor->getConnectedComponents());
    }
}
