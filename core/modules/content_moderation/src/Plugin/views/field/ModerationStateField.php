<?php

namespace Drupal\content_moderation\Plugin\views\field;

use Drupal\content_moderation\Plugin\views\ModerationStateJoinViewsHandlerTrait;
use Drupal\views\Plugin\views\field\EntityField;

/**
 * A field handler for the computed moderation_state field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("moderation_state_field")
 */
class ModerationStateField extends EntityField {

  use ModerationStateJoinViewsHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->ensureMyTable();

    // This could be derived from the content_moderation_state entity table
    // mapping, however this is an internal entity type whose storage should
    // remain constant.
    $storage = $this->entityTypeManager->getStorage('content_moderation_state');
    $storage_definition = $this->entityFieldManager->getActiveFieldStorageDefinitions('content_moderation_state')['moderation_state'];
    $column_name = $storage->getTableMapping()->getFieldColumnName($storage_definition, 'value');
    $this->aliases[$column_name] = $this->tableAlias . '.' . $column_name;

    $this->query->addOrderBy(NULL, NULL, $order, $this->aliases[$column_name]);
  }

}
