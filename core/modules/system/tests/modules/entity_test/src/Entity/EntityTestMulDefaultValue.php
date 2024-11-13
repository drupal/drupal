<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_mul_default_value',
  label: new TranslatableMarkup('Test entity - multiple default value and data table'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
      'delete' => EntityTestDeleteForm::class,
    ],
    'views_data' => EntityViewsData::class,
  ],
  links: [
    'canonical' => '/entity_test_mul_default_value/manage/{entity_test_mul_default_value}',
    'edit-form' => '/entity_test_mul_default_value/manage/{entity_test_mul_default_value}',
    'delete-form' => '/entity_test/delete/entity_test_mul_default_value/{entity_test_mul_default_value}',
  ],
  base_table: 'entity_test_mul_default_value',
  data_table: 'entity_test_mul_default_value_property_data',
  translatable: TRUE,
  field_ui_base_route: 'entity.entity_test_mul.admin_form',
)]
class EntityTestMulDefaultValue extends EntityTestMul {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('shape')
      ->setLabel(t('Some custom description'))
      ->setTranslatable(TRUE)
      ->setDefaultValueCallback('entity_test_field_default_value');

    return $fields;
  }

}
