<?php

namespace Drupal\shortcut;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a storage for shortcut_set entities.
 */
class ShortcutSetStorage extends ConfigEntityStorage implements ShortcutSetStorageInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a ShortcutSetStorageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   The entity info for the entity type.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityTypeInterface $entity_info, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, Connection $connection) {
    parent::__construct($entity_info, $config_factory, $uuid_service, $language_manager, $memory_cache);

    $this->moduleHandler = $module_handler;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAssignedShortcutSets(ShortcutSetInterface $entity) {
    // First, delete any user assignments for this set, so that each of these
    // users will go back to using whatever default set applies.
    $this->connection->delete('shortcut_set_users')
      ->condition('set_name', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function assignUser(ShortcutSetInterface $shortcut_set, $account) {
    $this->connection->merge('shortcut_set_users')
      ->key('uid', $account->id())
      ->fields(['set_name' => $shortcut_set->id()])
      ->execute();
    drupal_static_reset('shortcut_current_displayed_set');
  }

  /**
   * {@inheritdoc}
   */
  public function unassignUser($account) {
    $deleted = $this->connection->delete('shortcut_set_users')
      ->condition('uid', $account->id())
      ->execute();
    return (bool) $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignedToUser($account) {
    $query = $this->connection->select('shortcut_set_users', 'ssu');
    $query->fields('ssu', ['set_name']);
    $query->condition('ssu.uid', $account->id());
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function countAssignedUsers(ShortcutSetInterface $shortcut_set) {
    return Database::getConnection()->query('SELECT COUNT(*) FROM {shortcut_set_users} WHERE set_name = :name', [':name' => $shortcut_set->id()])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSet(AccountInterface $account) {
    // Allow modules to return a default shortcut set name. Since we can only
    // have one, we allow the last module which returns a valid result to take
    // precedence. If no module returns a valid set, fall back on the site-wide
    // default, which is the lowest-numbered shortcut set.
    $suggestions = array_reverse($this->moduleHandler->invokeAll('shortcut_default_set', [$account]));
    $suggestions[] = 'default';
    $shortcut_set = NULL;
    foreach ($suggestions as $name) {
      if ($shortcut_set = $this->load($name)) {
        break;
      }
    }

    return $shortcut_set;
  }

}
