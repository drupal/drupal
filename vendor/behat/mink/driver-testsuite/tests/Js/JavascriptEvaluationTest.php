<?php

namespace Behat\Mink\Tests\Driver\Js;

use Behat\Mink\Tests\Driver\TestCase;

class JavascriptEvaluationTest extends TestCase
{
    /**
     * Tests, that `wait` method returns check result after exit.
     */
    public function testWaitReturnValue()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));

        $found = $this->getSession()->wait(5000, '$("#draggable").length == 1');
        $this->assertTrue($found);
    }

    public function testWait()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));

        $waitable = $this->findById('waitable');

        $waitable->click();
        $this->getSession()->wait(3000, '$("#waitable").has("div").length > 0');
        $this->assertEquals('arrived', $this->getAssertSession()->elementExists('css', '#waitable > div')->getText());

        $waitable->click();
        $this->getSession()->wait(3000, 'false');
        $this->assertEquals('timeout', $this->getAssertSession()->elementExists('css', '#waitable > div')->getText());
    }

    /**
     * @dataProvider provideExecutedScript
     */
    public function testExecuteScript($script)
    {
        $this->getSession()->visit($this->pathTo('/index.html'));

        $this->getSession()->executeScript($script);

        sleep(1);

        $heading = $this->getAssertSession()->elementExists('css', 'h1');
        $this->assertEquals('Hello world', $heading->getText());
    }

    public function provideExecutedScript()
    {
        return array(
            array('document.querySelector("h1").textContent = "Hello world"'),
            array('document.querySelector("h1").textContent = "Hello world";'),
            array('function () {document.querySelector("h1").textContent = "Hello world";}()'),
            array('function () {document.querySelector("h1").textContent = "Hello world";}();'),
            array('(function () {document.querySelector("h1").textContent = "Hello world";})()'),
            array('(function () {document.querySelector("h1").textContent = "Hello world";})();'),
        );
    }

    /**
     * @dataProvider provideEvaluatedScript
     */
    public function testEvaluateJavascript($script)
    {
        $this->getSession()->visit($this->pathTo('/index.html'));

        $this->assertSame(2, $this->getSession()->evaluateScript($script));
    }

    public function provideEvaluatedScript()
    {
        return array(
            array('1 + 1'),
            array('1 + 1;'),
            array('return 1 + 1'),
            array('return 1 + 1;'),
            array('function () {return 1+1;}()'),
            array('(function () {return 1+1;})()'),
            array('return function () { return 1+1;}()'),
            array('return (function () {return 1+1;})()'),
        );
    }
}
