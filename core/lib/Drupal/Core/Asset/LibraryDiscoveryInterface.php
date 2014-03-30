<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscoveryInterface.
 */

namespace Drupal\Core\Asset;

/**
 * Discovers information for asset (CSS/JavaScript) libraries.
 *
 * Library information is statically cached. Libraries are keyed by extension
 * for several reasons:
 * - Libraries are not unique. Multiple extensions might ship with the same
 *   library in a different version or variant. This registry cannot (and does
 *   not attempt to) prevent library conflicts.
 * - Extensions implementing and thereby depending on a library that is
 *   registered by another extension can only rely on that extension's library.
 * - Two (or more) extensions can still register the same library and use it
 *   without conflicts in case the libraries are loaded on certain pages only.
 */
interface LibraryDiscoveryInterface {

  /**
   * Gets all libraries defined by an extension.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return array
   *   An associative array of libraries registered by $extension is returned
   *   (which may be empty).
   *
   * @see self::getLibraryByName()
   */
  public function getLibrariesByExtension($extension);

  /**
   * Gets a single library defined by an extension by name.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $name
   *   The name of a registered library to retrieve.
   *
   * @return array|FALSE
   *   The definition of the requested library, if $name was passed and it
   *   exists, otherwise FALSE.
   */
  public function getLibraryByName($extension, $name);

}
