<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the destination roles ID for an array of source roles IDs.
 *
 * The roles_lookup plugin is used to get the destination roles for roles that
 * are assigned to a block. It always uses the 'roles' value on the row as the
 * source value.
 *
 *  Examples
 *
 * @code
 *  process:
 *    roles:
 *      plugin: roles_lookup
 *      migration: d7_user_role
 * @endcode
 *
 * This will get the destination role ID for each role in the 'roles' value on
 * the source row.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('roles_lookup')]
class RolesLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The migration for user role lookup.
   *
   * @var string
   */
  protected $migration;

  /**
   * Constructs a BlockVisibility object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrate_lookup;

    if (isset($configuration['migration'])) {
      $this->migration = $configuration['migration'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.lookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $roles = $row->get('roles');
    $roles_result = [];
    // If the block is assigned to specific roles, add the user_role condition.
    if ($roles) {
      foreach ($roles as $role_id) {
        $lookup_result = $this->migrateLookup->lookup([$this->migration], [$role_id]);
        if ($lookup_result) {
          $roles_result[$role_id] = $lookup_result[0]['id'];
        }
      }
    }
    return $roles_result;
  }

}
