<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\UserData.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\user\UserData as UserDataStorage;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @MigrateDestination(
 *   id = "user_data"
 * )
 */
class UserData extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Builds an user data entity destination.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\user\UserData $user_data
   *   The user data service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, UserDataStorage $user_data) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('user.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $uid = $row->getDestinationProperty('uid');
    $module = $row->getDestinationProperty('module');
    $key = $row->getDestinationProperty('key');
    $this->userData->set($module, $uid, $key, $row->getDestinationProperty('settings'));

    return [$uid, $module, $key];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uid']['type'] = 'integer';
    $ids['module']['type'] = 'string';
    $ids['key']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'uid' => 'The user id.',
      'module' => 'The module name responsible for the settings.',
      'key' => 'The setting key to save under.',
      'settings' => 'The settings to save.',
    ];
  }

}
