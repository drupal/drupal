<?php

namespace Drupal\node\Plugin\views\area;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsArea;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an area plugin to display a node/add link.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("node_listing_empty")]
class ListingEmpty extends AreaPluginBase {

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Constructs a new ListingEmpty.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager')
    );
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
            'url' => Url::fromRoute('node.add_page'),
            'title' => $this->t('Add content'),
          ],
        ],
        '#access' => $this->accessManager->checkNamedRoute('node.add_page', [], $account),
      ];
      return $element;
    }
    return [];
  }

}
