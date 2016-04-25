<?php

namespace Drupal\tour\Plugin\HelpSection;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the tours list section for the help page.
 *
 * @HelpSection(
 *   id = "tour",
 *   title = @Translation("Tours"),
 *   description = @Translation("Tours guide you through workflows or explain concepts on various user interface pages. The tours with links in this list are on user interface landing pages; the tours without links will show on individual pages (such as when editing a View using the Views UI module). Available tours:"),
 *   permission = "access tour"
 * )
 */
class TourHelpSection extends HelpSectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TourHelpSection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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
  public function getCacheMaxAge() {
    // The calculation of which URL (if any) gets put on which tour depends
    // on a route access check. This can have a lot of inputs, including user
    // permissions and other factors. Rather than doing a complicated
    // accounting of the cache metadata for all of these possible factors, set
    // the max age of the cache to zero to prevent using incorrect cached
    // information.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    /** @var \Drupal\tour\TourInterface[] $tours */
    $tours = $this->entityTypeManager->getStorage('tour')->loadMultiple();
    // Sort in the manner defined by Tour.
    uasort($tours, ['Drupal\tour\Entity\Tour', 'sort']);

    // Make a link to each tour, using the first of its routes that can
    // be linked to by this user, if any.
    $topics = [];
    foreach ($tours as $tour) {
      $title = $tour->label();
      $id = $tour->id();
      $routes = $tour->getRoutes();
      $made_link = FALSE;
      foreach ($routes as $route) {
        // Some tours are for routes with parameters. For instance, there is
        // currently a tour in the Language module for the language edit page,
        // which appears on all pages with URLs like:
        // /admin/config/regional/language/edit/LANGCODE.
        // There is no way to make a link to the page that displays the tour,
        // because it is a set of pages. The easiest way to detect this is to
        // use a try/catch exception -- try to make a link, and it will error
        // out with a missing parameter exception if the route leads to a set
        // of pages instead of a single page.
        try {
          $params = isset($route['route_params']) ? $route['route_params'] : [];
          $url = Url::fromRoute($route['route_name'], $params);
          // Skip this route if the current user cannot access it.
          if (!$url->access()) {
            continue;
          }

          // Generate the link HTML directly, using toString(), to catch
          // missing parameter exceptions now instead of at render time.
          $topics[$id] = Link::fromTextAndUrl($title, $url)->toString();
          // If the line above didn't generate an exception, we have a good
          // link that the user can access.
          $made_link = TRUE;
          break;
        }
        catch (\Exception $e) {
          // Exceptions are normally due to routes that need parameters. If
          // there is an exception, just try the next route and see if we can
          // find one that will work for us.
        }
      }
      if (!$made_link) {
        // None of the routes worked to make a link, so at least display the
        // tour title.
        $topics[$id] = $title;
      }
    }

    return $topics;
  }

}
