<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\field\NodeBulkForm.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\Core\Cache\Cache;

/**
 * Defines a node operations bulk form element.
 *
 * @PluginID("node_bulk_form")
 */
class NodeBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(&$form, &$form_state) {
    parent::viewsFormSubmit($form, $form_state);

    if ($form_state['step'] == 'views_form_views_form') {
      Cache::invalidateTags(array('content' => TRUE));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return t('No content selected.');
  }

}
