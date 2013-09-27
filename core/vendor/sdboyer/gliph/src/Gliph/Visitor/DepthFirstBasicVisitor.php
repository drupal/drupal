<?php

namespace Gliph\Visitor;

use Gliph\Exception\WrongVisitorStateException;

/**
 * Basic depth-first visitor.
 *
 * This visitor records reachability data for each vertex and creates a
 * topologically sorted list.
 */
class DepthFirstBasicVisitor extends DepthFirstToposortVisitor {

    /**
     * @var \SplObjectStorage
     */
    public $active;

    /**
     * @var \SplObjectStorage
     */
    protected $paths;

    public function __construct() {
        $this->active = new \SplObjectStorage();
        $this->paths = new \SplObjectStorage();
    }

    public function onInitializeVertex($vertex, $source, \SplQueue $queue) {
        parent::onInitializeVertex($vertex, $source, $queue);

        $this->paths[$vertex] = array();
    }

    public function onStartVertex($vertex, \Closure $visit) {
        parent::onStartVertex($vertex, $visit);

        $this->active->attach($vertex);
        if (!isset($this->paths[$vertex])) {
            $this->paths[$vertex] = array();
        }
    }

    public function onExamineEdge($from, $to, \Closure $visit) {
        parent::onExamineEdge($from, $to, $visit);

        foreach ($this->active as $vertex) {
            // TODO this check makes this less efficient - find a better algo
            if (!in_array($to, $this->paths[$vertex])) {
                $path = $this->paths[$vertex];
                $path[] = $to;
                $this->paths[$vertex] = $path;
            }
        }
    }

    public function onFinishVertex($vertex, \Closure $visit) {
        parent::onFinishVertex($vertex, $visit);

        $this->active->detach($vertex);
    }

    /**
     * Returns an array of all vertices reachable from the given vertex.
     *
     * @param object $vertex
     *   A vertex present in the graph for
     *
     * @return array|bool
     *   An array of reachable vertices, or FALSE if the vertex could not be
     *   found in the reachability data.
     *
     * @throws WrongVisitorStateException
     *   Thrown if reachability data is requested before the traversal algorithm
     *   completes.
     */
    public function getReachable($vertex) {
        if ($this->getState() !== self::COMPLETE) {
            throw new WrongVisitorStateException('Reachability data cannot be retrieved until traversal is complete.');
        }

        if (!isset($this->paths[$vertex])) {
            return FALSE;
        }

        return $this->paths[$vertex];
    }
}