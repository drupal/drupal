<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\DisplayVariant\FullPageVariant.
 */

namespace Drupal\block\Plugin\DisplayVariant;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a display variant that represents the full page.
 *
 * @DisplayVariant(
 *   id = "full_page",
 *   admin_label = @Translation("Full page")
 * )
 */
class FullPageVariant extends VariantBase implements ContainerFactoryPluginInterface {

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockStorage;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $blockViewBuilder;

  /**
   * The current theme.
   *
   * @var string
   */
  protected $theme;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  protected $themeNegotiator;

  /**
   * Constructs a new FullPageVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_storage
   *   The block entity storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $block_view_builder
   *   The block view builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $block_storage, EntityViewBuilderInterface $block_view_builder, RouteMatchInterface $route_match, ThemeNegotiatorInterface $theme_negotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockStorage = $block_storage;
    $this->blockViewBuilder = $block_view_builder;
    $this->routeMatch = $route_match;
    $this->themeNegotiator = $theme_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('block'),
      $container->get('entity.manager')->getViewBuilder('block'),
      $container->get('current_route_match'),
      $container->get('theme.negotiator')
    );
  }

  /**
   * Gets the current theme for this page.
   *
   * @return string
   *   The current theme.
   */
  protected function getTheme() {
    return $this->themeNegotiator->determineActiveTheme($this->routeMatch);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    // Load all region content assigned via blocks.
    foreach ($this->getRegionAssignments() as $region => $blocks) {
      /** @var $blocks \Drupal\block\BlockInterface[] */
      foreach ($blocks as $key => $block) {
        if ($block->access('view')) {
          $build[$region][$key] = $this->blockViewBuilder->view($block);
        }
      }
      if (!empty($build[$region])) {
        // self::getRegionAssignments() returns the blocks in sorted order.
        $build[$region]['#sorted'] = TRUE;
      }
    }
    return $build;
  }

  /**
   * Returns the human-readable list of regions keyed by machine name.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  protected function getRegionNames() {
    return system_region_list($this->getTheme());
  }

  /**
   * Returns an array of regions and their block entities.
   *
   * @return array
   *   The array is first keyed by region machine name, with the values
   *   containing an array keyed by block ID, with block entities as the values.
   */
  protected function getRegionAssignments() {
    // Build an array of the region names in the right order.
    $empty = array_fill_keys(array_keys($this->getRegionNames()), array());

    $full = array();
    foreach ($this->blockStorage->loadByProperties(array('theme' => $this->getTheme())) as $block_id => $block) {
      $full[$block->get('region')][$block_id] = $block;
    }

    // Merge it with the actual values to maintain the region ordering.
    $assignments = array_intersect_key(array_merge($empty, $full), $empty);
    foreach ($assignments as &$assignment) {
      // Suppress errors because PHPUnit will indirectly modify the contents,
      // triggering https://bugs.php.net/bug.php?id=50688.
      @uasort($assignment, 'Drupal\block\Entity\Block::sort');
    }
    return $assignments;
  }

}
