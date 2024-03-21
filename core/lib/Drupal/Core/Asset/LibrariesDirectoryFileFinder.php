<?php

namespace Drupal\Core\Asset;

use Drupal\Core\Extension\ProfileExtensionList;

/**
 * Finds files that are located in the supported 'libraries' directories.
 */
class LibrariesDirectoryFileFinder {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * The profile extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $profileExtensionList;

  /**
   * The install profile.
   *
   * @var string|false|null
   */
  protected $installProfile;

  /**
   * Constructs a new LibrariesDirectoryFileFinder instance.
   *
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_extension_list
   *   The profile extension list.
   * @param string $install_profile
   *   The install profile.
   */
  public function __construct($root, $site_path, ProfileExtensionList $profile_extension_list, $install_profile) {
    $this->root = $root;
    $this->sitePath = $site_path;
    $this->profileExtensionList = $profile_extension_list;
    $this->installProfile = $install_profile;
  }

  /**
   * Finds files that are located in the supported 'libraries' directories.
   *
   * It searches the following locations:
   * - A libraries directory in the current site directory, for example:
   *   sites/default/libraries.
   * - The root libraries directory.
   * - A libraries directory in the selected installation profile, for example:
   *   profiles/my_install_profile/libraries.
   * If the same library is present in multiple locations the first location
   * found will be used. The locations are searched in the order listed.
   *
   * @param string $path
   *   The path for the library file to find.
   *
   * @return string|false
   *   The real path to the library file relative to the root directory. If the
   *   library cannot be found then FALSE.
   */
  public function find($path) {
    // Search sites/<domain>/*.
    $directories[] = "{$this->sitePath}/libraries/";

    // Always search the root 'libraries' directory.
    $directories[] = 'libraries/';

    // Installation profiles can place libraries into a 'libraries' directory.
    if ($this->installProfile) {
      $profile_path = $this->profileExtensionList->getPath($this->installProfile);
      $directories[] = "$profile_path/libraries/";
    }

    foreach ($directories as $dir) {
      if (file_exists($this->root . '/' . $dir . $path)) {
        return $dir . $path;
      }
    }
    // The library has not been found.
    return FALSE;
  }

}
