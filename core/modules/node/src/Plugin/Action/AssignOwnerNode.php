<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Database\Connection;
use Drupal\action\Plugin\Action\AssignOwnerNode as ActionAssignOwnerNode;

/**
 * Assigns ownership of a node to a user.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 *   \Drupal\action\Plugin\Action\AssignOwnerNode instead.
 *
 * @see https://www.drupal.org/node/3424506
 */
class AssignOwnerNode extends ActionAssignOwnerNode {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\action\Plugin\Action\AssignOwnerNode instead. See https://www.drupal.org/node/3424506', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);
  }

}
