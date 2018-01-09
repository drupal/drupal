<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\UnpublishAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Unpublishes a node.
 *
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\UnpublishAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\UnpublishAction
 * @see https://www.drupal.org/node/2919303
 *
 * @Action(
 *   id = "node_unpublish_action",
 *   label = @Translation("Unpublish selected content"),
 *   type = "node"
 * )
 */
class UnpublishNode extends UnpublishAction {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    @trigger_error(__NAMESPACE__ . '\UnpublishNode is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\UnpublishAction instead. See https://www.drupal.org/node/2919303.', E_USER_DEPRECATED);
  }

}
