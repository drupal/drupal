<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\AnalyzeTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the views analyze system.
 *
 * @group views_ui
 */
class AnalyzeTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    // Add an admin user will full rights;
    $this->admin = $this->drupalCreateUser(array('administer views'));
  }

  /**
   * Tests that analyze works in general.
   */
  function testAnalyzeBasic() {
    $this->drupalLogin($this->admin);

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertLink(t('Analyze view'));

    // This redirects the user to the analyze form.
    $this->clickLink(t('Analyze view'));
    $this->assertText(t('View analysis'));

    foreach (array('ok', 'warning', 'error') as $type) {
      $xpath = $this->xpath('//div[contains(@class, :class)]', array(':class' => $type));
      $this->assertTrue(count($xpath), format_string('Analyse messages with @type found', array('@type' => $type)));
    }

    // This redirects the user back to the main views edit page.
    $this->drupalPostForm(NULL, array(), t('Ok'));
  }

}
