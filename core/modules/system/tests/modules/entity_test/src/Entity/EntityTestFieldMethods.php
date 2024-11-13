<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
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
  id: 'entity_test_field_methods',
  label: new TranslatableMarkup('Test entity - field methods and data table'),
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
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_field_methods',
  data_table: 'entity_test_field_methods_property',
  translatable: TRUE,
)]
class EntityTestFieldMethods extends EntityTestMul {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['test_invocation_order'] = BaseFieldDefinition::create('auto_incrementing_test')
      ->setLabel(t('Test field method invocation order.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
