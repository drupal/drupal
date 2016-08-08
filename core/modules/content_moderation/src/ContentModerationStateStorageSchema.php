<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the content moderation state schema handler.
 */
class ContentModerationStateStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Creates an index to ensure that the lookup in
    // \Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList::getModerationState()
    // is performant.
    $schema['content_moderation_state_field_data']['indexes'] += array(
      'content_moderation_state__lookup' => array('content_entity_type_id', 'content_entity_id', 'content_entity_revision_id'),
    );

    return $schema;
  }

}
