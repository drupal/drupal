<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\PublishAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Publishes a node.
 *
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\PublishAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\PublishAction
 * @see https://www.drupal.org/node/2919303
 *
 * @Action(
 *   id = "node_publish_action",
 *   label = @Translation("Publish selected content"),
 *   type = "node"
 * )
 */
class PublishNode extends PublishAction {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    @trigger_error(__NAMESPACE__ . '\PublishNode is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\PublishAction instead. See https://www.drupal.org/node/2919303.', E_USER_DEPRECATED);
  }

}
