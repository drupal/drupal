<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests configuration schema against new views.
 */
#[Group('views_ui')]
#[RunTestsInSeparateProcesses]
class NewViewConfigSchemaTest extends UITestBase {

  /**
   * {@inheritdoc}
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
  public function testNewViews(): void {
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
