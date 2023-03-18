<?php

namespace Drupal\system\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Linkset controller.
 *
 * Provides a menu endpoint.
 *
 * @internal
 *   This class's API is internal and it is not intended for extension.
 */
final class LinksetController extends ControllerBase {

  /**
   * Linkset constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   The menu tree loader service. This is used to load a menu's link
   *   elements so that they can be serialized into a linkset response.
   */
  public function __construct(protected readonly MenuLinkTreeInterface $menuTree) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('menu.link_tree'));
  }

  /**
   * Serve linkset requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   An HTTP request.
   * @param \Drupal\system\MenuInterface $menu
   *   A menu for which to produce a linkset.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   A linkset response.
   */
  public function process(Request $request, MenuInterface $menu) {
    // Load the given menu's tree of elements.
    $tree = $this->loadMenuTree($menu);
    // Get the incoming request URI and parse it so the linkset can use a
    // relative URL for the linkset anchor.
    ['path' => $path, 'query' => $query] = parse_url($request->getUri()) + ['query' => FALSE];
    // Construct a relative URL.
    $anchor = $path . (!empty($query) ? '?' . $query : '');
    $cacheability = CacheableMetadata::createFromObject($menu);
    // Encode the menu tree as links in the application/linkset+json media type
    // and add the machine name of the menu to which they belong.
    $menu_id = $menu->id();
    $links = $this->toLinkTargetObjects($tree, $cacheability);
    foreach ($links as $rel => $target_objects) {
      $links[$rel] = array_map(function (array $target) use ($menu_id) {
        // According to the Linkset specification, this member must be an array
        // since the "machine-name" target attribute is non-standard.
        // See https://tools.ietf.org/html/draft-ietf-httpapi-linkset-08#section-4.2.4.3
        return $target + ['machine-name' => [$menu_id]];
      }, $target_objects);
    }
    $linkset = !empty($tree)
      ? [['anchor' => $anchor] + $links]
      : [];
    $data = ['linkset' => $linkset];
    // Set the response content-type header.
    $headers = ['content-type' => 'application/linkset+json'];
    $response = new CacheableJsonResponse($data, 200, $headers);
    // Attach cacheability metadata to the response.
    $response->addCacheableDependency($cacheability);
    return $response;
  }

  /**
   * Encode a menu tree as link items and capture any cacheability metadata.
   *
   * This method recursively traverses the given menu tree to produce a flat
   * array of link items encoded according the application/linkset+json
   * media type.
   *
   * To preserve hierarchical information, the target attribute contains a
   * `hierarchy` member. Its value is an array containing the position of a link
   * within a particular sub-tree prepended by the positions of its ancestors,
   * and can be used to reconstruct a hierarchical data structure.
   *
   * The reason that a `hierarchy` member is used instead of a `parent` or
   * `children` member is because it is more compact, more suited to the linkset
   * media type, and because it simplifies many menu operations. Specifically:
   *
   * 1. Creating a `parent` member would require each link to have an `id`
   *    in order to have something referenceable by the `parent` member. Reusing
   *    the link plugin IDs would not be viable because it would leak
   *    information about which modules are installed on the site. Therefore,
   *    this ID would have to be invented and would probably end up looking a
   *    lot like the `hierarchy` value. Finally, link IDs would encourage
   *    clients to hardcode the ID instead of using link relation types
   *    appropriately.
   * 2. The linkset media type is not itself hierarchical. This means that
   *    `children` is infeasible without inventing our own Drupal-specific media
   *    type.
   * 3. The `hierarchy` member can be used to efficiently perform tree
   *    operations that would otherwise be more complicated to implement. For
   *    example, by comparing the first X amount of hierarchy levels, you can
   *    find any subtree without writing recursive logic or complicated loops.
   *    Visit the URL below for more examples.
   *
   * The structure of a `hierarchy` value is defined below.
   *
   * A link which is a child of another link will always be prefixed by the
   * exact value of their parent's hierarchy member. For example, if a link /bar
   * is a child of a link /foo and /foo has a hierarchy member with the value
   * ["1"], then the link /bar might have a hierarchy member with the value
   * ["1", "0"]. The link /foo can be said to have depth 1, while the link
   * /bar can be said to have depth 2.
   *
   * Links which have the same parent (or no parent) have their relative order
   * preserved in the final component of the hierarchy value.
   *
   * According to the Linkset specification, each value in the hierarchy array
   * must be a string. See https://tools.ietf.org/html/draft-ietf-httpapi-linkset-08#section-4.2.4.3
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   A tree of menu elements.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   An object to capture any cacheability metadata.
   * @param array $hierarchy_ancestors
   *   (Internal use only) The hierarchy value of the parent element
   *   if $tree is a subtree. Do not pass this value.
   *
   * @return array
   *   An array which can be JSON-encoded to represent the given link tree.
   *
   * @see https://www.drupal.org/project/decoupled_menus/issues/3204132#comment-14439385
   */
  protected function toLinkTargetObjects(array $tree, RefinableCacheableDependencyInterface $cacheability, $hierarchy_ancestors = []): array {
    $links = [];
    // Calling array_values() discards any key names so that $index will be
    // numerical.
    foreach (array_values($tree) as $index => $element) {
      // Extract and preserve the access cacheability metadata.
      $element_access = $element->access;
      assert($element_access instanceof AccessResultInterface);
      $cacheability->addCacheableDependency($element_access);
      // If an element is not accessible, it should not be encoded. Its
      // cacheability should be preserved regardless, which is why that is done
      // outside of this conditional.
      if ($element_access->isAllowed()) {
        // Get and generate the URL of the link's target. This can create
        // cacheability metadata also.
        $url = $element->link->getUrlObject();
        $generated_url = $url->toString(TRUE);
        $cacheability = $cacheability->addCacheableDependency($generated_url);
        // Take the hierarchy value for the current element and append it
        // to the link element parent's hierarchy value. See this method's
        // docblock for more context on why this value is the way it is.
        $hierarchy = $hierarchy_ancestors;
        array_push($hierarchy, strval($index));
        $link_options = $element->link->getOptions();
        $link_attributes = ($link_options['attributes'] ?? []);
        $link_rel = $link_attributes['rel'] ?? 'item';
        // Encode the link.
        $link = [
          'href' => $generated_url->getGeneratedUrl(),
          // @todo should this use the "title*" key if it is internationalized?
          // Follow up issue:
          // https://www.drupal.org/project/decoupled_menus/issues/3280735
          'title' => $element->link->getTitle(),
          'hierarchy' => $hierarchy,
        ];
        $this->processCustomLinkAttributes($link, $link_attributes);
        $links[$link_rel][] = $link;
        // Recurse into the element's subtree.
        if (!empty($element->subtree)) {
          // Recursion!
          $links = array_merge_recursive($links, $this->toLinkTargetObjects($element->subtree, $cacheability, $hierarchy));
        }
      }
    }

    return $links;
  }

  /**
   * Process custom link parameters.
   *
   * Since the values for attributes are dynamic and we can't
   * guarantee that they adhere to the linkset specification,
   * we do some custom processing as follows,
   * 1. Transform all of them into an array if
   *    they are not already an array.
   * 2. Transform all non-string values into strings
   *    (e.g. ["42"] instead of [42])
   * 3. Ignore (for now) any keys that are already specified.
   *    Namely: hreflang, media, type, title, and title*.
   * 4. Ensure that custom names do not contain an
   *    asterisk and ignore them if they do.
   * 5. These attributes require special handling. For instance,
   *    these parameters must be strings instead of an array of strings.
   *
   * NOTE: Values which are not object/array are cast to string.
   *
   * @param array $link
   *   Link structure.
   * @param array $attributes
   *   Attributes available for the link.
   */
  private function processCustomLinkAttributes(array &$link, array $attributes = []) {
    $attribute_keys_to_ignore = [
      'hreflang',
      'media',
      'type',
      'title',
      'title*',
    ];

    foreach ($attributes as $key => $value) {
      if (in_array($key, $attribute_keys_to_ignore, TRUE)) {
        continue;
      }
      // Skip the attribute key if it has an asterisk (*).
      if (str_contains($key, '*')) {
        continue;
      }
      // Skip the value if it is an object.
      if (is_object($value)) {
        continue;
      }
      // See https://datatracker.ietf.org/doc/html/draft-ietf-httpapi-linkset-03#section-4.2.4.3
      // Values for custom attributes must follow these rules,
      // - Values MUST be array.
      // - Each item in the array MUST be a string.
      if (is_array($value)) {
        $link[$key] = [];
        foreach ($value as $val) {
          if (is_object($val) || is_array($val)) {
            continue;
          }
          $link[$key][] = (string) $val;
        }
      }
      else {
        $link[$key] = [(string) $value];
      }
    }
  }

  /**
   * Loads a menu tree.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   A menu for which a tree should be loaded.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   A menu link tree.
   */
  protected function loadMenuTree(MenuInterface $menu) : array {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $parameters->setMinDepth(0);
    $tree = $this->menuTree->load($menu->id(), $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    return $this->menuTree->transform($tree, $manipulators);
  }

}
