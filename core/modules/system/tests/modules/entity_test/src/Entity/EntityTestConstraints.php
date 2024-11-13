<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test class for testing the definition of entity level constraints.
 */
#[ContentEntityType(
  id: 'entity_test_constraints',
  label: new TranslatableMarkup('Test entity constraints'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
  ],
  base_table: 'entity_test_constraints',
  constraints: [
    'NotNull' => [],
  ],
)]
class EntityTestConstraints extends EntityTest implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
