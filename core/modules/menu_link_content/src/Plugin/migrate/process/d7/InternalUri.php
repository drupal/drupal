<?php

namespace Drupal\menu_link_content\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process a path into an 'internal:' URI.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_internal_uri"
 * )
 */
class InternalUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($path) = $value;
    $path = ltrim($path, '/');

    if (parse_url($path, PHP_URL_SCHEME) == NULL) {
      // If $path is the node page (i.e. node/[nid]) then return entity path.
      if (preg_match('/^node\/\d+$/', $path)) {
        // "entity: URI"s enable the menu link to appear in the Menu Settings
        // section on the node edit page. Other entities (e.g. taxonomy terms,
        // users) do not have the Menu Settings section.
        return 'entity:' . $path;
      }
      elseif ($path == '<front>') {
        return 'internal:/';
      }
      else {
        return 'internal:/' . $path;
      }
    }
    return $path;
  }

}
