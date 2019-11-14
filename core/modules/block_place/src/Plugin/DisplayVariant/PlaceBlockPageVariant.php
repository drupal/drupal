<?php

namespace Drupal\block_place\Plugin\DisplayVariant;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Plugin\DisplayVariant\BlockPageVariant;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows blocks to be placed directly within a region.
 *
 * @PageDisplayVariant(
 *   id = "block_place_page",
 *   admin_label = @Translation("Page with blocks and place block buttons")
 * )
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See the
 * change record for a list of alternatives.
 *
 * @see https://www.drupal.org/node/3081957
 */
class PlaceBlockPageVariant extends BlockPageVariant {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new PlaceBlockPageVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\block\BlockRepositoryInterface $block_repository
   *   The block repository.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $block_view_builder
   *   The block view builder.
   * @param string[] $block_list_cache_tags
   *   The Block entity type list cache tags.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockRepositoryInterface $block_repository, EntityViewBuilderInterface $block_view_builder, array $block_list_cache_tags, ThemeManagerInterface $theme_manager, RedirectDestinationInterface $redirect_destination) {
    @trigger_error('The ' . __NAMESPACE__ . '\PlaceBlockPageVariant is deprecated in drupal:8.8.0 and will be removed from drupal:9.0.0. See the change record for a list of alternatives. See https://www.drupal.org/node/3081957.', E_USER_DEPRECATED);

    parent::__construct($configuration, $plugin_id, $plugin_definition, $block_repository, $block_view_builder, $block_list_cache_tags);

    $this->themeManager = $theme_manager;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('block.repository'),
      $container->get('entity_type.manager')->getViewBuilder('block'),
      $container->get('entity_type.manager')->getDefinition('block')->getListCacheTags(),
      $container->get('theme.manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $active_theme = $this->themeManager->getActiveTheme();
    $theme_name = $active_theme->getName();
    $destination = $this->redirectDestination->get();
    $visible_regions = $this->getVisibleRegionNames($theme_name);

    // Build an array of the region names in the right order.
    $build += array_fill_keys(array_keys($visible_regions), []);

    foreach ($visible_regions as $region => $region_name) {
      $query = [
        'region' => $region,
      ];
      if ($destination) {
        $query['destination'] = $destination;
      }
      $title = $this->t('<span class="visually-hidden">Place block in the %region region</span>', ['%region' => $region_name]);
      $operations['block_description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-place-region">{{ link }}</div>',
        '#context' => [
          'link' => Link::createFromRoute($title, 'block.admin_library', ['theme' => $theme_name], [
            'query' => $query,
            'attributes' => [
              'title' => $title,
              'class' => ['use-ajax', 'button', 'button--small'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
              ]),
            ],
          ]),
        ],
      ];
      $build[$region] = ['block_place_operations' => $operations] + $build[$region];
    }
    $build['#attached']['library'][] = 'block_place/drupal.block_place';
    return $build;
  }

  /**
   * Returns the human-readable list of regions keyed by machine name.
   *
   * @param string $theme
   *   The name of the theme.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  protected function getVisibleRegionNames($theme) {
    return system_region_list($theme, REGIONS_VISIBLE);
  }

}
