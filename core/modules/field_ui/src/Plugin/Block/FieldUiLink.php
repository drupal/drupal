<?php

namespace Drupal\field_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a link to add content page.
 *
 * @Block(
 *   id = "field_ui_link",
 *   admin_label = @Translation("Add Content")
 * )
 */
class FieldUiLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node_type = '';
    $path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $path);
    if ($path_args[4] == 'manage') {
      $node_type = $path_args[5];
    }
    $url = Url::fromRoute('node.add', ['node_type' => $node_type]);

    return [
      '#type' => 'container',
      'link' => [
        '#type' => 'link',
        '#title' => '+ ' . $this->t('Add @node_type', ['@node_type' => $node_type]),
        '#url' => $url,
        '#attributes' => [
          'class' => ['button'],
        ],
        '#attached' => ['library' => ['field_ui/drupal.field_ui_css']],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
