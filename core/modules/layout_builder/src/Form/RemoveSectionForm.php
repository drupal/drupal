<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to confirm the removal of a section.
 *
 * @internal
 */
class RemoveSectionForm extends LayoutRebuildConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_remove_section';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove this section?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  protected function handleEntity(EntityInterface $entity, FormStateInterface $form_state) {
    $entity->layout_builder__layout->removeItem($this->delta);
  }

}
