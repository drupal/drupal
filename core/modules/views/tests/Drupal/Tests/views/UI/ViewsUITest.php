<?php

/**
 * @file
 * Contains \Drupal\Tests\UI\ViewsUITest.
 */

namespace Drupal\views\Tests\views\UI;

use Drupal\Tests\UnitTestCase;
use Drupal\views_ui\Form\Ajax\RearrangeFilter;

/**
 * Tests views_ui functions and methods.
 *
 * @group Views UI
 */
class ViewsUITest extends UnitTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'views_ui');

  public static function getInfo() {
    return array(
      'name' => 'Module tests',
      'description' => 'Unit tests for Views UI module functions.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests static methods.
   */
  public function testStaticMethods() {
    // Test the RearrangeFilter::arrayKeyPlus method.
    $original = array(0 => 'one', 1 => 'two', 2 => 'three');
    $expected = array(1 => 'one', 2 => 'two', 3 => 'three');
    $this->assertSame(RearrangeFilter::arrayKeyPlus($original), $expected);
  }

}
