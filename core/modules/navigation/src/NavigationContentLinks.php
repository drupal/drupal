<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Build the menu links for the Content menu.
 *
 * The content menu contains a "Create" section, along with links to other
 * overview pages for different entity types.
 *
 * @internal The navigation module is experimental.
 */
final class NavigationContentLinks implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Construct a new NavigationContentLinks object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(private RouteProviderInterface $routeProvider, private EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Add links to the Content menu, based on enabled modules.
   *
   * @param array $links
   *   The array of links being altered.
   */
  public function addMenuLinks(array &$links): void {
    // First, add the top-level menu items.
    // @todo Consider turning this into a data object so we can avoid typos in
    // array keys.
    $content_links = [
      'navigation.create' => [
        'route_name' => 'node.add_page',
        'title' => $this->t('Create'),
        'weight' => -10,
      ],
      'navigation.content' => [
        'route_name' => 'view.content.page_1',
        'title' => $this->t('Content'),
      ],
      'navigation.files' => [
        'route_name' => 'view.files.page_1',
        'title' => $this->t('Files'),
      ],
      'navigation.media' => [
        'route_name' => 'view.media.media_page_list',
        'title' => $this->t('Media'),
      ],
      'navigation.blocks' => [
        'route_name' => 'view.block_content.page_1',
        'title' => $this->t('Blocks'),
      ],
    ];

    foreach ($content_links as $link_name => $link) {
      $this->addLink($link_name, $link, $links);
    }

    // Add supported add links under the Create button.
    $this->addCreateEntityLinks('node_type', 'node.add', $links);
    $this->addCreateEntityLinks('media_type', 'entity.media.add_form', $links, ['document', 'image']);

    // Finally, add the bundleless User link and pin it to the bottom.
    $this->addLink('navigation.create.user', [
      'route_name' => 'user.admin_create',
      'title' => $this->t('User'),
      'parent' => 'navigation.create',
      'weight' => 100,
    ], $links);
  }

  /**
   * Remove the admin/content link, and any direct children.
   *
   * @param array $links
   *   The array of links being altered.
   */
  public function removeAdminContentLink(array &$links): void {
    unset($links['system.admin_content']);

    // Also remove any links that have set admin/content as their parent link.
    // They are unsupported by the Navigation module.
    foreach ($links as $link_name => $link) {
      if (isset($link['parent']) && $link['parent'] === 'system.admin_content') {
        // @todo Do we need to make this recursive, and unset children of these
        // links too?
        unset($links[$link_name]);
      }
    }
  }

  /**
   * Remove the help link as render it outside any menu.
   *
   * @param array $links
   *   The array of links being altered.
   */
  public function removeHelpLink(array &$links): void {
    unset($links['help.main']);
  }

  /**
   * Add create links for an entity type.
   *
   * This function preserves the order of entity types as it is called.
   *
   * @param string $entity_type
   *   The entity type to add links for, such as node_type.
   * @param string $add_route_id
   *   The ID of the route for the entity type add form.
   * @param array $links
   *   The existing array of links to add to.
   * @param array $bundle_allow_list
   *   A list of allowed bundles to include. Can be used to limit the list of
   *   bundles that are included for noisy entity types like media.
   */
  private function addCreateEntityLinks(string $entity_type, string $add_route_id, array &$links, array $bundle_allow_list = []): void {
    // Ensure subsequent calls always get added to the bottom, and not in
    // alphabetical order.
    static $weight = 0;

    // The module providing the entity type is either not installed, or in the
    // process of being uninstalled.
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      return;
    }

    // Sort all types within an entity type alphabetically.
    $definition = $this->entityTypeManager->getDefinition($entity_type);
    $types = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
    if (method_exists($definition->getClass(), 'sort')) {
      uasort($types, [$definition->getClass(), 'sort']);
    }

    $add_content_links = [];
    foreach ($types as $type) {
      // Skip if the bundle is not in the allow list.
      if (!empty($bundle_allow_list) && !in_array($type->id(), $bundle_allow_list)) {
        continue;
      }
      $add_content_links['navigation.content.' . $type->getEntityTypeId() . '.' . $type->id()] = [
        'title' => $type->label(),
        'route_name' => $add_route_id,
        'route_parameters' => [
          $entity_type => $type->id(),
        ],
        'parent' => 'navigation.create',
        'weight' => $weight,
      ];
    }

    foreach ($add_content_links as $link_name => $link) {
      $this->addLink($link_name, $link, $links);
    }

    $weight++;
  }

  /**
   * Ensure a route exists and add the link.
   *
   * @param string $link_name
   *   The name of the link being added.
   * @param array $link
   *   The link array, as defined in hook_menu_links_discovered_alter().
   * @param array $links
   *   The existing array of links.
   */
  private function addLink(string $link_name, array $link, array &$links): void {
    try {
      // Ensure the route exists (there is no separate "exists" method).
      $this->routeProvider->getRouteByName($link['route_name']);
      $links[$link_name] = $link + ['menu_name' => 'content', 'provider' => 'navigation'];
    }
    catch (RouteNotFoundException) {
      // The module isn't installed, or the route (such as provided by a view)
      // has been deleted.
    }
  }

}
