<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "block_settings"
 * )
 */
class BlockSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Set the block configuration.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($plugin, $delta, $old_settings, $title) = $value;
    $settings = [];
    $settings['label'] = $title;
    if ($title && $title !== '<none>') {
      $settings['label_display'] = BlockPluginInterface::BLOCK_LABEL_VISIBLE;
    }
    else {
      $settings['label_display'] = '0';
    }
    switch ($plugin) {
      case 'aggregator_feed_block':
        list(, $id) = explode('-', $delta);
        $settings['block_count'] = $old_settings['aggregator']['item_count'];
        $settings['feed'] = $id;
        break;

      case 'book_navigation':
        $settings['block_mode'] = $old_settings['book']['block_mode'];
        break;

      case 'forum_active_block':
      case 'forum_new_block':
        $settings['block_count'] = $old_settings['forum']['block_num'];
        break;

      case 'statistics_popular_block':
        $settings['top_day_num'] = $old_settings['statistics']['statistics_block_top_day_num'];
        $settings['top_all_num'] = $old_settings['statistics']['statistics_block_top_all_num'];
        $settings['top_last_num'] = $old_settings['statistics']['statistics_block_top_last_num'];
        break;

      case 'views_block:who_s_new-block_1':
        $settings['items_per_page'] = $old_settings['user']['block_whois_new_count'];
        break;

      case 'views_block:who_s_online-who_s_online_block':
        $settings['items_per_page'] = $old_settings['user']['max_list_count'];
        break;
    }
    return $settings;
  }

}
