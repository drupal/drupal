<?php

/**
 * @file
 * Contains \Drupal\node\Form\NodeDeleteForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a node.
 */
class NodeDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->logger('content')->notice('@type: deleted %title.', array('@type' => $this->entity->bundle(), '%title' => $this->entity->label()));
    $node_type_storage = $this->entityManager->getStorage('node_type');
    $node_type = $node_type_storage->load($this->entity->bundle())->label();
    drupal_set_message(t('@type %title has been deleted.', array('@type' => $node_type, '%title' => $this->entity->label())));
    $form_state->setRedirect('<front>');
  }

}
