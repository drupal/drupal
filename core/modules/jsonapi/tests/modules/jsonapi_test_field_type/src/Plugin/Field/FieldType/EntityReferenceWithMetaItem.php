<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_field_type\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'entity_reference_with_meta' field type.
 *
 * An entity reference field type with an additional data property, used to
 * expose the conditions required to test JSON:API relationship PATCH handling.
 *
 * @see https://www.drupal.org/project/drupal/issues/XXXXXXX
 */
#[FieldType(
  id: 'jsonapi_test_field_type_entity_reference_with_meta',
  label: new TranslatableMarkup('Entity reference with meta'),
  description: new TranslatableMarkup('Test field type: entity reference with an additional data property.'),
  category: 'reference',
  default_widget: 'entity_reference_autocomplete',
  default_formatter: 'entity_reference_label',
  list_class: EntityReferenceFieldItemList::class,
  serialized_property_names: ['data'],
)]
class EntityReferenceWithMetaItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['data'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Data'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['data'] = [
      'type' => 'blob',
      'size' => 'big',
    ];

    return $schema;
  }

}
