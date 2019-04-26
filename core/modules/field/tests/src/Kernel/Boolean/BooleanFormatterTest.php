<?php

namespace Drupal\Tests\field\Kernel\Boolean;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the boolean formatter.
 *
 * @group field
 */
class BooleanFormatterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'text', 'entity_test', 'user', 'system'];

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'boolean',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($this->entityType, $this->bundle)
      ->setComponent($this->fieldName, [
        'type' => 'boolean',
        'settings' => [],
      ]);
    $this->display->save();
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

  /**
   * Tests boolean formatter output.
   */
  public function testBooleanFormatter() {
    $data = [];
    $data[] = [0, [], 'Off'];
    $data[] = [1, [], 'On'];

    $format = ['format' => 'enabled-disabled'];
    $data[] = [0, $format, 'Disabled'];
    $data[] = [1, $format, 'Enabled'];

    $format = ['format' => 'unicode-yes-no'];
    $data[] = [1, $format, '✔'];
    $data[] = [0, $format, '✖'];

    $format = [
      'format' => 'custom',
      'format_custom_false' => 'FALSE',
      'format_custom_true' => 'TRUE',
    ];
    $data[] = [0, $format, 'FALSE'];
    $data[] = [1, $format, 'TRUE'];

    foreach ($data as $test_data) {
      list($value, $settings, $expected) = $test_data;

      $component = $this->display->getComponent($this->fieldName);
      $component['settings'] = $settings;
      $this->display->setComponent($this->fieldName, $component);

      $entity = EntityTest::create([]);
      $entity->{$this->fieldName}->value = $value;

      // Verify that all HTML is escaped and newlines are retained.
      $this->renderEntityFields($entity, $this->display);
      $this->assertRaw($expected);
    }
  }

}
