<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group field
 */
class ConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'user'];

  /**
   * The configuration manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_with_bundle');
    EntityTestBundle::create([
      'id' => 'test',
      'label' => $this->randomString(),
    ])->save();

    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   * Tests the application of configuration actions on field settings.
   */
  public function testConfigActions(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test',
      'type' => 'boolean',
      'entity_type' => 'entity_test_with_bundle',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test',
    ]);
    $field->save();

    $this->assertTrue($field->isTranslatable());
    $this->assertFalse($field->isRequired());
    $this->assertSame('On', (string) $field->getSetting('on_label'));
    $this->assertSame('Off', (string) $field->getSetting('off_label'));
    $this->assertEmpty($field->getDefaultValueLiteral());

    $this->configActionManager->applyAction(
      'entity_method:field.field:setLabel',
      $field->getConfigDependencyName(),
      'Not what you were expecting!',
    );
    $this->configActionManager->applyAction(
      'entity_method:field.field:setDescription',
      $field->getConfigDependencyName(),
      "Any ol' nonsense can go here.",
    );
    $this->configActionManager->applyAction(
      'entity_method:field.field:setTranslatable',
      $field->getConfigDependencyName(),
      FALSE,
    );
    $this->configActionManager->applyAction(
      'entity_method:field.field:setRequired',
      $field->getConfigDependencyName(),
      TRUE,
    );
    $this->configActionManager->applyAction(
      'entity_method:field.field:setSettings',
      $field->getConfigDependencyName(),
      [
        'on_label' => 'Zap!',
        'off_label' => 'Zing!',
      ],
    );
    $this->configActionManager->applyAction(
      'entity_method:field.field:setDefaultValue',
      $field->getConfigDependencyName(),
      [
        'value' => FALSE,
      ],
    );

    $field = FieldConfig::load($field->id());
    $this->assertNotEmpty($field);
    $this->assertSame('Not what you were expecting!', $field->getLabel());
    $this->assertSame("Any ol' nonsense can go here.", $field->getDescription());
    $this->assertFalse($field->isTranslatable());
    $this->assertTrue($field->isRequired());
    $this->assertSame('Zap!', $field->getSetting('on_label'));
    $this->assertSame('Zing!', $field->getSetting('off_label'));
    $this->assertSame([['value' => 0]], $field->getDefaultValueLiteral());
  }

}
