<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Menu\MyAccountMenuLink.
 */

namespace Drupal\user\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Provides custom logic for the user.page menu link.
 */
class MyAccountMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    // The path 'user' must be accessible for anonymous users, but only visible
    // for authenticated users. Authenticated users should see "My account", but
    // anonymous users should not see it at all.
    // @todo Re-write this as a link to entity.user.canonical with dynamic route parameters
    //   to affect access since hidden should not be dynamic.
    //   https://www.drupal.org/node/2306991
    return $this->pluginDefinition['enabled'] && !\Drupal::currentUser()->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return FALSE;
  }

}
