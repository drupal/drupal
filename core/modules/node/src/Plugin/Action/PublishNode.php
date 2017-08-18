<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\node\NodeInterface;

/**
 * Publishes a node.
 *
 * @Action(
 *   id = "node_publish_action",
 *   label = @Translation("Publish selected content"),
 *   type = "node"
 * )
 */
class PublishNode extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['status' => NodeInterface::PUBLISHED];
  }

}
