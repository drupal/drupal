<?php

namespace Drupal\Tests\aggregator\Functional\views_ui;

use Drupal\Tests\views_ui\Functional\UITestBase;

/**
 * Tests aggregator configuration schema against new views.
 *
 * @group aggregator
 * @group legacy
 */
class NewViewAggregatorConfigSchemaTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views_ui',
    'aggregator',
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

    // Create views with all standard derivative classes for Views wizards.
    $wizards = [
      'standard:aggregator_feed',
      'standard:aggregator_item',
    ];
    foreach ($wizards as $wizard_key) {
      $edit = [];
      $edit['label'] = $this->randomString();
      $edit['id'] = strtolower($this->randomMachineName());
      $edit['show[wizard_key]'] = $wizard_key;
      $edit['description'] = $this->randomString();
      $this->drupalGet('admin/structure/views/add');
      $this->submitForm($edit, 'Save and edit');
    }
  }

}
