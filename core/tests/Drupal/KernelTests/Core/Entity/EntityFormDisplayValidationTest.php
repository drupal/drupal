<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests validation of entity_form_display entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityFormDisplayValidationTest extends EntityFormModeValidationTest {

  /**
   * {@inheritdoc}
   */
  protected bool $hasLabel = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = EntityFormDisplay::create([
      'targetEntityType' => 'user',
      'bundle' => 'user',
      // The mode was created by the parent class.
      'mode' => 'test',
    ]);
    $this->entity->save();
  }

  /**
   * Tests validation of entity form display component's widget settings.
   */
  public function testMultilineTextFieldWidgetPlaceholder(): void {
    // First, create a field for which widget settings exist.
    $this->enableModules(['field', 'text']);
    $text_field_storage_config = FieldStorageConfig::create([
      'type' => 'text_with_summary',
      'field_name' => 'novel',
      'entity_type' => 'user',
    ]);
    $text_field_storage_config->save();

    $text_field_config = FieldConfig::create([
      'field_storage' => $text_field_storage_config,
      'bundle' => 'user',
      'dependencies' => [
        'config' => [
          $text_field_storage_config->getConfigDependencyName(),
        ],
      ],
    ]);
    $text_field_config->save();

    // Then, configure a form display widget for this field.
    assert($this->entity instanceof EntityFormDisplayInterface);
    $this->entity->setComponent('novel', [
      'type' => 'text_textarea_with_summary',
      'region' => 'content',
      'settings' => [
        'rows' => 9,
        'summary_rows' => 3,
        'placeholder' => "Multi\nLine",
        'show_summary' => FALSE,
      ],
      'third_party_settings' => [],
    ]);

    $this->assertValidationErrors([]);
  }

}
