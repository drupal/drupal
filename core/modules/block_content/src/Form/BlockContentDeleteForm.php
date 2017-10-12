<?php

namespace Drupal\block_content\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting a custom block entity.
 *
 * @internal
 */
class BlockContentDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $instances = $this->entity->getInstances();

    $form['message'] = [
      '#markup' => $this->formatPlural(count($instances), 'This will also remove 1 placed block instance.', 'This will also remove @count placed block instances.'),
      '#access' => !empty($instances),
    ];

    return parent::buildForm($form, $form_state);
  }

}
