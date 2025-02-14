<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Determines the bundle for a field.
 *
 * The field bundle process plugin is used to get the destination bundle name
 * for a field. This is necessary because the bundle names used for comments in
 * legacy versions of Drupal are no longer used.
 *
 * Available configuration keys:
 * - source: The input value - must be an array.
 *
 * Examples:
 *
 * @code
 * process:
 *   bundle:
 *     plugin: field_bundle
 *     source
 *       - entity_type
 *       - bundle
 * @endcode
 *
 * If 'bundle' is 'article' and 'entity_type' is node then 'article' is
 * returned.
 *
 * @code
 * process:
 *   bundle:
 *     plugin: field_bundle
 *     source
 *       - entity_type
 *       - bundle
 * @endcode
 *
 * If 'bundle' is 'comment_node_a_thirty_two_character_name' and 'entity_type'
 * is blog then a lookup is performed on the comment type migration so that the
 * migrated bundle name for 'comment_node_a_thirty_two_character_name' is
 * returned. That name will be truncated to 'comment_node_a_thirty_two_char'.
 *
 * @see core/modules/comment/migrations/d7_comment_type.yml
 * @see core/modules/field/migrations/d7_field_instance.yml
 */
#[MigrateProcess('field_bundle')]
class FieldBundle extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a ProcessField plugin.
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
    [$entity_type, $bundle] = $value;
    $lookup_result = NULL;
    // For comment entity types get the destination bundle from the
    // d7_comment_type migration, if it exists.
    if ($entity_type === 'comment' && $bundle != 'comment_forum') {
      $lookup_result = $row->get('@_comment_type');
      // Legacy generated migrations will not have the destination property
      // '_comment_type'.
      if (!$row->hasDestinationProperty('_comment_type')) {
        $value = str_replace('comment_node_', '', $bundle);
        $migration = 'd7_comment_type';
        $lookup_result = $this->migrateLookup->lookup($migration, [$value]);
        $lookup_result = empty($lookup_result) ? NULL : reset($lookup_result[0]);
      }
    }
    return $lookup_result ?: $bundle;
  }

}
