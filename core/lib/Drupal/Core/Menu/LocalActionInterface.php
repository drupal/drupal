<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalActionInterface.
 */

namespace Drupal\Core\Menu;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for menu local actions.
 */
interface LocalActionInterface {

  /**
   * Get the route name from the settings.
   *
   * @return string
   *   The name of the route this action links to.
   */
  public function getRouteName();

  /**
   * Returns the route parameters needed to render a link for the local action.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return array
   *   An array of parameter names and values.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  public function getRouteParameters(Request $request);

  /**
   * Returns the weight for the local action.
   *
   * @return int
   */
  public function getWeight();

  /**
   * Returns options for rendering a link for the local action.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object representing the current request.
   *
   * @return array
   *   An associative array of options.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  public function getOptions(Request $request);

  /**
   * Returns the localized title to be shown for this action.
   *
   * Subclasses may add optional arguments like NodeInterface $node = NULL that
   * will be supplied by the ControllerResolver.
   *
   * @return string
   *   The title to be shown for this action.
   *
   * @see \Drupal\Core\Menu\LocalActionManager::getTitle()
   */
  public function getTitle();



}
