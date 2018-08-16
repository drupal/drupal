<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the views analyze system.
 *
 * @group views_ui
 */
class AnalyzeTest extends UITestBase {

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

  /**
   * Tests that analyze works in general.
   */
  public function testAnalyzeBasic() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertLink(t('Analyze view'));

    // This redirects the user to the analyze form.
    $this->clickLink(t('Analyze view'));
    $this->assertSession()->titleEquals('View analysis | Drupal');

    foreach (['ok', 'warning', 'error'] as $type) {
      $xpath = $this->xpath('//div[contains(@class, :class)]', [':class' => $type]);
      $this->assertTrue(count($xpath), format_string('Analyse messages with @type found', ['@type' => $type]));
    }

    // This redirects the user back to the main views edit page.
    $this->drupalPostForm(NULL, [], t('Ok'));
  }

}
