<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\node\NodeInterface;

/**
 * Unpublishes a node.
 *
 * @Action(
 *   id = "node_unpublish_action",
 *   label = @Translation("Unpublish selected content"),
 *   type = "node"
 * )
 */
class UnpublishNode extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['status' => NodeInterface::NOT_PUBLISHED];
  }

}
