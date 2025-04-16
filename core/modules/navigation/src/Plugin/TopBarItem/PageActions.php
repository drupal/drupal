<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\TopBarItem;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\NavigationRenderer;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Page Actions basic top bar item.
 *
 * @internal
 */
#[TopBarItem(
  id: 'page_actions',
  region: TopBarRegion::Actions,
  label: new TranslatableMarkup('Page Actions'),
)]
final class PageActions extends TopBarItemBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a PageActions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\navigation\NavigationRenderer $navigationRenderer
   *   The navigation renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected NavigationRenderer $navigationRenderer,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(NavigationRenderer::class),
      $container->get(RouteMatchInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    // Local tasks for content entities.
    if (!$this->navigationRenderer->hasLocalTasks()) {
      return $build;
    }

    $page_actions = $this->navigationRenderer->getLocalTasks();
    $featured_page_actions = $this->getFeaturedPageActions($page_actions);

    // Filter actions to exclude featured ones from the main array.
    $page_actions['page_actions'] = array_filter($page_actions['page_actions'],
      static fn ($action_route) =>!array_key_exists($action_route, $featured_page_actions),
    ARRAY_FILTER_USE_KEY);

    $build += [
      '#theme' => 'top_bar_page_actions',
      '#page_actions' => $page_actions['page_actions'],
      '#featured_page_actions' => $featured_page_actions,
    ];

    assert($page_actions['cacheability'] instanceof CacheableMetadata);
    $page_actions['cacheability']->applyTo($build);

    return $build;
  }

  /**
   * Gets the featured local task.
   *
   * @param array $page_actions
   *   The array of local tasks for the current page.
   *
   * @return array|null
   *   The featured local task definition if available. NULL otherwise.
   */
  protected function getFeaturedPageActions(array $page_actions): ?array {
    $featured_page_actions = [];
    $current_route_name = $this->routeMatch->getRouteName();
    $canonical_pattern = '/^entity\.(.+?)\.(canonical|latest_version)$/';
    if (preg_match($canonical_pattern, $current_route_name, $matches)) {
      $entity_type = $matches[1];
      $edit_route = "entity.$entity_type.edit_form";
      // For core entities, the local task name matches the route name. If
      // needed, we could iterate over the items and check the actual route.
      if (isset($page_actions['page_actions'][$edit_route]) && $page_actions['page_actions'][$edit_route]['#access']?->isAllowed()) {
        $featured_page_actions[$edit_route] = [
          'page_action' => $page_actions['page_actions'][$edit_route],
          'icon' => [
            'icon_id' => 'pencil',
          ],
        ];
      }
    }
    return $featured_page_actions;
  }

}
