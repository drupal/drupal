<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Block\AggregatorFeedBlock.
 */

namespace Drupal\aggregator\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides an 'Aggregator feed' block with the latest items from the feed.
 *
 * @Block(
 *   id = "aggregator_feed_block",
 *   admin_label = @Translation("Aggregator feed"),
 *   derivative = "Drupal\aggregator\Plugin\Derivative\AggregatorFeedBlock"
 * )
 */
class AggregatorFeedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will contain 10 feed items.
    return array(
      'block_count' => 10,
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    // Only grant access to users with the 'access news feeds' permission.
    return user_access('access news feeds');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of news items in block'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => drupal_map_assoc(range(2, 20)),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['block_count'] = $form_state['values']['block_count'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Plugin IDs look something like this: aggregator_feed_block:1.
    list(, $id) = explode(':', $this->getPluginId());
    if ($feed = db_query('SELECT fid, title, block FROM {aggregator_feed} WHERE block <> 0 AND fid = :fid', array(':fid' => $id))->fetchObject()) {
      $result = db_query_range("SELECT * FROM {aggregator_item} WHERE fid = :fid ORDER BY timestamp DESC, iid DESC", 0, $this->configuration['block_count'], array(':fid' => $id));
      $more_link = array(
        '#theme' => 'more_link',
        '#url' => 'aggregator/sources/' . $feed->fid,
        '#title' => t("View this feed's recent news."),
      );
      $read_more = drupal_render($more_link);
      $items = array();
      foreach ($result as $item) {
        $aggregator_block_item = array(
          '#theme' => 'aggregator_block_item',
          '#item' => $item,
        );
        $items[] = drupal_render($aggregator_block_item);
      }
      // Only display the block if there are items to show.
      if (count($items) > 0) {
        $item_list = array(
          '#theme' => 'item_list',
          '#items' => $items,
        );
        return array(
          '#children' => drupal_render($item_list) . $read_more,
        );
      }
    }
  }

}
