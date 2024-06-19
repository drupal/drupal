<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Database\Database;

/**
 * Tests all ui related settings under admin/structure/views/settings.
 *
 * @group views_ui
 */
class SettingsTest extends UITestBase {

  /**
   * Stores an admin user used by the different tests.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the settings for the edit ui.
   */
  public function testEditUI(): void {
    $this->drupalLogin($this->adminUser);

    // Test the settings tab exists.
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->linkNotExists('admin/structure/views/settings');

    // Test the confirmation message.
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Configure to always show the default display.
    $edit = [
      'ui_show_default_display' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm($edit, 'Save configuration');

    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = TRUE;
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $this->randomMachineName(16);
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    // Configure to not always show the default display.
    // If you have a view without a page or block the default display should be
    // still shown.
    $edit = [
      'ui_show_default_display' => FALSE,
    ];
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm($edit, 'Save configuration');

    $view['page[create]'] = FALSE;
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    // Create a view with an additional display, so default should be hidden.
    $view['page[create]'] = TRUE;
    $view['id'] = $this->randomMachineName();
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    $this->assertSession()->linkNotExists('Default');

    // Configure to always show the advanced settings.
    // @todo It doesn't seem to be a way to test this as this works just on js.

    // Configure to show the embeddable display.
    $edit = [
      'ui_show_display_embed' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm($edit, 'Save configuration');

    $view['id'] = $this->randomMachineName();
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');
    $this->assertSession()->buttonExists('edit-displays-top-add-display-embed');

    $edit = [
      'ui_show_display_embed' => FALSE,
    ];
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');
    $this->assertSession()->buttonNotExists('edit-displays-top-add-display-embed');

    // Configure to hide/show the sql at the preview.
    $edit = [
      'ui_show_sql_query_enabled' => FALSE,
    ];
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm($edit, 'Save configuration');

    $view['id'] = $this->randomMachineName();
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    // Verify that the views sql is hidden.
    $this->submitForm([], 'Update preview');
    $this->assertSession()->elementNotExists('xpath', '//div[@class="views-query-info"]/pre');

    $edit = [
      'ui_show_sql_query_enabled' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/settings');
    $this->submitForm($edit, 'Save configuration');

    $view['id'] = $this->randomMachineName();
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    // Verify that the views sql is shown.
    $this->submitForm([], 'Update preview');
    $this->assertSession()->elementExists('xpath', '//div[@class="views-query-info"]//pre');
    // Verify that no placeholders are shown in the views sql.
    $this->assertSession()->elementTextNotContains('xpath', '//div[@class="views-query-info"]//pre', 'db_condition_placeholder');
    // Verify that the placeholders in the views sql are replaced by the actual
    // values.
    $this->assertSession()->elementTextContains('xpath', '//div[@class="views-query-info"]//pre', Database::getConnection()->escapeField("node_field_data.status") . " = '1'");

    // Test the advanced settings form.

    // Test the confirmation message.
    $this->drupalGet('admin/structure/views/settings/advanced');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $edit = [
      'sql_signature' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/settings/advanced');
    $this->submitForm($edit, 'Save configuration');

    $this->assertSession()->checkboxChecked('edit-sql-signature');

    // Test the "Clear Views' cache" button.
    $this->drupalGet('admin/structure/views/settings/advanced');
    $this->submitForm([], "Clear Views' cache");
    $this->assertSession()->pageTextContains('The cache has been cleared.');
  }

}
