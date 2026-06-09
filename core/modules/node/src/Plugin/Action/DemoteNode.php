<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Demotes a node.
 */
#[Action(
  id: 'node_unpromote_action',
  label: new TranslatableMarkup('Unpromote selected content'),
  type: 'node'
)]
class DemoteNode extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['promote' => NodeInterface::NOT_PROMOTED];
  }

}
