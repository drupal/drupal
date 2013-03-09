<?php

/**
 * @file
 * Definition of Drupal\views\Tests\AnalyzeTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests the views analyze system.
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

  public static function getInfo() {
    return array(
      'name' => 'Views Analyze',
      'description' => 'Tests the views analyze system.',
      'group' => 'Views',
    );
  }

  public function setUp() {
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
    $this->assertLink(t('analyze view'));

    // This redirects the user to the analyze form.
    $this->clickLink(t('analyze view'));
    $this->assertText(t('View analysis'));

    foreach (array('ok', 'warning', 'error') as $type) {
      $xpath = $this->xpath('//div[contains(@class, :class)]', array(':class' => $type));
      $this->assertTrue(count($xpath), format_string('Analyse messages with @type found', array('@type' => $type)));
    }

    // This redirects the user back to the main views edit page.
    $this->drupalPost(NULL, array(), t('Ok'));
  }

}
