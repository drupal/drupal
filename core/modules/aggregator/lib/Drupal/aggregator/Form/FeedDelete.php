<?php
/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedDelete.
 */

namespace Drupal\aggregator\Form;


use Drupal\Core\Form\ConfirmFormBase;
use Drupal\aggregator\Plugin\Core\Entity\Feed;

/**
 * Provides a form for deleting a feed.
 */
class FeedDelete extends ConfirmFormBase {

  /**
   * The feed the being deleted.
   *
   * @var \Drupal\aggregator\Plugin\Core\Entity\Feed
   */
  protected $feed;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'aggregator_feed_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the feed %feed?', array('%feed' => $this->feed->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/config/services/aggregator';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Feed $aggregator_feed = NULL) {
    $this->feed = $aggregator_feed;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->feed->delete();
    watchdog('aggregator', 'Feed %feed deleted.', array('%feed' => $this->feed->label()));
    drupal_set_message(t('The feed %feed has been deleted.', array('%feed' => $this->feed->label())));
    if (arg(0) == 'admin') {
      $form_state['redirect'] = 'admin/config/services/aggregator';
    }
    else {
      $form_state['redirect'] = 'aggregator/sources';
    }
  }
}
