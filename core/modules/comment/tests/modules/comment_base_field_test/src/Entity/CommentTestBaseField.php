<?php

declare(strict_types=1);

namespace Drupal\comment_base_field_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines a test entity class for comment as a base field.
 */
#[ContentEntityType(
  id: 'comment_test_base_field',
  label: new TranslatableMarkup('Test comment - base field'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
  ],
  base_table: 'comment_test_base_field'
)]
class CommentTestBaseField extends EntityTest {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['test_comment'] = BaseFieldDefinition::create('comment')
      ->setLabel(t('A comment field'))
      ->setSetting('comment_type', 'test_comment_type')
      ->setDefaultValue([
        'status' => CommentItemInterface::OPEN,
      ]);

    return $fields;
  }

}
