<?php

namespace Drupal\layout_builder\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access check for the Layout Builder defaults.
 *
 * @ingroup layout_builder_access
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutBuilderAccessCheck implements AccessInterface {

  /**
   * Constructs a new LayoutBuilderAccessCheck class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    protected RouteMatchInterface $route_match,
  ) {}

  /**
   * Checks routing access to the layout.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(SectionStorageInterface $section_storage, AccountInterface $account, Route $route) {
    $operation = $route->getRequirement('_layout_builder_access');
    $access = $section_storage->access($operation, $account, TRUE);

    // Check for the global permission unless the section storage checks
    // permissions itself.
    if (!$section_storage->getPluginDefinition()->get('handles_permission_check')) {
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, 'configure any layout'));
    }

    // Disables access to inline blocks add_block routes if the section storage
    // opts out.
    // Check if inline block access should be disabled.
    if ($operation === 'add_block' && !($section_storage->getPluginDefinition()->get('allow_inline_blocks') ?? TRUE)) {
      $route_name = $this->route_match->getRouteName();
      $is_inline_block = str_starts_with((string) $this->route_match->getParameter('plugin_id'), 'inline_block:');

      if ($route_name === 'layout_builder.choose_inline_block' || ($route_name === 'layout_builder.add_block' && $is_inline_block)) {
        $access = $access->andIf(AccessResult::forbidden());
      }
    }

    if ($access instanceof RefinableCacheableDependencyInterface) {
      // @todo https://www.drupal.org/project/drupal/issues/3446509 Decide if
      // this logic needs to be changed.
      if ($section_storage instanceof CacheableDependencyInterface) {
        $access->addCacheableDependency($section_storage);
      }
      else {
        $access->setCacheMaxAge(0);
      }
    }
    return $access;
  }

}
