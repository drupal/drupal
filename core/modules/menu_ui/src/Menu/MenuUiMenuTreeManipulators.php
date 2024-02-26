<?php

namespace Drupal\menu_ui\Menu;

use Drupal\Core\Access\AccessResult;

/**
 * Provides menu tree manipulators to be used when managing menu links.
 */
class MenuUiMenuTreeManipulators {

  /**
   * Grants access to a menu tree when used in the menu management form.
   *
   * This manipulator allows access to menu links with inaccessible routes.
   *
   * Example use cases:
   * - A login menu link, using the `user.login` route, is not accessible to a
   *   logged-in user, but the site builder still needs to configure the menu
   *   link.
   * - A site builder wants to create a menu item for a Views page that has not
   *   been created. In this case, there is no access to the route because it
   *   does not exist.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   *
   * @internal
   * This menu tree manipulator is intended for use only in the context of
   * MenuForm because the user permissions to administer links is already
   * checked. Don't use this manipulator in other places.
   *
   * @see \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators::checkAccess()
   * @see \Drupal\menu_ui\MenuForm
   */
  public function checkAccess(array $tree): array {
    foreach ($tree as $element) {
      $element->access = AccessResult::allowed();
      if ($element->subtree) {
        $element->subtree = $this->checkAccess($element->subtree);
      }
    }
    return $tree;
  }

}
