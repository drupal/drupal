<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\area\ListingEmpty.
 */

namespace Drupal\node\Plugin\views\area;

use Drupal\Core\Access\AccessManager;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an area plugin to display a node/add link.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("node_listing_empty")
 */
class ListingEmpty extends AreaPluginBase {

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * Constructs a new ListingEmpty.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccessManager $access_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
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
      $element = array(
        '#theme' => 'links',
        '#links' => array(
          array(
            'href' => 'node/add',
            'title' => $this->t('Add content'),
          ),
        ),
        '#access' => $this->accessManager->checkNamedRoute('node.add_page', array(), $account),
      );
      return $element;
    }
    return array();
  }

}
