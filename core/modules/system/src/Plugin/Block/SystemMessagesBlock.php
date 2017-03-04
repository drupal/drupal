<?php

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MessagesBlockPluginInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a block to display the messages.
 *
 * @see drupal_set_message()
 *
 * @Block(
 *   id = "system_messages_block",
 *   admin_label = @Translation("Messages")
 * )
 */
class SystemMessagesBlock extends BlockBase implements MessagesBlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#type' => 'status_messages'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // The messages are session-specific and hence aren't cacheable, but the
    // block itself *is* cacheable because it uses a #lazy_builder callback and
    // hence the block has a globally cacheable render array.
    return Cache::PERMANENT;
  }

}
