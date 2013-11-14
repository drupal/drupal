<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedItemsRemoveForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a deletion confirmation form for items that belong to a feed.
 */
class FeedItemsRemoveForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove all items from the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'aggregator.admin_overview',
    );
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

    $form_state['redirect_route']['route_name'] = 'aggregator.admin_overview';
  }

}
