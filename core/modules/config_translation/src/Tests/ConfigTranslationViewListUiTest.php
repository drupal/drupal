<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigTranslationViewListUiTest.
 */

namespace Drupal\config_translation\Tests;

use Drupal\views_ui\Tests\UITestBase;

/**
 * Visit view list and test if translate is available.
 *
 * @group config_translation
 */
class ConfigTranslationViewListUiTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('node', 'test_view');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_translation', 'views_ui');

  protected function setUp() {
    parent::setUp();

    $permissions = array(
      'administer views',
      'translate configuration',
    );

    // Create and log in user.
    $this->drupalLogin($this->drupalCreateUser($permissions));
  }

  /**
   * Tests views_ui list to see if translate link is added to operations.
   */
  public function testTranslateOperationInViewListUi() {
    // Views UI List 'admin/structure/views'.
    $this->drupalGet('admin/structure/views');
    $translate_link = 'admin/structure/views/view/test_view/translate';
    // Test if the link to translate the test_view is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');

    // Test that the 'Edit' tab appears.
    $this->assertLinkByHref('admin/structure/views/view/test_view');
  }

}
