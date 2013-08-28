<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedItemsRemoveForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;

/**
 * Provides a deletion confirmation form for items that belong to a feed.
 */
class FeedItemsRemoveForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove all items from the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/services/aggregator';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove items');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->removeItems();

    $form_state['redirect'] = 'admin/config/services/aggregator';
  }

}
