<?php

/**
 * @file
 * Contains Drupal\aggregator\Form\CategorizeFeedForm
 */

namespace Drupal\aggregator\Form;

use Drupal\aggregator\FeedInterface;

/**
 * A form for categorizing feed items from a feed.
 */
class CategorizeFeedForm extends AggregatorCategorizeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'aggregator_page_source_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FeedInterface $aggregator_feed = NULL) {
    $this->feed = $aggregator_feed;
    $items = $this->aggregatorItemStorage->loadByFeed($aggregator_feed->id());
    return parent::buildForm($form, $form_state, $items);
  }

}
