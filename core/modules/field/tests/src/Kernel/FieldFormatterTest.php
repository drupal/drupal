<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for field formatters.
 *
 * @group field
 */
class FieldFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'text',
    'entity_test',
    'field_test',
    'system',
    'filter',
    'user',
  ];

  /**
   * The field's name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The default display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Configure the theme system.
    $this->installConfig(['system', 'field']);
    $this->installEntitySchema('entity_test_rev');

    $entity_type = 'entity_test_rev';
    $bundle = $entity_type;
    $this->fieldName = mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $entity_type,
      'type' => 'string',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->display = EntityViewDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldName, [
      'type' => 'string',
      'settings' => [],
    ]);
    $this->display->save();
  }

  /**
   * Tests availability of third party settings in field render arrays.
   */
  public function testThirdPartySettings() {
    $third_party_settings = [
      'field_test' => [
        'foo' => 'bar',
      ],
    ];
    $component = $this->display->getComponent($this->fieldName);
    $component['third_party_settings'] = $third_party_settings;
    $this->display->setComponent($this->fieldName, $component)->save();
    $entity = EntityTestRev::create([]);

    $entity->{$this->fieldName}->value = $this->randomString();
    $build = $entity->{$this->fieldName}->view('default');
    $this->assertEquals($third_party_settings, $build['#third_party_settings']);
  }

}
