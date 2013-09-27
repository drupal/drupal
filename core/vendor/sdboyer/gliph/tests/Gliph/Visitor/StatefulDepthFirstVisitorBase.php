<?php

namespace Gliph\Visitor;

class StatefulDepthFirstVisitorStub extends StatefulDepthFirstVisitor {}

abstract class StatefulDepthFirstVisitorBase extends \PHPUnit_Framework_TestCase {

    /**
     * Creates a StatefulDepthFirstVisitor in NOT_STARTED state.
     *
     * @return StatefulDepthFirstVisitor
     */
    public function createNotStartedVisitor() {
        return new StatefulDepthFirstVisitorStub();
    }

    /**
     * Creates a StatefulDepthFirstVisitor in IN_PROGRESS state.
     *
     * @return StatefulDepthFirstVisitor
     */
    public function createInProgressVisitor() {
        $stub = new StatefulDepthFirstVisitorStub();

        $prop = new \ReflectionProperty($stub, 'state');
        $prop->setAccessible(TRUE);
        $prop->setValue($stub, StatefulVisitorInterface::IN_PROGRESS);

        return $stub;
    }

    /**
     * Creates a StatefulDepthFirstVisitor in COMPLETED state.
     *
     * @return StatefulDepthFirstVisitor
     */
    public function createCompletedVisitor() {
        $stub = new StatefulDepthFirstVisitorStub();

        $prop = new \ReflectionProperty($stub, 'state');
        $prop->setAccessible(TRUE);
        $prop->setValue($stub, StatefulVisitorInterface::COMPLETE);

        return $stub;
    }

    /**
     * Returns A list of methods and arguments that should be tested for state sensitivity.
     *
     *
     * @return array
     */
    public function stateSensitiveMethods() {
        return array(
            'notStarted' => array(
                array('onInitializeVertex', array(new \stdClass(), TRUE, new \SplQueue())),
                array('beginTraversal', array()),
            ),
            'inProgress' => array(
                array('onStartVertex', array(new \stdClass(), function() {})),
                array('onBackEdge', array(new \stdClass(), function() {})),
                array('onExamineEdge', array(new \stdClass(), new \stdClass(), function() {})),
                array('onFinishVertex', array(new \stdClass(), function() {})),
                array('endTraversal', array()),
            ),
            'completed' => array(
                array(),
            ),
        );
    }

    /**
     * Data provider of visitor methods safe to call from IN_PROGRESS state.
     */
    public function inProgressMethods() {
        $methods = $this->stateSensitiveMethods();
        return $methods['inProgress'];
    }

    /**
     * Data provider of visitor methods safe to call from NOT_STARTED state.
     */
    public function notStartedMethods() {
        $methods = $this->stateSensitiveMethods();
        return $methods['notStarted'];
    }

    /**
     * Data provider of visitor methods safe to call from COMPLETE state.
     */
    public function completedMethods() {
        $methods = $this->stateSensitiveMethods();
        return $methods['completed'];
    }

    /**
     * Data provider of visitor methods not safe to call from NOT_STARTED state.
     */
    public function invalidNotStartedMethods() {
        return array_filter(array_merge($this->inProgressMethods(), $this->completedMethods()));
    }

    /**
     * Data provider of visitor methods not safe to call from COMPLETED state.
     */
    public function invalidCompletedMethods() {
        return array_filter(array_merge($this->notStartedMethods(), $this->inProgressMethods()));
    }

    /**
     * Data provider of visitor methods not safe to call from IN_PROGRESS state.
     */
    public function invalidInProgressMethods() {
        return array_filter(array_merge($this->notStartedMethods(), $this->completedMethods()));
    }

    public function testInitialState() {
        $this->assertEquals(StatefulVisitorInterface::NOT_STARTED, $this->createNotStartedVisitor()->getState());
    }

    public function testBeginTraversal() {
        $vis = $this->createNotStartedVisitor();

        $vis->beginTraversal();
        $this->assertEquals(StatefulVisitorInterface::IN_PROGRESS, $vis->getState());
    }

    public function testEndTraversal() {
        $vis = $this->createInProgressVisitor();

        $vis->endTraversal();
        $this->assertEquals(StatefulVisitorInterface::COMPLETE, $vis->getState());
    }

    /**
     * @dataProvider notStartedMethods
     */
    public function testNotStartedMethods($method, $args) {
        $vis = $this->createNotStartedVisitor();
        call_user_func_array(array($vis, $method), $args);
    }

    /**
     * @dataProvider inProgressMethods
     */
    public function testInProgressMethods($method, $args) {
        $vis = $this->createInProgressVisitor();
        call_user_func_array(array($vis, $method), $args);
    }

    /**
     * @dataProvider invalidInProgressMethods
     * @expectedException \Gliph\Exception\WrongVisitorStateException
     */
    public function testInvalidInProgressMethods($method, $args) {
        call_user_func_array(array($this->createInProgressVisitor(), $method), $args);
    }

    /**
     * @dataProvider invalidNotStartedMethods
     * @expectedException \Gliph\Exception\WrongVisitorStateException
     */
    public function testInvalidNotStartedMethods($method, $args) {
        call_user_func_array(array($this->createNotStartedVisitor(), $method), $args);
    }

    /**
     * @dataProvider invalidCompletedMethods
     * @expectedException \Gliph\Exception\WrongVisitorStateException
     */
    public function testInvalidCompletedMethods($method, $args) {
        call_user_func_array(array($this->createCompletedVisitor(), $method), $args);
    }
}

