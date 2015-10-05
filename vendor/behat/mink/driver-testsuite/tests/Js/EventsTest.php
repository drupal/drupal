<?php

namespace Behat\Mink\Tests\Driver\Js;

use Behat\Mink\Tests\Driver\TestCase;

class EventsTest extends TestCase
{
    /**
     * @group mouse-events
     */
    public function testClick()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $clicker = $this->getAssertSession()->elementExists('css', '.elements div#clicker');
        $this->assertEquals('not clicked', $clicker->getText());

        $clicker->click();
        $this->assertEquals('single clicked', $clicker->getText());
    }

    /**
     * @group mouse-events
     */
    public function testDoubleClick()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $clicker = $this->getAssertSession()->elementExists('css', '.elements div#clicker');
        $this->assertEquals('not clicked', $clicker->getText());

        $clicker->doubleClick();
        $this->assertEquals('double clicked', $clicker->getText());
    }

    /**
     * @group mouse-events
     */
    public function testRightClick()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $clicker = $this->getAssertSession()->elementExists('css', '.elements div#clicker');
        $this->assertEquals('not clicked', $clicker->getText());

        $clicker->rightClick();
        $this->assertEquals('right clicked', $clicker->getText());
    }

    /**
     * @group mouse-events
     */
    public function testFocus()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $focusBlurDetector = $this->getAssertSession()->elementExists('css', '.elements input#focus-blur-detector');
        $this->assertEquals('no action detected', $focusBlurDetector->getValue());

        $focusBlurDetector->focus();
        $this->assertEquals('focused', $focusBlurDetector->getValue());
    }

    /**
     * @group mouse-events
     * @depends testFocus
     */
    public function testBlur()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $focusBlurDetector = $this->getAssertSession()->elementExists('css', '.elements input#focus-blur-detector');
        $this->assertEquals('no action detected', $focusBlurDetector->getValue());

        $focusBlurDetector->blur();
        $this->assertEquals('blured', $focusBlurDetector->getValue());
    }

    /**
     * @group mouse-events
     */
    public function testMouseOver()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $mouseOverDetector = $this->getAssertSession()->elementExists('css', '.elements div#mouseover-detector');
        $this->assertEquals('no mouse action detected', $mouseOverDetector->getText());

        $mouseOverDetector->mouseOver();
        $this->assertEquals('mouse overed', $mouseOverDetector->getText());
    }

    /**
     * @dataProvider provideKeyboardEventsModifiers
     */
    public function testKeyboardEvents($modifier, $eventProperties)
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $webAssert = $this->getAssertSession();

        $input1 = $webAssert->elementExists('css', '.elements input.input.first');
        $input2 = $webAssert->elementExists('css', '.elements input.input.second');
        $input3 = $webAssert->elementExists('css', '.elements input.input.third');
        $event = $webAssert->elementExists('css', '.elements .text-event');

        $input1->keyDown('u', $modifier);
        $this->assertEquals('key downed:'.$eventProperties, $event->getText());

        $input2->keyPress('r', $modifier);
        $this->assertEquals('key pressed:114 / '.$eventProperties, $event->getText());

        $input3->keyUp(78, $modifier);
        $this->assertEquals('key upped:78 / '.$eventProperties, $event->getText());
    }

    public function provideKeyboardEventsModifiers()
    {
        return array(
            'none' => array(null, '0 / 0 / 0 / 0'),
            'alt' => array('alt', '1 / 0 / 0 / 0'),
             // jQuery considers ctrl as being a metaKey in the normalized event
            'ctrl' => array('ctrl', '0 / 1 / 0 / 1'),
            'shift' => array('shift', '0 / 0 / 1 / 0'),
            'meta' => array('meta', '0 / 0 / 0 / 1'),
        );
    }
}
