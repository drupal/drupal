<?php

namespace Drupal\Core\Theme;

/**
 * Defines an interface for generating the data for a site's manifest.json file.
 */
interface ManifestGeneratorInterface {

  /**
   * Generate contents for the manifest.json for a specified theme.
   *
   * @param string $themeName
   *   The theme name.
   *
   * @return \Drupal\Core\Theme\Manifest
   *   The manifest file contents, complete with cache metadata.
   */
  public function generateManifest($themeName) : Manifest;

}
