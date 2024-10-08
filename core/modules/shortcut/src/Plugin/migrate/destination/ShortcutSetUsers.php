<?php

namespace Drupal\shortcut\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\shortcut\ShortcutSetStorageInterface;
use Drupal\user\Entity\User;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Migration destination for shortcut_set_users.
 */
#[MigrateDestination('shortcut_set_users')]
class ShortcutSetUsers extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The shortcut set storage handler.
   *
   * @var \Drupal\shortcut\ShortcutSetStorageInterface
   */
  protected $shortcutSetStorage;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\shortcut\ShortcutSetStorageInterface $shortcut_set_storage
   *   The shortcut_set entity storage handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ShortcutSetStorageInterface $shortcut_set_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->shortcutSetStorage = $shortcut_set_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage('shortcut_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'set_name' => [
        'type' => 'string',
      ],
      'uid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uid' => 'The users.uid for this set.',
      'source' => 'The shortcut_set.set_name that will be displayed for this user.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    /** @var \Drupal\shortcut\ShortcutSetInterface $set */
    $set = $this->shortcutSetStorage->load($row->getDestinationProperty('set_name'));
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load($row->getDestinationProperty('uid'));
    $this->shortcutSetStorage->assignUser($set, $account);

    return [$set->id(), $account->id()];
  }

}
