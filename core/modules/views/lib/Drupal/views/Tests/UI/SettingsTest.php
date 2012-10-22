<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\SettingsTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the various settings in the views UI.
 */
class SettingsTest extends UITestBase {

  /**
   * Stores an admin user used by the different tests.
   *
   * @var Drupal\user\User
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

    // Configure to always show the master display.
    $edit = array(
      'ui_show_master_display' => TRUE,
    );
    $this->drupalPost('admin/structure/views/settings', $edit, t('Save configuration'));

    $view = array();
    $view['human_name'] = $this->randomName(16);
    $view['name'] = strtolower($this->randomName(16));
    $view['description'] = $this->randomName(16);
    $view['page[create]'] = TRUE;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));

    $this->assertLink(t('Master') . '*');

    // Configure to not always show the master display.
    // If you have a view without a page or block the master display should be
    // still shown.
    $edit = array(
      'ui_show_master_display' => FALSE,
    );
    $this->drupalPost('admin/structure/views/settings', $edit, t('Save configuration'));

    $view['page[create]'] = FALSE;
    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));

    $this->assertLink(t('Master') . '*');

    // Create a view with an additional display, so master should be hidden.
    $view['page[create]'] = TRUE;
    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));

    $this->assertNoLink(t('Master'));

    // Configure to always show the advanced settings.
    // @todo It doesn't seem to be a way to test this as this works just on js.

    // Configure to show the embedable display.
    $edit = array(
      'ui_show_display_embed' => TRUE,
    );
    $this->drupalPost('admin/structure/views/settings', $edit, t('Save configuration'));
    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));
    $this->assertFieldById('edit-displays-top-add-display-embed');

    $edit = array(
      'ui_show_display_embed' => FALSE,
    );
    $this->drupalPost('admin/structure/views/settings', $edit, t('Save configuration'));
    views_invalidate_cache();
    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));
    $this->assertNoFieldById('edit-displays-top-add-display-embed');
  }

}
