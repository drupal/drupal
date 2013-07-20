<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewsUITourTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests tour functionality.
 */
class ViewsUITourTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tour');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Views UI tour tests',
      'description' => 'Tests the Views UI tour.',
      'group' => 'Tour',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(array('access tour', 'administer views')));
  }

  /**
   * Tests the Views UI tour.
   */
  public function testTourFunctionality() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $elements = $this->xpath('//ol[@id="tour"]');
    $this->assertEqual(count($elements), 1, 'Found a tour on the test view.');
  }

}
