<?php

namespace Drupal\node\Plugin\views\area;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsArea;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Defines an area plugin to display a node/add link.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("node_listing_empty")]
class ListingEmpty extends AreaPluginBase {

  /**
   * Constructs a new ListingEmpty.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The access manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccessManagerInterface $accessManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $account = \Drupal::currentUser();
    if (!$empty || !empty($this->options['empty'])) {
      $element = [
        '#theme' => 'links',
        '#links' => [
          [
            'url' => Url::fromRoute('entity.node.add_page'),
            'title' => $this->t('Add content'),
          ],
        ],
        '#access' => $this->accessManager->checkNamedRoute('entity.node.add_page', [], $account),
      ];
      return $element;
    }
    return [];
  }

}
