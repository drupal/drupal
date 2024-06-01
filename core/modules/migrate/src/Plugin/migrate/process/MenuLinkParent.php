<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore plid

/**
 * Determines the parent of a menu link.
 *
 * Menu link item belongs to a menu such as 'Navigation' or 'Administration'.
 * Menu link item also has a parent item unless it is the root element of the
 * menu.
 *
 * This process plugin determines the parent item of a menu link. If the parent
 * item can't be determined by ID, we try to determine it by a combination of
 * menu name and parent link path.
 *
 * The source is an array of three values:
 * - parent_id: The numeric ID of the parent menu link, or 0 if the link is the
 *   root element of the menu.
 * - menu_name: The name of the menu the menu link item belongs to.
 * - parent_link_path: The Drupal path or external URL the parent of this menu
 *   link points to.
 *
 * Example:
 *
 * @code
 * process:
 *   parent:
 *     plugin: menu_link_parent
 *     source:
 *       - plid
 *       - menu_name
 *       - parent_link_path
 * @endcode
 * In this example, first look for a menu link that had an ID defined by 'plid'
 * in the source (e.g., '20'). If that fails, try to determine the parent by a
 * combination of a menu name (e.g., 'management') and a parent menu link path
 * (e.g., 'admin/structure').
 *
 * @see https://www.drupal.org/docs/8/api/menu-api
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('menu_link_parent')]
class MenuLinkParent extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The currently running migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The menu link entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * Constructs a MenuLinkParent object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_link_storage
   *   The menu link storage object.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The currently running migration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrate_lookup, MenuLinkManagerInterface $menu_link_manager, EntityStorageInterface $menu_link_storage, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->migration = $migration;
    $this->migrateLookup = $migrate_lookup;
    $this->menuLinkManager = $menu_link_manager;
    $this->menuLinkStorage = $menu_link_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.lookup'),
      $container->get('plugin.manager.menu.link'),
      $container->get('entity_type.manager')->getStorage('menu_link_content'),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   *
   * Find the parent link GUID.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $parent_id = array_shift($value);

    // Handle root elements of a menu.
    if (!$parent_id) {
      return '';
    }

    $lookup_result = $this->migrateLookup->lookup($this->migration->id(), [$parent_id]);
    if ($lookup_result) {
      $already_migrated_id = $lookup_result[0]['id'];
    }

    if (!empty($already_migrated_id) && ($link = $this->menuLinkStorage->load($already_migrated_id))) {
      return $link->getPluginId();
    }

    // Parent could not be determined by ID, so we try to determine by the
    // combination of the menu name and parent link path.
    if (isset($value[1])) {
      [$menu_name, $parent_link_path] = $value;

      // If the parent link path is external, URL will be useless because the
      // link will definitely not correspond to a Drupal route.
      if (UrlHelper::isExternal($parent_link_path)) {
        $links = $this->menuLinkStorage->loadByProperties([
          'menu_name' => $menu_name,
          'link.uri' => $parent_link_path,
        ]);
      }
      else {
        $url = Url::fromUserInput('/' . ltrim($parent_link_path, '/'));
        if ($url->isRouted()) {
          $links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
        }
      }
      if (!empty($links)) {
        return reset($links)->getPluginId();
      }
    }

    // Parent could not be determined.
    throw new MigrateSkipRowException(sprintf("No parent link found for plid '%d' in menu '%s'.", $parent_id, $value[0]));
  }

}
