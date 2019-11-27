<?php

namespace Drupal\toolbar\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\toolbar\Ajax\SetSubtreesCommand;

/**
 * Defines a controller for the toolbar module.
 */
class ToolbarController extends ControllerBase implements TrustedCallbackInterface {

  /**
   * Returns an AJAX response to render the toolbar subtrees.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function subtreesAjax() {
    list($subtrees, $cacheability) = toolbar_get_rendered_subtrees();
    $response = new AjaxResponse();
    $response->addCommand(new SetSubtreesCommand($subtrees));

    // The Expires HTTP header is the heart of the client-side HTTP caching. The
    // additional server-side page cache only takes effect when the client
    // accesses the callback URL again (e.g., after clearing the browser cache
    // or when force-reloading a Drupal page).
    $max_age = 365 * 24 * 60 * 60;
    $response->setPrivate();
    $response->setMaxAge($max_age);

    $expires = new \DateTime();
    $expires->setTimestamp(REQUEST_TIME + $max_age);
    $response->setExpires($expires);

    return $response;
  }

  /**
   * Checks access for the subtree controller.
   *
   * @param string $hash
   *   The hash of the toolbar subtrees.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkSubTreeAccess($hash) {
    $expected_hash = _toolbar_get_subtrees_hash()[0];
    return AccessResult::allowedIf($this->currentUser()->hasPermission('access toolbar') && hash_equals($expected_hash, $hash))->cachePerPermissions();
  }

  /**
   * Renders the toolbar's administration tray.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array.
   *
   * @see \Drupal\Core\Render\RendererInterface::render()
   */
  public static function preRenderAdministrationTray(array $element) {
    $menu_tree = \Drupal::service('toolbar.menu_tree');
    // Load the administrative menu. The first level is the "Administration"
    // link. In order to load the children of that link, start and end on the
    // second level.
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth(2)->setMaxDepth(2)->onlyEnabledLinks();
    // @todo Make the menu configurable in https://www.drupal.org/node/1869638.
    $tree = $menu_tree->load('admin', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'toolbar_menu_navigation_links'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $element['administration_menu'] = $menu_tree->build($tree);
    return $element;
  }

  /**
   * #pre_render callback for toolbar_get_rendered_subtrees().
   *
   * @internal
   */
  public static function preRenderGetRenderedSubtrees(array $data) {
    $menu_tree = \Drupal::service('toolbar.menu_tree');
    // Load the administration menu. The first level is the "Administration"
    // link. In order to load the children of that link and the subsequent two
    // levels, start at the second level and end at the fourth.
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth(2)->setMaxDepth(4)->onlyEnabledLinks();
    // @todo Make the menu configurable in https://www.drupal.org/node/1869638.
    $tree = $menu_tree->load('admin', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'toolbar_menu_navigation_links'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $subtrees = [];
    // Calculated the combined cacheability of all subtrees.
    $cacheability = new CacheableMetadata();
    foreach ($tree as $element) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($element->subtree) {
        $subtree = $menu_tree->build($element->subtree);
        $output = \Drupal::service('renderer')->renderPlain($subtree);
        $cacheability = $cacheability->merge(CacheableMetadata::createFromRenderArray($subtree));
      }
      else {
        $output = '';
      }
      // Many routes have dots as route name, while some special ones like
      // <front> have <> characters in them.
      $url = $link->getUrlObject();
      $id = str_replace(['.', '<', '>'], ['-', '', ''], $url->isRouted() ? $url->getRouteName() : $url->getUri());

      $subtrees[$id] = $output;
    }

    // Store the subtrees, along with the cacheability metadata.
    $cacheability->applyTo($data);
    $data['#subtrees'] = $subtrees;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderAdministrationTray', 'preRenderGetRenderedSubtrees'];
  }

}
