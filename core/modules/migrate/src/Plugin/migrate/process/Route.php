<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Sets the destination route information based on the source link_path.
 *
 * The source value is an array of two values:
 * - link_path: The path or URL of the route.
 * - options: An array of URL options, e.g. query string, attributes, etc.
 *
 * Example:
 *
 * @code
 * process:
 *   new_route_field:
 *     plugin: route
 *     source:
 *       - 'https://www.drupal.org'
 *       -
 *         attributes:
 *           title: Drupal
 * @endcode
 *
 * This will set new_route_field to be a route with the URL
 * "https://www.drupal.org" and title attribute "Drupal".
 *
 * Example:
 *
 * @code
 * process:
 *   another_route_field:
 *     plugin: route
 *     source:
 *       - 'user/login'
 *       -
 *         query:
 *           destination: 'node/1'
 * @endcode
 *
 * This will set another_route_field to be a route to the user login page
 * (user/login) with a query string of "destination=node/1".
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "route"
 * )
 */
class Route extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, PathValidatorInterface $path_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->pathValidator = $path_validator;
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
    if (is_string($value)) {
      $link_path = $value;
      $options = [];
    }
    else {
      [$link_path, $options] = $value;
    }

    $extracted = $this->pathValidator->getUrlIfValidWithoutAccessCheck($link_path);
    $route = [];

    if ($extracted) {
      if ($extracted->isExternal()) {
        $route['route_name'] = NULL;
        $route['route_parameters'] = [];
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
        $route['url'] = NULL;
      }
    }

    return $route;
  }

}
