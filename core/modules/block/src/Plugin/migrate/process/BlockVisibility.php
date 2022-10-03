<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
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
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Whether or not to skip blocks that use PHP for visibility.
   *
   * Only applies if the PHP module is not enabled.
   *
   * @var bool
   */
  protected $skipPHP = FALSE;

  /**
   * Constructs a BlockVisibility object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->migrateLookup = $migrate_lookup;

    if (isset($configuration['skip_php'])) {
      $this->skipPHP = $configuration['skip_php'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('migrate.lookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$old_visibility, $pages, $roles] = $value;

    $visibility = [];

    // If the block is assigned to specific roles, add the user_role condition.
    if ($roles) {
      $visibility['user_role'] = [
        'id' => 'user_role',
        'roles' => [],
        'context_mapping' => [
          'user' => '@user.current_user_context:current_user',
        ],
        'negate' => FALSE,
      ];

      foreach ($roles as $key => $role_id) {
        $lookup_result = $this->migrateLookup->lookup(['d6_user_role', 'd7_user_role'], [$role_id]);
        if ($lookup_result) {
          $roles[$key] = $lookup_result[0]['id'];
        }
      }
      $visibility['user_role']['roles'] = array_combine($roles, $roles);
    }

    if ($pages) {
      // 2 == BLOCK_VISIBILITY_PHP in Drupal 6 and 7.
      if ($old_visibility == 2) {
        // If the PHP module is present, migrate the visibility code unaltered.
        if ($this->moduleHandler->moduleExists('php')) {
          $visibility['php'] = [
            'id' => 'php',
            // PHP code visibility could not be negated in Drupal 6 or 7.
            'negate' => FALSE,
            'php' => $pages,
          ];
        }
        // Skip the row if we're configured to. If not, we don't need to do
        // anything else -- the block will simply have no PHP or request_path
        // visibility configuration.
        elseif ($this->skipPHP) {
          throw new MigrateSkipRowException(sprintf("The block with bid '%d' from module '%s' will have no PHP or request_path visibility configuration.", $row->getSourceProperty('bid'), $row->getSourceProperty('module')));
        }
      }
      else {
        $paths = preg_split("(\r\n?|\n)", $pages);
        foreach ($paths as $key => $path) {
          $paths[$key] = $path === '<front>' ? $path : '/' . ltrim($path, '/');
        }
        $visibility['request_path'] = [
          'id' => 'request_path',
          'negate' => !$old_visibility,
          'pages' => implode("\n", $paths),
        ];
      }
    }

    return $visibility;
  }

}
