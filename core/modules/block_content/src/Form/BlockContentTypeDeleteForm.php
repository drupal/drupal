<?php

namespace Drupal\block_content\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting a custom block type entity.
 *
 * @internal
 */
class BlockContentTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $blocks = $this->entityTypeManager->getStorage('block_content')->getQuery()
      ->condition('type', $this->entity->id())
      ->execute();
    if (!empty($blocks)) {
      $caption = '<p>' . $this->formatPlural(count($blocks), '%label is used by 1 custom block on your site. You can not remove this block type until you have removed all of the %label blocks.', '%label is used by @count custom blocks on your site. You may not remove %label until you have removed all of the %label custom blocks.', ['%label' => $this->entity->label()]) . '</p>';
      $form['description'] = ['#markup' => $caption];
      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

}
