<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "block_visibility"
 * )
 */
class BlockVisibility extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The migration process plugin, configured for lookups in the d6_user_role
   * and d7_user_role migrations.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * Whether or not to skip blocks that use PHP for visibility. Only applies
   * if the PHP module is not enabled.
   *
   * @var bool
   */
  protected $skipPHP = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->migrationPlugin = $migration_plugin;

    if (isset($configuration['skip_php'])) {
      $this->skipPHP = $configuration['skip_php'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $migration_configuration = array(
      'migration' => array(
        'd6_user_role',
        'd7_user_role',
      ),
    );
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($old_visibility, $pages, $roles) = $value;

    $visibility = array();

    // If the block is assigned to specific roles, add the user_role condition.
    if ($roles) {
      $visibility['user_role'] = array(
        'id' => 'user_role',
        'roles' => array(),
        'context_mapping' => array(
          'user' => '@user.current_user_context:current_user',
        ),
        'negate' => FALSE,
      );

      foreach ($roles as $key => $role_id) {
        $roles[$key] = $this->migrationPlugin->transform($role_id, $migrate_executable, $row, $destination_property);
      }
      $visibility['user_role']['roles'] = array_combine($roles, $roles);
    }

    if ($pages) {
      // 2 == BLOCK_VISIBILITY_PHP in Drupal 6 and 7.
      if ($old_visibility == 2) {
        // If the PHP module is present, migrate the visibility code unaltered.
        if ($this->moduleHandler->moduleExists('php')) {
          $visibility['php'] = array(
            'id' => 'php',
            // PHP code visibility could not be negated in Drupal 6 or 7.
            'negate' => FALSE,
            'php' => $pages,
          );
        }
        // Skip the row if we're configured to. If not, we don't need to do
        // anything else -- the block will simply have no PHP or request_path
        // visibility configuration.
        elseif ($this->skipPHP) {
          throw new MigrateSkipRowException();
        }
      }
      else {
        $paths = preg_split("(\r\n?|\n)", $pages);
        foreach ($paths as $key => $path) {
          $paths[$key] = $path === '<front>' ? $path : '/' . ltrim($path, '/');
        }
        $visibility['request_path'] = array(
          'id' => 'request_path',
          'negate' => !$old_visibility,
          'pages' => implode("\n", $paths),
        );
      }
    }

    return $visibility;
  }

}
