<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Block\AggregatorCategoryBlock.
 */

namespace Drupal\aggregator\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides an 'Aggregator category' block for the latest items in a category.
 *
 * @Block(
 *   id = "aggregator_category_block",
 *   admin_label = @Translation("Aggregator category"),
 *   derivative = "Drupal\aggregator\Plugin\Derivative\AggregatorCategoryBlock"
 * )
 */
class AggregatorCategoryBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function settings() {
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
    $id = $this->getPluginId();
    if ($category = db_query('SELECT cid, title, block FROM {aggregator_category} WHERE cid = :cid', array(':cid' => $id))->fetchObject()) {
      $result = db_query_range('SELECT i.* FROM {aggregator_category_item} ci LEFT JOIN {aggregator_item} i ON ci.iid = i.iid WHERE ci.cid = :cid ORDER BY i.timestamp DESC, i.iid DESC', 0, $this->configuration['block_count'], array(':cid' => $category->cid));
      $more_link = array(
        '#theme' => 'more_link',
        '#url' => 'aggregator/categories/' . $category->cid,
        '#title' => t("View this category's recent news."),
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
      return array();
    }
  }

}
