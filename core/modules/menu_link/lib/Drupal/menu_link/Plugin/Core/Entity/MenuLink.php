<?php

/**
 * @file
 * Contains \Drupal\menu_link\Plugin\Core\Entity\MenuLink.
 */

namespace Drupal\menu_link\Plugin\Core\Entity;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;

/**
 * Defines the menu link entity class.
 *
 * @Plugin(
 *   id = "menu_link",
 *   label = @Translation("Menu link"),
 *   module = "menu_link",
 *   controller_class = "Drupal\menu_link\MenuLinkStorageController",
 *   form_controller_class = {
 *     "default" = "Drupal\menu_link\MenuLinkFormController"
 *   },
 *   static_cache = FALSE,
 *   base_table = "menu_links",
 *   uri_callback = "menu_link_uri",
 *   entity_keys = {
 *     "id" = "mlid",
 *     "label" = "link_title",
 *     "uuid" = "uuid"
 *   },
 *   bundles = {
 *     "menu_link" = {
 *       "label" = "Menu link",
 *     }
 *   }
 * )
 */
class MenuLink extends Entity implements \ArrayAccess, ContentEntityInterface {

  /**
   * The link's menu name.
   *
   * @var string
   */
  public $menu_name = 'tools';

  /**
   * The menu link ID.
   *
   * @var int
   */
  public $mlid;

  /**
   * The menu link UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The parent link ID.
   *
   * @var int
   */
  public $plid;

  /**
   * The Drupal path or external path this link points to.
   *
   * @var string
   */
  public $link_path;

  /**
   * For links corresponding to a Drupal path (external = 0), this connects the
   * link to a {menu_router}.path for joins.
   *
   * @var string
   */
  public $router_path;

  /**
   * The entity label.
   *
   * @var string
   */
  public $link_title = '';

  /**
   * A serialized array of options to be passed to the url() or l() function,
   * such as a query string or HTML attributes.
   *
   * @var array
   */
  public $options = array();

  /**
   * The name of the module that generated this link.
   *
   * @var string
   */
  public $module = 'menu';

  /**
   * A flag for whether the link should be rendered in menus.
   *
   * @var int
   */
  public $hidden = 0;

  /**
   * A flag to indicate if the link points to a full URL starting with a
   * protocol, like http:// (1 = external, 0 = internal).
   *
   * @var int
   */
  public $external;

  /**
   * Flag indicating whether any links have this link as a parent.
   *
   * @var int
   */
  public $has_children = 0;

  /**
   * Flag for whether this link should be rendered as expanded in menus.
   * Expanded links always have their child links displayed, instead of only
   * when the link is in the active trail.
   *
   * @var int
   */
  public $expanded = 0;

  /**
   * Link weight among links in the same menu at the same depth.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The depth relative to the top level. A link with plid == 0 will have
   * depth == 1.
   *
   * @var int
   */
  public $depth;

  /**
   * A flag to indicate that the user has manually created or edited the link.
   *
   * @var int
   */
  public $customized = 0;

  /**
   * The first entity ID in the materialized path.
   *
   * @var int
   *
   * @todo Investigate whether the p1, p2, .. pX properties can be moved to a
   * single array property.
   */
  public $p1;

  /**
   * The second entity ID in the materialized path.
   *
   * @var int
   */
  public $p2;

  /**
   * The third entity ID in the materialized path.
   *
   * @var int
   */
  public $p3;

  /**
   * The fourth entity ID in the materialized path.
   *
   * @var int
   */
  public $p4;

  /**
   * The fifth entity ID in the materialized path.
   *
   * @var int
   */
  public $p5;

  /**
   * The sixth entity ID in the materialized path.
   *
   * @var int
   */
  public $p6;

  /**
   * The seventh entity ID in the materialized path.
   *
   * @var int
   */
  public $p7;

  /**
   * The eighth entity ID in the materialized path.
   *
   * @var int
   */
  public $p8;

  /**
   * The ninth entity ID in the materialized path.
   *
   * @var int
   */
  public $p9;

  /**
   * The menu link modification timestamp.
   *
   * @var int
   */
  public $updated = 0;

  /**
   * Overrides Entity::id().
   */
  public function id() {
    return $this->mlid;
  }

  /**
   * Overrides Entity::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    $duplicate->plid = NULL;
    return $duplicate;
  }

  /**
   * Resets a system-defined menu link.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A menu link entity.
   */
  public function reset() {
    // To reset the link to its original values, we need to retrieve its
    // definition from hook_menu(). Otherwise, for example, the link's menu
    // would not be reset, because properties like the original 'menu_name' are
    // not stored anywhere else. Since resetting a link happens rarely and this
    // is a one-time operation, retrieving the full menu router does no harm.
    $menu = menu_get_router();
    $router_item = $menu[$this->router_path];
    $new_link = self::buildFromRouterItem($router_item);
    // Merge existing menu link's ID and 'has_children' property.
    foreach (array('mlid', 'has_children') as $key) {
      $new_link->{$key} = $this->{$key};
    }
    $new_link->save();
    return $new_link;
  }

  /**
   * Builds a menu link entity from a router item.
   *
   * @param array $item
   *   A menu router item.
   *
   * @return MenuLink
   *   A menu link entity.
   */
  public static function buildFromRouterItem(array $item) {
    // Suggested items are disabled by default.
    if ($item['type'] == MENU_SUGGESTED_ITEM) {
      $item['hidden'] = 1;
    }
    // Hide all items that are not visible in the tree.
    elseif (!($item['type'] & MENU_VISIBLE_IN_TREE)) {
      $item['hidden'] = -1;
    }
    // Note, we set this as 'system', so that we can be sure to distinguish all
    // the menu links generated automatically from entries in {menu_router}.
    $item['module'] = 'system';
    $item += array(
      'link_title' => $item['title'],
      'link_path' => $item['path'],
      'options' => empty($item['description']) ? array() : array('attributes' => array('title' => $item['description'])),
    );
    return drupal_container()->get('plugin.manager.entity')
      ->getStorageController('menu_link')->create($item);
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return isset($this->{$offset});
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function &offsetGet($offset) {
    return $this->{$offset};
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    $this->{$offset} = $value;
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->{$offset});
  }
}
