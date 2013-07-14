<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Common\RenderWrapperTest.
 */

namespace Drupal\Tests\Core\Common;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Template\RenderWrapper;

/**
 * Tests the \Drupal\Core\Template\RenderWrapper functionality.
 */
class RenderWrapperTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Render wrapper',
      'description' => 'Tests the RenderWrapper class used for late rendering.',
      'group' => 'Common',
    );
  }

  /**
   * Provides data for the RenderWrapper test.
   *
   * @return array
   */
  public function providerTestRenderWrapperData() {
    return array(
      array('ucwords', array('Amazingly few discotheques provide jukeboxes.'), 'Amazingly Few Discotheques Provide Jukeboxes.', 'Simple string manipulation callback.'),
      array('phpversion', array(), phpversion(), 'Callback with no arguments.'),
      array(array('Drupal\Component\Utility\String', 'checkPlain'), array('<script>'), '&lt;script&gt;', 'Namespaced callback.'),
    );
  }

  /**
   * Tests casting a RenderWrapper object to a string.
   *
   * @see \Drupal\Core\Template\RenderWrapper::__toString()
   *
   * @dataProvider providerTestRenderWrapperData
   */
  public function testDrupalRenderWrapper($callback, $arguments, $expected, $message) {
    $this->assertSame($expected, (string) new RenderWrapper($callback, $arguments), $message);
  }

  /**
   * Tests that an invalid callback throws an exception.
   *
   * @expectedException InvalidArgumentException
   */
  public function testInvalidCallback() {
    new RenderWrapper(FALSE);
  }

}
