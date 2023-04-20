<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for configuring entity displays.
 *
 * @group field_ui
 */
class EntityDisplayFormBaseTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui', 'entity_test', 'field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    foreach (entity_test_entity_types() as $entity_type) {
      // Auto-create fields for testing.
      FieldStorageConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_no_plugin',
        'type' => 'field_test',
        'cardinality' => 1,
      ])->save();
      FieldConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_no_plugin',
        'bundle' => $entity_type,
        'label' => 'Test field with no plugin',
        'translatable' => FALSE,
      ])->save();

      \Drupal::service('entity_display.repository')
        ->getFormDisplay($entity_type, $entity_type)
        ->setComponent('field_test_no_plugin', [])
        ->save();
    }

    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test form display',
    ]));
  }

  /**
   * Ensures the entity is not affected when there are no applicable formatters.
   */
  public function testNoApplicableFormatters(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
    $id = 'entity_test.entity_test.default';

    $entity_before = $storage->load($id);
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $entity_after = $storage->load($id);

    $this->assertSame($entity_before->toArray(), $entity_after->toArray());
  }

}
