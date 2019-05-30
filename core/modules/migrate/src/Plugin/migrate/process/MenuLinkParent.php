<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateProcessInterface;
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
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateProcessInterface $migration_plugin, MenuLinkManagerInterface $menu_link_manager, EntityStorageInterface $menu_link_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPlugin = $migration_plugin;
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
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration),
      $container->get('plugin.manager.menu.link'),
      $container->get('entity_type.manager')->getStorage('menu_link_content')
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
    try {
      $already_migrated_id = $this
        ->migrationPlugin
        ->transform($parent_id, $migrate_executable, $row, $destination_property);
      if ($already_migrated_id && ($link = $this->menuLinkStorage->load($already_migrated_id))) {
        return $link->getPluginId();
      }
    }
    catch (MigrateSkipRowException $e) {

    }
    if (isset($value[1])) {
      list($menu_name, $parent_link_path) = $value;
      $url = Url::fromUserInput("/$parent_link_path");
      if ($url->isRouted()) {
        $links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
        if (count($links) == 1) {
          /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
          $link = reset($links);
          return $link->getPluginId();
        }
      }
    }
    throw new MigrateSkipRowException(sprintf("No parent link found for plid '%d' in menu '%s'.", $parent_id, $value[0]));
  }

}
