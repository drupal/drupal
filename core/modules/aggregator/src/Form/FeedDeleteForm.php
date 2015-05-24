<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedDeleteForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a feed.
 */
class FeedDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('aggregator.admin_overview');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The feed %label has been deleted.', array(
      '%label' => $this->entity->label(),
    ));
  }

}
