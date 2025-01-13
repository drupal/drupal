<?php

namespace Drupal\navigation;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle rendering for different pieces of the navigation.
 *
 * @internal The navigation module is experimental.
 */
final class NavigationRenderer {

  /**
   * Use the default Drupal logo in the navigation.
   */
  const LOGO_PROVIDER_DEFAULT = 'default';

  /**
   * Hide the logo in the navigation.
   */
  const LOGO_PROVIDER_HIDE = 'hide';

  /**
   * Use the custom provided logo in the navigation.
   */
  const LOGO_PROVIDER_CUSTOM = 'custom';

  /**
   * A list of all the link paths of enabled content entities.
   *
   * @var array
   */
  protected array $contentEntityPaths;

  /**
   * The navigation local tasks render array.
   *
   * @var array
   */
  protected array $localTasks;

  /**
   * Construct a new NavigationRenderer object.
   */
  public function __construct(
    private ConfigFactoryInterface $configFactory,
    private ModuleHandlerInterface $moduleHandler,
    private RouteMatchInterface $routeMatch,
    private LocalTaskManagerInterface $localTaskManager,
    private EntityTypeManagerInterface $entityTypeManager,
    private ImageFactory $imageFactory,
    private FileUrlGeneratorInterface $fileUrlGenerator,
    private SectionStorageManagerInterface $sectionStorageManager,
    private RequestStack $requestStack,
    private ModuleExtensionList $moduleExtensionList,
    private AccountInterface $currentUser,
  ) {}

  /**
   * Remove the toolbar provided by Toolbar module.
   *
   * @param array $page_top
   *   A renderable array representing the top of the page.
   *
   * @see toolbar_page_top()
   * @see hook_page_top()
   */
  public function removeToolbar(array &$page_top): void {
    if (isset($page_top['toolbar'])) {
      unset($page_top['toolbar']);
    }
  }

  /**
   * Build out the navigation bar.
   *
   * @param array $page_top
   *   A renderable array representing the top of the page.
   *
   * @see toolbar_page_top()
   * @see hook_page_top()
   */
  public function buildNavigation(array &$page_top): void {
    $page_top['navigation'] = [
      '#cache' => [
        'keys' => ['navigation', 'navigation'],
        'max-age' => CacheBackendInterface::CACHE_PERMANENT,
      ],
      '#pre_render' => ['navigation.renderer:doBuildNavigation'],
    ];
  }

  /**
   * Pre-render callback for ::buildNavigation.
   */
  #[TrustedCallback]
  public function doBuildNavigation($build): array {
    $logo_settings = $this->configFactory->get('navigation.settings');
    $logo_provider = $logo_settings->get('logo.provider');

    $cacheability = new CacheableMetadata();
    $contexts = [
      'navigation' => new Context(ContextDefinition::create('string'), 'navigation'),
    ];
    $storage = $this->sectionStorageManager->findByContext($contexts, $cacheability);

    if ($storage) {
      foreach ($storage->getSections() as $delta => $section) {
        $build[$delta] = $section->toRenderArray([]);
        $build[$delta]['#cache']['contexts'] = ['user.permissions', 'theme', 'languages:language_interface'];
      }
    }
    // The render array is built based on decisions made by SectionStorage
    // plugins and therefore it needs to depend on the accumulated
    // cacheability of those decisions.
    $cacheability->addCacheableDependency($logo_settings)
      ->addCacheableDependency($this->configFactory->get('navigation.block_layout'));
    $cacheability->applyTo($build);

    $module_path = $this->requestStack->getCurrentRequest()->getBasePath() . '/' . $this->moduleExtensionList->getPath('navigation');
    $asset_url = $module_path . '/assets/fonts/inter-var.woff2';

    $defaults = [
      'settings' => ['hide_logo' => $logo_provider === self::LOGO_PROVIDER_HIDE],
      '#attached' => [
        'html_head_link' => [
          [
            [
              'rel' => 'preload',
              'href' => $asset_url,
              'as' => 'font',
              'crossorigin' => 'anonymous',
            ],
          ],
        ],
      ],
    ];
    $build[0] = NestedArray::mergeDeepArray([$build[0], $defaults]);

    $build[0]['content_top'] = $this->getContentTop();

    if ($logo_provider === self::LOGO_PROVIDER_CUSTOM) {
      $logo_path = $logo_settings->get('logo.path');
      if (!empty($logo_path) && is_file($logo_path)) {
        $logo_managed_url = $this->fileUrlGenerator->generateAbsoluteString($logo_path);
        $image = $this->imageFactory->get($logo_path);
        $build[0]['settings']['logo_path'] = $logo_managed_url;
        if ($image->isValid()) {
          $build[0]['settings']['logo_width'] = $image->getWidth();
          $build[0]['settings']['logo_height'] = $image->getHeight();
        }
      }
    }
    return $build;
  }

  /**
   * Gets the content for content_top section.
   *
   * @return array
   *   The content_top section content.
   */
  protected function getContentTop(): array {
    $content_top = [
      '#theme' => 'navigation_content_top',
    ];
    $content_top_items = $this->moduleHandler->invokeAll('navigation_content_top');
    $this->moduleHandler->alter('navigation_content_top', $content_top_items);
    uasort($content_top_items, [SortArray::class, 'sortByWeightElement']);
    // Filter out empty items, taking care to merge any cacheability metadata.
    $cacheability = new CacheableMetadata();
    $content_top_items = array_filter($content_top_items, function ($item) use (&$cacheability) {
      if (Element::isEmpty($item)) {
        $cacheability = $cacheability->merge(CacheableMetadata::createFromRenderArray($item));
        return FALSE;
      }
      return TRUE;
    });
    $cacheability->applyTo($content_top);
    $content_top['#items'] = $content_top_items;
    return $content_top;
  }

