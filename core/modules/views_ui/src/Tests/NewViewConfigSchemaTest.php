<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\NewViewConfigSchemaTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration schema against new views.
 *
 * @group views_ui
 */
class NewViewConfigSchemaTest extends WebTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'node', 'comment', 'file', 'taxonomy', 'dblog', 'aggregator');

  /**
   * Tests creating brand new views.
   */
  public function testNewViews() {
    $this->drupalLogin($this->drupalCreateUser(array('administer views')));

    // Create views with all core Views wizards.
    $wizards = array(
      // Wizard with their own classes.
      'node',
      'node_revision',
      'users',
      'comment',
      'file_managed',
      'taxonomy_term',
      'watchdog',
      // Standard derivative classes.
      'standard:aggregator_feed',
      'standard:aggregator_item',
    );
    foreach($wizards as $wizard_key) {
      $edit = array();
      $edit['label'] = $this->randomString();
      $edit['id'] = strtolower($this->randomMachineName());
      $edit['show[wizard_key]'] = $wizard_key;
      $edit['description'] = $this->randomString();
      $this->drupalPostForm('admin/structure/views/add', $edit, t('Save and edit'));
    }
  }

}
