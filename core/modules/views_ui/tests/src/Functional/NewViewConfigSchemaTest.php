<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests configuration schema against new views.
 *
 * @group views_ui
 */
class NewViewConfigSchemaTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views_ui',
    'node',
    'comment',
    'file',
    'taxonomy',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests creating brand new views.
   */
  public function testNewViews() {
    $this->drupalLogin($this->drupalCreateUser(['administer views']));

    // Create views with all core Views wizards.
    $wizards = [
      // Wizard with their own classes.
      'node',
      'node_revision',
      'users',
      'comment',
      'file_managed',
      'taxonomy_term',
      'watchdog',
    ];
    foreach ($wizards as $wizard_key) {
      $edit = [];
      $edit['label'] = $this->randomString();
      $edit['id'] = $this->randomMachineName();
      $edit['show[wizard_key]'] = $wizard_key;
      $edit['description'] = $this->randomString();
      $this->drupalGet('admin/structure/views/add');
      $this->submitForm($edit, 'Save and edit');
    }
  }

}
