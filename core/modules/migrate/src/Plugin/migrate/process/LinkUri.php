<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Generates an internal URI from the source value.
 *
 * Converts the source path value to an 'entity:', 'internal:' or 'base:' URI.
 *
 * Available configuration keys:
 * - source: A source path to be converted into an URI.
 * - validate_route: (optional) Whether the plugin should validate that the URI
 *   derived from the source link path has a valid Drupal route.
 *   - TRUE: Throw a MigrateException if the resulting URI is not routed. This
 *     value is the default.
 *   - FALSE: Return the URI for the unrouted path.
 *
 * Examples:
 *
 * @code
 * process:
 *   link/uri:
 *     plugin: link_uri
 *     validate_route: false
 *     source: link_path
 * @endcode
 *
 * This will set the uri property of link to the internal notation of link_path
 * without validating if the resulting URI is valid. For example, if the
 * 'link_path' property is 'node/12', the uri property value of link will be
 * 'entity:node/12'.
 */
#[MigrateProcess('link_uri')]
class LinkUri extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    $configuration += [
      'validate_route' => TRUE,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $path = ltrim($value, '/');

    if (parse_url($path, PHP_URL_SCHEME) === NULL) {
      if ($path === '<front>') {
        $path = '';
      }
      elseif (empty($path) || in_array($path, ['<nolink>', '<none>'], TRUE)) {
        return 'route:<nolink>';
      }
      elseif ($path === '<button>') {
        return 'route:<button>';
      }
      $path = 'internal:/' . $path;

      // Convert entity URIs to the entity scheme, if the path matches a route
      // of the form "entity.$entity_type_id.canonical".
      // @see \Drupal\Core\Url::fromEntityUri()
      $url = Url::fromUri($path);
      if ($url->isRouted()) {
        if (preg_match('/^entity\.(.*)\.canonical$/', $url->getRouteName(), $matches)) {
          $entity_type_id = $matches[1];
          if (isset($url->getRouteParameters()[$entity_type_id], $this->entityTypeManager->getDefinitions()[$entity_type_id])) {
            return "entity:$entity_type_id/" . $url->getRouteParameters()[$entity_type_id];
          }
        }
      }
      else {
        // If the URL is not routed, we might want to get something back to do
        // other processing. If this is the case, the "validate_route"
        // configuration option can be set to FALSE to return the URI.
        if ($this->configuration['validate_route']) {
          throw new MigrateException(sprintf('The path "%s" failed validation.', $path));
        }
        return $url->getUri();
      }
    }
    return $path;
  }

}
