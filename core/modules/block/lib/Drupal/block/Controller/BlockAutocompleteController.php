<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockAutocompleteController.
 */

namespace Drupal\block\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an autocomplete for blocks.
 */
class BlockAutocompleteController implements ControllerInterface {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new BlockAutocompleteController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The block plugin manager.
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Autocompletes a block plugin ID.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched plugins as JSON.
   */
  public function autocomplete(Request $request) {
    $string_typed = $request->query->get('q');
    // The passed string may be comma or space separated.
    $string_typed = Tags::explode($string_typed);
    // Take the last result and lowercase it.
    $string = Unicode::strtolower(array_pop($string_typed));
    $matches = array();
    if ($string) {
      $titles = array();
      // Gather all block plugins and their admin titles.
      foreach($this->manager->getDefinitions() as $plugin_id => $plugin) {
        $titles[$plugin_id] = $plugin['admin_label'];
      }
      // Find any matching block plugin IDs.
      $matches = preg_grep("/\b". $string . "/i", $titles);
    }

    return new JsonResponse($matches);
  }

}
