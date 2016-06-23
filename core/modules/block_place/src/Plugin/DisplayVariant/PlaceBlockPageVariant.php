<?php

namespace Drupal\block_place\Plugin\DisplayVariant;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Plugin\DisplayVariant\BlockPageVariant;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Allows blocks to be placed directly within a region.
 *
 * @PageDisplayVariant(
 *   id = "block_place_page",
 *   admin_label = @Translation("Page with blocks and place block buttons")
 * )
 */
class PlaceBlockPageVariant extends BlockPageVariant {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockRepositoryInterface $block_repository, EntityViewBuilderInterface $block_view_builder, array $block_list_cache_tags, ThemeManagerInterface $theme_manager, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $block_repository, $block_view_builder, $block_list_cache_tags);

    $this->themeManager = $theme_manager;
    $this->requestStack = $request_stack;
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
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $active_theme = $this->themeManager->getActiveTheme();
    $theme_name = $active_theme->getName();
    $visible_regions = $this->getVisibleRegionNames($theme_name);

    // Build an array of the region names in the right order.
    $build += array_fill_keys(array_keys($visible_regions), []);

    foreach ($visible_regions as $region => $region_name) {
      $destination = $this->requestStack->getCurrentRequest()->query->get('destination');
      $query = [
        'region' => $region,
      ];
      if ($destination) {
        $query['destination'] = $destination;
      }
      $title = $this->t('Place block<span class="visually-hidden"> in the %region region</span>', ['%region' => $region_name]);
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
