<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\field\NodeBulkForm.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a node operations bulk form element.
 *
 * @ViewsField("node_bulk_form")
 */
class NodeBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return t('No content selected.');
  }

}
