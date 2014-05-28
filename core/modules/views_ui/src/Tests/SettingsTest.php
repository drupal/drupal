<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\SettingsTest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the various settings in the views UI.
 */
class SettingsTest extends UITestBase {

  /**
   * Stores an admin user used by the different tests.
   *
   * @var \Drupal\user\User
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Settings functionality',
      'description' => 'Tests all ui related settings under admin/structure/views/settings.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the settings for the edit ui.
   */
  function testEditUI() {
    $this->drupalLogin($this->adminUser);

    // Test the settings tab exists.
    $this->drupalGet('admin/structure/views');
    $this->assertLinkByHref('admin/structure/views/settings');

    // Test the confirmation message.
    $this->drupalPostForm('admin/structure/views/settings', array(), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // Configure to always show the master display.
    $edit = array(
      'ui_show_master_display' => TRUE,
    );
    $this->drupalPostForm('admin/structure/views/settings', $edit, t('Save configuration'));

    $view = array();
    $view['label'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['description'] = $this->randomName(16);
    $view['page[create]'] = TRUE;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    // Configure to not always show the master display.
    // If you have a view without a page or block the master display should be
    // still shown.
    $edit = array(
      'ui_show_master_display' => FALSE,
    );
    $this->drupalPostForm('admin/structure/views/settings', $edit, t('Save configuration'));

    $view['page[create]'] = FALSE;
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    // Create a view with an additional display, so master should be hidden.
    $view['page[create]'] = TRUE;
    $view['id'] = strtolower($this->randomName());
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $this->assertNoLink(t('Master'));

    // Configure to always show the advanced settings.
    // @todo It doesn't seem to be a way to test this as this works just on js.

    // Configure to show the embedable display.
    $edit = array(
      'ui_show_display_embed' => TRUE,
    );
    $this->drupalPostForm('admin/structure/views/settings', $edit, t('Save configuration'));

    $view['id'] = strtolower($this->randomName());
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertFieldById('edit-displays-top-add-display-embed');

    $edit = array(
      'ui_show_display_embed' => FALSE,
    );
    $this->drupalPostForm('admin/structure/views/settings', $edit, t('Save configuration'));

    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertNoFieldById('edit-displays-top-add-display-embed');

    // Configure to hide/show the sql at the preview.
    $edit = array(
      'ui_show_sql_query_enabled' => FALSE,
    );
    $this->drupalPostForm('admin/structure/views/settings', $edit, t('Save configuration'));

    $view['id'] = strtolower($this->randomName());
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $this->drupalPostForm(NULL, array(), t('Update preview'));
    $xpath = $this->xpath('//div[@class="views-query-info"]/pre');
    $this->assertEqual(count($xpath), 0, 'The views sql is hidden.');

    $edit = array(
      'ui_show_sql_query_enabled' => TRUE,
    );
    $this->drupalPostForm('admin/structure/views/settings', $edit, t('Save configuration'));

    $view['id'] = strtolower($this->randomName());
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $this->drupalPostForm(NULL, array(), t('Update preview'));
    $xpath = $this->xpath('//div[@class="views-query-info"]//pre');
    $this->assertEqual(count($xpath), 1, 'The views sql is shown.');
    $this->assertFalse(strpos($xpath[0], 'db_condition_placeholder') !== FALSE, 'No placeholders are shown in the views sql.');
    $this->assertTrue(strpos($xpath[0], "node_field_data.status = '1'") !== FALSE, 'The placeholders in the views sql is replace by the actual value.');

    // Test the advanced settings form.

    // Test the confirmation message.
    $this->drupalPostForm('admin/structure/views/settings/advanced', array(), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $edit = array(
      'skip_cache' => TRUE,
      'sql_signature' => TRUE,
      'no_javascript' => TRUE,
    );
    $this->drupalPostForm('admin/structure/views/settings/advanced', $edit, t('Save configuration'));

    $this->assertFieldChecked('edit-skip-cache', 'The skip_cache option is checked.');
    $this->assertFieldChecked('edit-sql-signature', 'The sql_signature option is checked.');
    $this->assertFieldChecked('edit-no-javascript', 'The no_javascript option is checked.');

    // Test the "Clear Views' cache" button.
    $this->drupalPostForm('admin/structure/views/settings/advanced', array(), t("Clear Views' cache"));
    $this->assertText(t('The cache has been cleared.'));
  }

}
