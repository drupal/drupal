<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Promotes a node.
 */
#[Action(
  id: 'node_promote_action',
  label: new TranslatableMarkup('Promote selected content to front page'),
  type: 'node'
)]
class PromoteNode extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['promote' => NodeInterface::PROMOTED];
  }

}