  /**
   * Build the top bar for content entity pages.
   *
   * @param array $page_top
   *   A renderable array representing the top of the page.
   *
   * @see navigation_page_top()
   * @see hook_page_top()
   */
  public function buildTopBar(array &$page_top): void {
    if (!$this->moduleHandler->moduleExists('navigation_top_bar')) {
      return;
    }

    $page_top['top_bar'] = [
      '#type' => 'top_bar',
      '#access' => $this->currentUser->hasPermission('access navigation'),
      '#cache' => [
        'keys' => ['top_bar'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * Alter the build of any local_tasks_block plugin block.
   *
   * If we are showing the local tasks in the top bar, hide the local tasks
   * from display to avoid duplicating the links.
   *
   * @param array $build
   *   A renderable array representing the local_tasks_block plugin block to be
   *   rendered.
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   Block plugin object representing a local_tasks_block.
   *
   * @see navigation_block_build_local_tasks_block_alter()
   */
  public function removeLocalTasks(array &$build, BlockPluginInterface $block): void {
    if ($block->getPluginId() !== 'local_tasks_block') {
      return;
    }
    if ($this->hasLocalTasks() && $this->moduleHandler->moduleExists('navigation_top_bar')) {
      $build['#access'] = FALSE;
    }
  }

  /**
   * Local tasks list based on user access.
   *
   * @return array
   *   Local tasks keyed by route name.
   */
  public function getLocalTasks(): array {
    if (isset($this->localTasks)) {
      return $this->localTasks;
    }

    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->localTaskManager);
    $this->localTasks = [
      'tasks' => [],
      'cacheability' => $cacheability,
    ];
    // For now, we're only interested in local tasks corresponding to a content
    // entity.
    if (!$this->meetsContentEntityRoutesCondition()) {
      return $this->localTasks;
    }
    $entity_local_tasks = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName());
    foreach ($entity_local_tasks['tabs'] as $route_name => $local_task) {
      // The $local_task array that we get here is tailor-made for use
      // with the menu-local-tasks.html.twig, eg. the menu_local_task
      // theme hook. It has all the information we need, but we're not
      // rendering local tasks, or tabs, we're rendering a simple list of
      // links. Here we're taking advantage of all the good stuff found in
      // the render array, namely the #link, and #access properties, using
      // them to render a simple link.
      // @see \Drupal\Core\Menu\LocalTaskManager::getTasksBuild()
      $link = $local_task['#link'];
      $link['localized_options'] += [
        'set_active_class' => TRUE,
      ];
      $this->localTasks['tasks'][$route_name] = [
        '#theme' => 'top_bar_local_task',
        '#link' => [
          '#type' => 'link',
          '#title' => $link['title'],
          '#url' => $link['url'],
          '#options' => $link['localized_options'],
        ],
        '#access' => $local_task['#access'],
      ];
    }
    $this->localTasks['cacheability'] = $cacheability->merge($entity_local_tasks['cacheability']);

    return $this->localTasks;
  }

  /**
   * Do we have local tasks that we want to show in the top bar?
   *
   * @return bool
   *   TRUE if there are local tasks available for the top bar, FALSE otherwise.
   */
  public function hasLocalTasks(): bool {
    $local_tasks = $this->getLocalTasks();
    return !empty($local_tasks['tasks']);
  }

  /**
   * Determines if content entity route condition is met.
   *
   * @return bool
   *   TRUE if the content entity route condition is met, FALSE otherwise.
   */
  protected function meetsContentEntityRoutesCondition(): bool {
    return array_key_exists($this->routeMatch->getRouteObject()->getPath(), $this->getContentEntityPaths());
  }

  /**
   * Returns the paths for the link templates of all content entities.
   *
   * @return array
   *   An array of all content entity type IDs, keyed by the corresponding link
   *   template paths.
   */
  protected function getContentEntityPaths(): array {
    if (isset($this->contentEntityPaths)) {
      return $this->contentEntityPaths;
    }

    $this->contentEntityPaths = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if ($entity_type->entityClassImplements(ContentEntityInterface::class)) {
        $entity_paths = $this->getContentEntityTypePaths($entity_type);
        $this->contentEntityPaths = array_merge($this->contentEntityPaths, $entity_paths);
      }
    }

    return $this->contentEntityPaths;
  }

  /**
   * Returns the path for the link template for a given content entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return array
   *   Array containing the paths for the given content entity type.
   */
  protected function getContentEntityTypePaths(EntityTypeInterface $entity_type): array {
    $paths = array_filter($entity_type->getLinkTemplates(), fn ($template) => $template !== 'collection', ARRAY_FILTER_USE_KEY);
    if ($this->isLayoutBuilderEntityType($entity_type)) {
      $paths[] = $entity_type->getLinkTemplate('canonical') . '/layout';
    }
    return array_fill_keys($paths, $entity_type->id());
  }

  /**
   * Determines if a given entity type is layout builder relevant or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return bool
   *   Whether this entity type is a Layout builder candidate or not
   *
   * @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::getEntityTypes()
   */
  protected function isLayoutBuilderEntityType(EntityTypeInterface $entity_type): bool {
    return $entity_type->entityClassImplements(FieldableEntityInterface::class) && $entity_type->hasHandlerClass('form', 'layout_builder') && $entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical');
  }

}
