<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\CckLink.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\process\Route;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_cck_link"
 * )
 */
class CckLink extends Route implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($url, $title, $attributes) = $value;

    // Drupal 6 link attributes are double serialized.
    $attributes = unserialize(unserialize($attributes));
    $route_plugin_value = [$url, []];
    $route = parent::transform($route_plugin_value, $migrate_executable, $row, $destination_property);

    // Massage the values into the correct form for the link.
    $route['options']['attributes'] = $attributes;
    $route['title'] = $title;
    return $route;
  }

}
