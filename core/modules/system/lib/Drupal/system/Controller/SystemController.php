<?php

/**
 * @file
 * Contains \Drupal\system\Controller\SystemController.
 */

namespace Drupal\system\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Returns responses for System routes.
 */
class SystemController extends ContainerAware {

  /**
   * Autocompletes any plugin system tied to a plugin UI plugin.
   *
   * The passed plugin_id indicates the specific plugin_ui plugin that is in use
   * here. The documentation within the annotation of that plugin will contain a
   * manager for the plugins that need to be autocompleted allowing this
   * function to autocomplete plugins for any plugin type.
   *
   * @param string $plugin_id
   *   The plugin id for the calling plugin.
   * @param Request $request
   *   The request object that contains the typed tags.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched plugins as json.
   */
  public function autocomplete($plugin_id, Request $request) {
    $string_typed = $request->query->get('q');
    $string_typed = Tags::explode($string_typed);
    $string = Unicode::strtolower(array_pop($string_typed));
    $matches = array();
    if ($string) {
      $plugin_ui = $this->container->get('plugin.manager.system.plugin_ui')->getDefinition($plugin_id);
      $manager = $this->container->get($plugin_ui['manager']);
      $titles = array();
      foreach($manager->getDefinitions() as $plugin_id => $plugin) {
        $titles[$plugin_id] = $plugin[$plugin_ui['title_attribute']];
      }
      $matches = preg_grep("/\b". $string . "/i", $titles);
    }

    return new JsonResponse($matches);
  }

}
