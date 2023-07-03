<?php

namespace Drupal\jsonapi_test_reference_types\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Entity reference field type which doesn't implement the standard interface.
 *
 * This is to test the handling of deprecated fields which do not implement
 * \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface.
 *
 * @see https://www.drupal.org/node/3279140
 * @see \Drupal\Tests\jsonapi\Kernel\ResourceType\RelatedResourceTypesTest::testGetRelatableResourceTypesFromFieldDefinitionEntityReferenceFieldDeprecated()
 *
 * @todo Remove this in Drupal 11 https://www.drupal.org/project/drupal/issues/3353314.
 *
 * @FieldType(
 *   id = "jsonapi_test_deprecated_reference",
 * )
 */
class DeprecatedReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_id'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Target ID'))
      ->setSetting('unsigned', TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the target entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_id';
  }

}
