<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests Field display.
 *
 * @group field
 */
class FieldDisplayTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'field',
    'system',
    'user',
  ];

  /**
   * Test entity type name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Test entity bundle name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Test field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure the theme system.
    $this->installConfig(['system', 'field']);
    $this->installEntitySchema('entity_test_rev');

    $this->entityType = 'entity_test_rev';
    $this->bundle = $this->entityType;
    $this->fieldName = $this->randomMachineName();

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'string',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $values = [
      'targetEntityType' => $this->entityType,
      'bundle' => $this->bundle,
      'mode' => 'default',
      'status' => TRUE,
    ];

    $this->display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->create($values);
    $this->display->save();
  }

  /**
   * Tests that visually hidden works with core.
   */
  public function testFieldVisualHidden() {
    $value = $this->randomMachineName();

    // Set the formatter to link to the entity.
    $this->display->setComponent($this->fieldName, [
      'type' => 'string',
      'label' => 'visually_hidden',
      'settings' => [],
    ])->save();

    $entity = EntityTestRev::create([]);
    $entity->{$this->fieldName}->value = $value;
    $entity->save();

    $build = $this->display->build($entity);
    $renderer = \Drupal::service('renderer');
    $content = $renderer->renderPlain($build);
    $this->setRawContent((string) $content);

    $css_selector_converter = new CssSelectorConverter();
    $elements = $this->xpath($css_selector_converter->toXPath('.visually-hidden'));
    $this->assertCount(1, $elements, $content);
  }

}
