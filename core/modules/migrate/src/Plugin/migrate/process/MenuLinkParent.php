<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin figures out menu link parent plugin IDs.
 *
 * @MigrateProcessPlugin(
 *   id = "menu_link_parent"
 * )
 */
class MenuLinkParent extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
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
  // @codingStandardsIgnoreLine
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $migration_configuration['migration'][] = $migration->id();
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
    if (!$parent_id) {
      // Top level item.
      return '';
    }
    $lookup_result = $this->migrateLookup->lookup($this->migration->id(), [$parent_id]);
    if ($lookup_result) {
      $already_migrated_id = $lookup_result[0]['id'];
    }

    if (!empty($already_migrated_id) && ($link = $this->menuLinkStorage->load($already_migrated_id))) {
      return $link->getPluginId();
    }

    if (isset($value[1])) {
      list($menu_name, $parent_link_path) = $value;

      $links = [];
      if (UrlHelper::isExternal($parent_link_path)) {
        $links = $this->menuLinkStorage->loadByProperties(['link__uri' => $parent_link_path]);
      }
      else {
        $url = Url::fromUserInput("/$parent_link_path");
        if ($url->isRouted()) {
          $links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
        }
      }
      if (count($links) == 1) {
        /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
        $link = reset($links);
        return $link->getPluginId();
      }
    }
    throw new MigrateSkipRowException(sprintf("No parent link found for plid '%d' in menu '%s'.", $parent_id, $value[0]));
  }

}
