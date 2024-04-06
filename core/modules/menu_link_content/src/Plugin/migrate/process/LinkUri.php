<?php

namespace Drupal\menu_link_content\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  /**
   * The entity type manager, used to fetch entity link templates.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LinkUri object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager, used to fetch entity link templates.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    $configuration += [
      'validate_route' => TRUE,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $path = ltrim($value, '/');

    if (parse_url($path, PHP_URL_SCHEME) === NULL) {
      if ($path == '<front>') {
        $path = '';
      }
      elseif ($path == '<nolink>') {
        return 'route:<nolink>';
      }
      $path = 'internal:/' . $path;

      // Convert entity URIs to the entity scheme, if the path matches a route
      // of the form "entity.$entity_type_id.canonical".
      // @see \Drupal\Core\Url::fromEntityUri()
      $url = Url::fromUri($path);
      if ($url->isRouted()) {
        $route_name = $url->getRouteName();
        foreach (array_keys($this->entityTypeManager->getDefinitions()) as $entity_type_id) {
          if ($route_name == "entity.$entity_type_id.canonical" && isset($url->getRouteParameters()[$entity_type_id])) {
            return "entity:$entity_type_id/" . $url->getRouteParameters()[$entity_type_id];
          }
        }
      }
      else {
        // If the URL is not routed, we might want to get something back to do
        // other processing. If this is the case, the "validate_route"
        // configuration option can be set to FALSE to return the URI.
        if (!$this->configuration['validate_route']) {
          return $url->getUri();
        }
        else {
          throw new MigrateException(sprintf('The path "%s" failed validation.', $path));
        }
      }
    }
    return $path;
  }

}
