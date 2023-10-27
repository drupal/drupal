<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Makes a node not sticky.
 */
#[Action(
  id: 'node_make_unsticky_action',
  label: new TranslatableMarkup('Make selected content not sticky'),
  type: 'node'
)]
class UnstickyNode extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['sticky' => NodeInterface::NOT_STICKY];
  }

}
