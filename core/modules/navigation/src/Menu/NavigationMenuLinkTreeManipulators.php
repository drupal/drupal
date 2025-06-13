<?php

declare(strict_types=1);

namespace Drupal\navigation\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\system\Controller\SystemController;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a menu link tree manipulator for the navigation menu block.
 */
class NavigationMenuLinkTreeManipulators {

  use StringTranslationTrait;

  public function __construct(
    protected readonly RouteProviderInterface $routeProvider,
    protected readonly StaticMenuLinkOverridesInterface $overrides,
    TranslationInterface $translation,
  ) {
    $this->setStringTranslation($translation);
  }

  /**
   * Adds an "overview" child link to second level menu links with children.
   *
   * In the navigation menu, a second-level menu item is a link if it does not
   * have children, but if it does, it instead becomes a button that opens
   * its child menu. To provide a way to access the page a second-level menu
   * item links to, add an "overview" link that links to the page as a child
   * (third-level) menu item.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function addSecondLevelOverviewLinks(array $tree): array {
    if (!$tree) {
      return [];
    }

    foreach ($tree as $item) {
      if (!$this->isEnabledAndAccessible($item)) {
        continue;
      }
      foreach ($item->subtree as $sub_item) {
        if ($this->shouldAddOverviewLink($sub_item)) {
          $this->addOverviewLink($sub_item);
        }
      }
    }

    return $tree;
  }

  /**
   * Whether a menu tree element should have an overview link added to it.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $element
   *   The menu link tree element to check.
   *
   * @return bool
   *   TRUE if menu tree element should have a child overview link added.
   */
  protected function shouldAddOverviewLink(MenuLinkTreeElement $element): bool {
    if (empty($element->subtree) || !$this->isEnabledAndAccessible($element)) {
      return FALSE;
    }

    $route_name = $element->link->getRouteName();
    if (in_array($route_name, ['<nolink>', '<button>'])) {
      return FALSE;
    }

    $has_visible_children = FALSE;
    foreach ($element->subtree as $sub_element) {
      // Do not add overview link if there are no accessible or enabled
      // children.
      if ($this->isEnabledAndAccessible($sub_element)) {
        $has_visible_children = TRUE;
      }

      // Do not add overview link if there is already a child linking to the
      // same URL.
      if ($sub_element->link->getRouteName() === $route_name) {
        return FALSE;
      }
    }

    if (!$has_visible_children) {
      return FALSE;
    }

    // The systemAdminMenuBlockPage() method in SystemController returns a list
    // of child menu links for the page. If the second-level menu item link's
    // route uses that controller, do not add the overview link, because that
    // duplicates what is already in the navigation menu.
    try {
      $controller = ltrim($this->routeProvider->getRouteByName($route_name)->getDefault('_controller') ?? '', "\\");
      return $controller !== SystemController::class . '::systemAdminMenuBlockPage';
    }
    catch (RouteNotFoundException) {
      return TRUE;
    }
  }

  /**
   * Checks whether the menu link tree element is accessible and enabled.
   *
   * Generally, the 'checkAccess' manipulator should run before this manipulator
   * does, so the access objects should be set on all the links, but if it is
   * not, treat the link as accessible for the purpose of adding the overview
   * child link.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $element
   *   The menu link tree element to be checked.
   *
   * @return bool
   *   TRUE if the menu link tree element is enabled and has access allowed.
   */
  protected function isEnabledAndAccessible(MenuLinkTreeElement $element): bool {
    return $element->link->isEnabled() && (!isset($element->access) || $element->access->isAllowed());
  }

  /**
   * Adds "overview" menu tree element as child of a menu tree element.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $element
   *   The menu link tree element to add the overview child element to.
   */
  protected function addOverviewLink(MenuLinkTreeElement $element): void {
    // Copy the menu link for the menu link element to a new menu link
    // definition, except with overrides to make 'Overview' the title, set the
    // parent to the original menu link, and set weight to a low number so that
    // it likely goes to the top.
    $definition = [
      'title' => $this->t('Overview', [
        '@title' => $element->link->getTitle(),
      ]),
      'parent' => $element->link->getPluginId(),
      'provider' => 'navigation',
      'weight' => -1000,
    ] + $element->link->getPluginDefinition();
    $link = new MenuLinkDefault([], $element->link->getPluginId() . '.navigation_overview', $definition, $this->overrides);
    $overview_element = new MenuLinkTreeElement($link, FALSE, $element->depth + 1, $element->inActiveTrail, []);
    $overview_element->access = $element->access ? clone $element->access : NULL;
    $element->subtree[$element->link->getPluginId() . '.navigation_overview'] = $overview_element;
  }

}
