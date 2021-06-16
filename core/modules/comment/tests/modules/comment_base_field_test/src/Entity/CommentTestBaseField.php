<?php

namespace Drupal\comment_base_field_test\Entity;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines a test entity class for comment as a base field.
 *
 * @ContentEntityType(
 *   id = "comment_test_base_field",
 *   label = @Translation("Test comment - base field"),
 *   base_table = "comment_test_base_field",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 * )
 */
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
