<?php

namespace Gliph\Visitor;

/**
 * Visitor that collects strongly connected component data from Tarjan's
 * strongly connected components algorithm.
 */
class TarjanSCCVisitor {

    protected $components = array();

    protected $currentComponent;

    public function newComponent() {
        // Ensure the reference is broken
        unset($this->currentComponent);
        $this->currentComponent = array();
        $this->components[] = &$this->currentComponent;
    }

    public function addToCurrentComponent($vertex) {
        $this->currentComponent[] = $vertex;
    }

    public function getComponents() {
        return $this->components;
    }

    public function getConnectedComponents() {
        // TODO make this less stupid
        return array_values(array_filter($this->components, function($component) {
            return count($component) > 1;
        }));
    }
}