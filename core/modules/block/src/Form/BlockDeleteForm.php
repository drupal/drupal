<?php

namespace Drupal\block\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 */
class BlockDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('block.admin_display');
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
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The @entity-type %label has been removed.', [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ]);
  }

}
