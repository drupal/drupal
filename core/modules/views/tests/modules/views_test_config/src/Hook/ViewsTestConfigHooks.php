<?php

declare(strict_types=1);

namespace Drupal\views_test_config\Hook;

use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_config.
 */
class ViewsTestConfigHooks {

  /**
   * Implements hook_ENTITY_TYPE_load().
   */
  #[Hook('view_load')]
  public function viewLoad(array $views) {
    // Emulate a severely broken view: this kind of view configuration cannot be
    // saved, it can likely be returned only by a corrupt active configuration.
    $broken_view_id = \Drupal::state()->get('views_test_config.broken_view');
    if (isset($views[$broken_view_id])) {
      $display =& $views[$broken_view_id]->getDisplay('default');
      $display['display_options']['fields']['id_broken'] = NULL;
    }
  }

  /**
   * Implements hook_views_post_render().
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, &$output, CachePluginBase $cache) {
    if (\Drupal::state()->get('views_test_config.views_post_render_cache_tag')) {
      \Drupal::state()->set('views_test_config.views_post_render_called', TRUE);
      // Set a cache key on output to ensure ViewsSelection::stripAdminAndAnchorTagsFromResults
      // correctly handles elements that aren't result rows.
      $output['#cache']['tags'][] = 'foo';
    }
  }

  /**
   * Implements hook_views_plugins_area_alter().
   */
  #[Hook('views_plugins_area_alter')]
  public function viewsPluginsAreaAlter(array &$definitions) : void {
    _views_test_config_disable_broken_handler($definitions, 'area');
  }

  /**
   * Implements hook_views_plugins_argument_alter().
   */
  #[Hook('views_plugins_argument_alter')]
  public function viewsPluginsArgumentAlter(array &$definitions) : void {
    _views_test_config_disable_broken_handler($definitions, 'argument');
  }

  /**
   * Implements hook_views_plugins_field_alter().
   */
  #[Hook('views_plugins_field_alter')]
  public function viewsPluginsFieldAlter(array &$definitions) : void {
    _views_test_config_disable_broken_handler($definitions, 'field');
  }

  /**
   * Implements hook_views_plugins_filter_alter().
   */
  #[Hook('views_plugins_filter_alter')]
  public function viewsPluginsFilterAlter(array &$definitions) : void {
    _views_test_config_disable_broken_handler($definitions, 'filter');
  }

  /**
   * Implements hook_views_plugins_relationship_alter().
   */
  #[Hook('views_plugins_relationship_alter')]
  public function viewsPluginsRelationshipAlter(array &$definitions) : void {
    _views_test_config_disable_broken_handler($definitions, 'relationship');
  }

  /**
   * Implements hook_views_plugins_sort_alter().
   */
  #[Hook('views_plugins_sort_alter')]
  public function viewsPluginsSortAlter(array &$definitions) : void {
    _views_test_config_disable_broken_handler($definitions, 'sort');
  }

}
