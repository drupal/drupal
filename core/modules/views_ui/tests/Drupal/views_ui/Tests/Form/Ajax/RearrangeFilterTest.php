<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\Form\Ajax\RearrangeFilterTest.
 */

namespace Drupal\views_ui\Tests\Form\Ajax;

use Drupal\Tests\UnitTestCase;
use Drupal\views_ui\Form\Ajax\RearrangeFilter;

/**
 * Tests RearrangeFilter's methods.
 *
 * @group Views UI
 */
class RearrangeFilterTest extends UnitTestCase {

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
