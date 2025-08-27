<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\history\Plugin\views\field\NodeNewComments as HistoryNodeNewComments;

/**
 * Field handler to display the number of new comments.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use
 *   \Drupal\history\Plugin\views\field\NodeNewComments instead.
 *
 * @see https://www.drupal.org/node/3542850
 */
class NodeNewComments extends HistoryNodeNewComments {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use \Drupal\history\Plugin\views\field\NodeNewComments instead. See https://www.drupal.org/node/3542850', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $database, $entity_type_manager, $entity_field_manager);
  }

}
