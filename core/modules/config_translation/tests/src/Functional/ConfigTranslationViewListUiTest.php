<?php

namespace Drupal\Tests\config_translation\Functional;

use Drupal\Tests\views_ui\Functional\UITestBase;

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
  public static $testViews = ['node', 'test_view'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['config_translation', 'views_ui'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $permissions = [
      'administer views',
      'translate configuration',
    ];

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
