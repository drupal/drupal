<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedItemsDelete.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\aggregator\Plugin\Core\Entity\Feed;

/**
 * Provides a deletion confirmation form for items that belong to a feed.
 */
class FeedItemsDelete extends ConfirmFormBase {

  /**
   * The feed the items being deleted belong to.
   *
   * @var \Drupal\aggregator\Plugin\Core\Entity\Feed
   */
  protected $feed;

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'aggregator_feed_items_delete_form';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('Are you sure you want to remove all items from the feed %feed?', array('%feed' => $this->feed->label()));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin/config/services/aggregator';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  protected function getConfirmText() {
    return t('Remove items');
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, Feed $aggregator_feed = NULL) {
    $this->feed = $aggregator_feed;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    // @todo Remove once http://drupal.org/node/1930274 is fixed.
    aggregator_remove($this->feed);
    $form_state['redirect'] = 'admin/config/services/aggregator';
  }

}
