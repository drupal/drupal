<?php

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
  public static $modules = ['views_ui'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    // Add an admin user will full rights;
    $this->admin = $this->drupalCreateUser(['administer views']);
  }

  /**
   * Tests that analyze works in general.
   */
  public function testAnalyzeBasic() {
    $this->drupalLogin($this->admin);

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertLink(t('Analyze view'));

    // This redirects the user to the analyze form.
    $this->clickLink(t('Analyze view'));
    $this->assertText(t('View analysis'));

    foreach (['ok', 'warning', 'error'] as $type) {
      $xpath = $this->xpath('//div[contains(@class, :class)]', [':class' => $type]);
      $this->assertTrue(count($xpath), format_string('Analyse messages with @type found', ['@type' => $type]));
    }

    // This redirects the user back to the main views edit page.
    $this->drupalPostForm(NULL, [], t('Ok'));
  }

}
