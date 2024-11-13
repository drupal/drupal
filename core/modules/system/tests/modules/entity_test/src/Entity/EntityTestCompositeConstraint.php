<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_test\EntityTestForm;

/**
 * Defines a test class for testing composite constraints.
 */
#[ContentEntityType(
  id: 'entity_test_composite_constraint',
  label: new TranslatableMarkup('Test entity constraints with composite constraint'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
  ],
  handlers: [
    'form' => [
      'default' => EntityTestForm::class,
    ],
  ],
  base_table: 'entity_test_composite_constraint',
  constraints: [
    'EntityTestComposite' => [],
    'EntityTestEntityLevel' => [],
  ],
)]
class EntityTestCompositeConstraint extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name']->setDisplayOptions('form', [
      'type' => 'string',
      'weight' => 0,
    ]);

    $fields['type']->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 0,
    ]);

    return $fields;
  }

}
