<?php

namespace Drupal\node\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for content type deletion.
 */
class NodeTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_nodes = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_nodes) {
      $caption = '<p>' . $this->formatPlural($num_nodes, '%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', '%type is used by @count pieces of content on your site. You may not remove %type until you have removed all of the %type content.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
