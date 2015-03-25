<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMessagesBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MessagesBlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

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
    return array(
      'label_display' => FALSE,
    );
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // @see ::isCacheable()
    $form['cache']['#description'] = $this->t('This block is cacheable forever, it is not configurable.');
    $form['cache']['max_age']['#value'] = Cache::PERMANENT;
    $form['cache']['max_age']['#disabled'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    // The messages are session-specific and hence aren't cacheable, but the
    // block itself *is* cacheable because it uses a #post_render_cache callback
    // and hence the block has a globally cacheable render array.
    return TRUE;
  }

}
