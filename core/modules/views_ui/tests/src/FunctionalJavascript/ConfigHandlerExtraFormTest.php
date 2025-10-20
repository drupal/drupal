<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\views_ui\Traits\FilterEntityReferenceTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ConfigHandlerExtra form.
 *
 * @see \Drupal\views_ui\Form\Ajax\ConfigHandlerExtra
 */
#[Group('views_ui')]
#[RunTestsInSeparateProcesses]
final class ConfigHandlerExtraFormTest extends WebDriverTestBase {

  use FilterEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_ui_test',
    'views_test_entity_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer views',
    ]);
    $this->drupalLogin($admin_user);

    $this->setUpEntityTypes();
  }

  /**
   * Tests validation error messages are displayed.
   *
   * Note: This test relies on a form alter hook to make an extra options field
   * required.
   *
   * @see \Drupal\views_ui_test\Hook\ViewsUiTestHooks::formViewsUiConfigItemExtraFormAlter
   */
  public function testExtraOptionsModalValidation(): void {
    // Set the Drupal state key which will trigger the form alter.
    \Drupal::state()->set('views_ui_test.alter_views_ui_config_item_extra_form', TRUE);
    $this->drupalGet('admin/structure/views/view/content');
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Open the dialog.
    $page->clickLink('views-add-filter');

    // Wait for the popup to open and the search field to be available.
    $assert->waitForField('override[controls][options_search]');
    $page->findField('name[node__field_test.field_test_target_id]')
      ->click();
    $page->find('css', 'button.button.button--primary.form-submit.ui-button')
      ->click();

    // Wait for the selection handler to show up.
    $assert->waitForField('options[sub_handler]');
    // Choose the default handler with article type checked.
    $page->selectFieldOption('options[sub_handler]', 'default:node');
    $page->checkField('options[reference_default:node][target_bundles][article]');
    // Leave the required `widget` option unselected and submit to trigger
    // validation.
    $page->find('xpath', "//*[contains(text(), 'Apply and continue')]")
      ->press();
    $assert->assertWaitOnAjaxRequest();
    // The `options[widget]` field should still be visible.
    $assert->fieldExists('options[widget]');
    // Assert error message is displayed.
    $assert->elementTextEquals('css', '.views-messages', 'Error message Selection type field is required.');
  }

}
