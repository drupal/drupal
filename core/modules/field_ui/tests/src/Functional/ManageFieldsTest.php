<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Manage Display page of a fieldable entity type.
 *
 * @group field_ui
 */
class ManageFieldsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'field_ui_test',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser(['administer node fields']);
    $this->drupalLogin($account);
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_DISPLAY_ALL)
      ->save();
  }

  public function testFieldDropButtonOperations() {
    $node_type = $this->drupalCreateContentType();

    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'string',
        'field_name' => 'highlander',
        'entity_type' => 'node',
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $node_type->id(),
      ])
      ->save();

    $this->drupalGet('/admin/structure/types/manage/' . $node_type->id() . '/fields');

  }

}
