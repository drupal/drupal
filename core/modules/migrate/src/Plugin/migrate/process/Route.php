<?php
/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Route.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "route"
 * )
 */
class Route extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, PathValidatorInterface $pathValidator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->pathValidator = $pathValidator;
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
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Set the destination route information based on the source link_path.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($link_path, $options) = $value;
    $extracted = $this->pathValidator->getUrlIfValidWithoutAccessCheck($link_path);
    $route = array();

    if ($extracted) {
      if ($extracted->isExternal()) {
        $route['route_name'] = null;
        $route['route_parameters'] = array();
        $route['options'] = $options;
        $route['url'] = $extracted->getUri();
      }
      else {
        $route['route_name'] = $extracted->getRouteName();
        $route['route_parameters'] = $extracted->getRouteParameters();
        $route['options'] = $extracted->getOptions();

        if (isset($options['query'])) {
          // If the querystring is stored as a string (as in D6), convert it
          // into an array.
          if (is_string($options['query'])) {
            parse_str($options['query'], $old_query);
          }
          else {
            $old_query = $options['query'];
          }
          $options['query'] = $route['options']['query'] + $old_query;
          unset($route['options']['query']);
        }
        $route['options'] = $route['options'] + $options;
        $route['url'] = null;
      }
    }

    return $route;
  }

}

