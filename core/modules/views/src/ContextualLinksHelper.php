<?php

declare(strict_types=1);

namespace Drupal\views;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Views contextual links helper service.
 */
class ContextualLinksHelper {

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'plugin.manager.views.display')]
    protected PluginManagerInterface $displayManager,
  ) {}

  /**
   * Adds view's display contextual links to a renderable array.
   *
   * Checks the view's display plugin for any contextual links defined for the
   * requested location and attaches them if found.
   *
   * Each display plugin controls which contextual links it provides and where
   * they appear, using the 'contextual_links' and 'contextual_links_locations'
   * properties in its attribute.
   *
   * This function attaches two properties to the passed-in array:
   *
   * - #contextual_links: The standard contextual links for the display.
   * - #views_contextual_links_info: A metadata array keyed by module name
   *   (matching the keys in #contextual_links). Each entry holds three values:
   *   'location', 'view_name', and 'view_display_id', reflecting the arguments
   *   passed to this function. This metadata is useful when you need to
   *   inspect or alter the renderable array later in the page request, such as
   *   inside alter hooks.
   *
   * @param array $renderElement
   *   The renderable array to which contextual links will be added.
   * @param string $location
   *   The location in which the calling function intends to render the view
   *   and its contextual links. The core system supports three options for
   *   this parameter:
   *   - block: Used when rendering a block which contains a view. This
   *     retrieves any contextual links intended to be attached to the block
   *     itself.
   *   - page: Used when rendering the main content of a page which contains
   *     a view. This retrieves any contextual links intended to be attached to
   *     the page itself (for example, links which are displayed directly next
   *     to the page title).
   *   - view: Used when rendering the view itself, in any context. This
   *     retrieves any contextual links intended to be attached directly to the
   *     view.
   *   Example: If you are rendering a view and its contextual links in another
   *   location, you can pass in a different value for the $location parameter.
   *   However, you will also need to set 'contextual_links_locations' in your
   *   plugin annotation to indicate which view displays support having their
   *   contextual links rendered in the location you have defined.
   * @param string $displayId
   *   The ID of the view display whose contextual links will be added.
   * @param array|null $viewElement
   *   (optional) The render array of the view. Defaults to $renderElement. It
   *   should contain the following properties:
   *   - #view_id: The ID of the view.
   *   - #view_display_show_admin_links: A boolean indicating whether the admin
   *     links should be shown.
   *   - #view_display_plugin_id: The plugin ID of the display.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::addContextualLinks()
   * @see \Drupal\views\Hook\ViewsThemeHooks::preprocessViewsView()
   */
  public function addLinks(array &$renderElement, string $location, string $displayId, ?array $viewElement = NULL): void {
    assert(in_array($location, ['block', 'page', 'view'], TRUE));

    if (!isset($viewElement)) {
      $viewElement = $renderElement;
    }

    // Exit if the Contextual Links module is not enabled or if the view is
    // configured to hide its administrative links.
    if (!$this->moduleHandler->moduleExists('contextual') || !$viewElement['#view_display_show_admin_links']) {
      return;
    }

    $viewId = $viewElement['#view_id'];
    $displayPluginId = $viewElement['#view_display_plugin_id'];

    $plugin = $this->displayManager->getDefinition($displayPluginId);
    // If contextual_links_locations are not set, provide a sane default. Filter
    // empty items because, to avoid displaying any contextual links at all, a
    // display plugin can still set 'contextual_links_locations' to, e.g., {""}.
    $plugin['contextual_links_locations'] = array_filter($plugin['contextual_links_locations'] ?? ['view']);

    // On exposed_forms blocks contextual links should always be visible.
    $plugin['contextual_links_locations'][] = 'exposed_filter';
    $hasLinks = !empty($plugin['contextual links']) && !empty($plugin['contextual_links_locations']);

    // Also, do not do anything if the display plugin has not defined any
    // contextual links that are intended to be displayed in the requested
    // location.
    if (!$hasLinks || !in_array($location, $plugin['contextual_links_locations'])) {
      return;
    }

    $viewStorage = $this->entityTypeManager->getStorage('view')->load($viewId);

    foreach ($plugin['contextual links'] as $group => $link) {
      $args = [];
      $valid = TRUE;
      if (!empty($link['route_parameters_names'])) {
        foreach ($link['route_parameters_names'] as $parameterName => $property) {
          // If the plugin is trying to create an invalid contextual link (for
          // example, "path/to/{$view->storage->property}", where
          // $view->storage->{property} does not exist), we cannot construct the
          // link, so we skip it.
          if (!property_exists($viewStorage, $property)) {
            $valid = FALSE;
            break;
          }
          else {
            $args[$parameterName] = $viewStorage->get($property);
          }
        }
      }

      if (!$valid) {
        continue;
      }

      // Link is valid. Attach information about it to the renderable array.
      $renderElement['#views_contextual_links'] = TRUE;
      $renderElement['#contextual_links'][$group] = [
        'route_parameters' => $args,
        'metadata' => [
          'location' => $location,
          'name' => $viewId,
          'display_id' => $displayId,
        ],
      ];
      $renderElement['#cache']['contexts'][] = 'user.permissions';
    }
  }

}
