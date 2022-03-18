<?php

namespace Drupal\Tests\config_translation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Views;

// cspell:ignore später

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
  protected static $modules = ['config_translation', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $permissions = [
      'administer views',
      'translate configuration',
      'access content overview',
      'administer languages',
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
    $this->assertSession()->linkByHrefExists($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertSession()->responseContains('<th>Language</th>');

    // Test that the 'Edit' tab appears.
    $this->assertSession()->linkByHrefExists('admin/structure/views/view/test_view');
  }

  /**
   * Test to ensure that TimestampFormatter translation works.
   */
  public function testTimestampFormatterTranslation() {
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->drupalCreateContentType(['type' => 'article']);
    $node = $this->drupalCreateNode(['type' => 'article', 'title' => $this->randomMachineName()]);

    // Update the view to set the field formatter.
    $view = Views::getView('content');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['changed']['type'] = 'timestamp_ago';
    $display['display_options']['fields']['changed']['settings'] = [
      'future_format' => '@interval hence',
      'past_format' => '@interval ago',
      'granularity' => 1,
    ];
    $view->save();

    // Add a translation to the views configuration for the past and future
    // formats.
    $this->drupalGet('admin/structure/views/view/content/translate/de/edit');
    $edit = [
      'translation[config_names][views.view.content][display][default][display_options][fields][changed][settings][future_format]' => '@interval später',
      'translation[config_names][views.view.content][display][default][display_options][fields][changed][settings][past_format]' => 'vor @interval',
    ];
    $this->submitForm($edit, 'Save translation');

    // Create a timestamp just over an hour in the past and set the nodes update
    // time to this.
    $past_timestamp = \Drupal::time()->getCurrentTime() - 3700;
    $node->setChangedTime($past_timestamp);
    $node->save();

    $this->drupalGet('/de/admin/content');
    // Not all normal string translations are available, so 'hour' is still in
    // English.
    $this->assertSession()->pageTextContains('vor 1 hour');

    // Create a timestamp just over an hour in the future and set the nodes
    // update time to this.
    $past_timestamp = \Drupal::time()->getCurrentTime() + 3700;
    $node->setChangedTime($past_timestamp);
    $node->save();

    $this->drupalGet('/de/admin/content');
    // Not all normal string translations are available, so 'hour' is still in
    // English.
    $this->assertSession()->pageTextContains('1 hour später');
  }

}
