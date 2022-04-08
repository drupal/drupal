<?php

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JS functionality in the block add form.
 *
 * @group block
 */
class BlockAddTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the AJAX for the theme selector.
   */
  public function testBlockAddThemeSelector() {
    \Drupal::service('theme_installer')->install(['seven']);

    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
    ]));

    $this->drupalGet('admin/structure/block/add/system_powered_by_block');
    $assert_session = $this->assertSession();
    // Pick a theme with a region that does not exist in another theme.
    $assert_session->selectExists('Theme')->selectOption('seven');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->selectExists('Region')->selectOption('pre_content');
    $assert_session->assertWaitOnAjaxRequest();
    // Switch to a theme that doesn't contain the region selected above.
    $assert_session->selectExists('Theme')->selectOption('stark');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('An illegal choice has been detected. Please contact the site administrator.');
    $assert_session->optionExists('Region', '- Select -');
  }

}
