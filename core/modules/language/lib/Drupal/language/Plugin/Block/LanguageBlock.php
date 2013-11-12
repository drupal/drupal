<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\language\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Language switcher' block.
 *
 * @Block(
 *   id = "language_block",
 *   admin_label = @Translation("Language switcher"),
 *   category = @Translation("System"),
 *   derivative = "Drupal\language\Plugin\Derivative\LanguageBlock"
 * )
 */
class LanguageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  function access(AccountInterface $account) {
    return language_multilingual();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    $path = drupal_is_front_page() ? '<front>' : current_path();
    list(, $type) = explode(':', $this->getPluginId());
    $links = language_negotiation_get_switch_links($type, $path);

    if (isset($links->links)) {
      $build = array(
        '#theme' => 'links__language_block',
        '#links' => $links->links,
        '#attributes' => array(
          'class' => array(
            "language-switcher-{$links->method_id}",
          ),
        ),
      );
    }
    return $build;
  }

}
