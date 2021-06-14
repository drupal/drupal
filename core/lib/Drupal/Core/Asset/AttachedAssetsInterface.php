<?php

namespace Drupal\Core\Asset;

/**
 * The attached assets collection for the current response.
 *
 * Allows for storage of:
 * - an ordered list of asset libraries (to be loaded for the current response)
 * - attached JavaScript settings (to be loaded for the current response)
 * - a set of asset libraries that the client already has loaded (as indicated
 *   in the request, to *not* be loaded for the current response)
 *
 * @see \Drupal\Core\Asset\AssetResolverInterface
 */
interface AttachedAssetsInterface {

  /**
   * Creates an AttachedAssetsInterface object from a render array.
   *
   * @param array $render_array
   *   A render array.
   *
   * @return static
   *
   * @throws \LogicException
   */
  public static function createFromRenderArray(array $render_array);

  /**
   * Sets the asset libraries attached to the current response.
   *
   * @param string[] $libraries
   *   A list of libraries, in the order they should be loaded.
   *
   * @return $this
   */
  public function setLibraries(array $libraries);

  /**
   * Returns the asset libraries attached to the current response.
   *
   * @return string[]
   */
  public function getLibraries();

  /**
   * Sets the JavaScript settings that are attached to the current response.
   *
   * @param array $settings
   *   The needed JavaScript settings.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Returns the settings attached to the current response.
   *
   * @return array
   */
  public function getSettings();

  /**
   * Sets the asset libraries that the current request marked as already loaded.
   *
   * @param string[] $libraries
   *   The set of already loaded libraries.
   *
   * @return $this
   */
  public function setAlreadyLoadedLibraries(array $libraries);

  /**
   * Returns the set of already loaded asset libraries.
   *
   * @return string[]
   */
  public function getAlreadyLoadedLibraries();

}
