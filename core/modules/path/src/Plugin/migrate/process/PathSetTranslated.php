<?php

namespace Drupal\path\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * A process plugin to update the path of a translated node.
 *
 * Available configuration keys:
 * - source: An array of two values, the first being the original path, and the
 *   second being an array of the format [nid, langcode] if a translated node
 *   exists (likely from a migration lookup). Paths not of the format
 *   '/node/<nid>' will pass through unchanged, as will any inputs with invalid
 *   or missing translated nodes.
 *
 * This plugin will return the correct path for the translated node if the above
 * conditions are met, and will return the original path otherwise.
 *
 * Example:
 *   node_translation:
 *   -
 *     plugin: explode
 *     source: source
 *     delimiter: /
 *   -
 *     # If the source path has no slashes return a dummy default value.
 *     plugin: extract
 *     default: 'INVALID_NID'
 *     index:
 *       - 1
 *   -
 *     plugin: migration_lookup
 *     migration: d7_node_translation
 *   _path:
 *     plugin: concat
 *     source:
 *       - constants/slash
 *       - source
 *   path:
 *     plugin: path_set_translated
 *     source:
 *       - '@_path'
 *       - '@node_translation'
 *
 * In the example above, if the node_translation lookup succeeds and the
 * original path is of the format '/node/<original node nid>', then the new path
 * will be set to '/node/<translated node nid>'
 *
 * @MigrateProcessPlugin(
 *   id = "path_set_translated"
 * )
 */
class PathSetTranslated extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException("The input value should be an array.");
    }

    $path = $value[0] ?? '';
    $nid = (is_array($value[1]) && isset($value[1][0])) ? $value[1][0] : FALSE;
    if (preg_match('/^\/node\/\d+$/', $path) && $nid) {
      return '/node/' . $nid;
    }
    return $path;
  }

}
