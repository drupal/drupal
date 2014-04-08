<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\menu_link\MenuTreeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "system_menu_block",
 *   admin_label = @Translation("Menu"),
 *   category = @Translation("Menus"),
 *   derivative = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class SystemMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu tree.
   *
   * @var \Drupal\menu_link\MenuTreeInterface
   */
  protected $menuTree;

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\menu_link\MenuTreeInterface $menu_tree
   *   The menu tree.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuTreeInterface $menu_tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu = $this->getDerivativeId();
    return $this->menuTree->renderMenu($menu);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Modify the default max age for menu blocks: modifications made to menus,
    // menu links and menu blocks will automatically invalidate corresponding
    // cache tags, therefore allowing us to cache menu blocks forever. This is
    // only not the case if there are user-specific or dynamic alterations (e.g.
    // hook_node_access()), but in that:
    // 1) it is possible to set a different max age for individual blocks, since
    //    this is just the default value.
    // 2) modules can modify caching by implementing hook_block_view_alter()
    return array('cache' => array('max_age' => \Drupal\Core\Cache\Cache::PERMANENT));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    // Add a key for the active menu trail.
    $menu = $this->getDerivativeId();
    $active_trail = $this->menuTree->getActiveTrailIds($menu);
    $active_trail_key = 'trail.' . implode('|', $active_trail);
    return array_merge(parent::getCacheKeys(), array($active_trail_key));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the menu block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the menu is changed, this
    // menu block must also be re-rendered for that user, because maybe a menu
    // link that is accessible for that user has been added.
    $tags = array('menu' => array($this->getDerivativeId()));
    return NestedArray::mergeDeep(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // Menu blocks must be cached per role: different roles may have access to
    // different menu links.
    return array('cache_context.user.roles');
  }

}
