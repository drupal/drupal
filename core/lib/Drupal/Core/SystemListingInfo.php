<?php

/**
 * @file
 * Definition of Drupal\Core\SystemListingInfo.
 */

namespace Drupal\Core;

/**
 * Returns information about system object files (modules, themes, etc.).
 *
 * This class finds the profile directories itself and also parses info files.
 */
class SystemListingInfo extends SystemListing {

  /**
   * Overrides Drupal\Core\SystemListing::profiles().
   */
  protected function profiles($directory) {
    $searchdir = array();
    // The 'core/profiles' directory contains pristine collections of modules
    // and themes as provided by a distribution. It is pristine in the same
    // way that the 'core/modules' directory is pristine for core; users
    // should avoid any modification by using the top-level or sites/<domain>
    // directories.
    $profile = drupal_get_profile();
    // For SimpleTest to be able to test modules packaged together with a
    // distribution we need to include the profile of the parent site (in
    // which test runs are triggered).
    if (drupal_valid_test_ua() && !drupal_installation_attempted()) {
      $testing_profile = config('simpletest.settings')->get('parent_profile');
      if ($testing_profile && $testing_profile != $profile) {
        $searchdir[] = drupal_get_path('profile', $testing_profile) . '/' . $directory;
      }
    }
    // In case both profile directories contain the same extension, the actual
    // profile always has precedence.
    $searchdir[] = drupal_get_path('profile', $profile) . '/' . $directory;
    return $searchdir;
  }

  /**
   * Overrides Drupal\Core\SystemListing::process().
   */
  protected function process(array $files, array $files_to_add) {
    // Duplicate files found in later search directories take precedence over
    // earlier ones, so we want them to overwrite keys in our resulting
    // $files array.
    // The exception to this is if the later file is from a module or theme not
    // compatible with Drupal core. This may occur during upgrades of Drupal
    // core when new modules exist in core while older contrib modules with the
    // same name exist in a directory such as /modules.
    foreach (array_intersect_key($files_to_add, $files) as $file_key => $file) {
      // If it has no info file, then we just behave liberally and accept the
      // new resource on the list for merging.
      if (file_exists($info_file = dirname($file->uri) . '/' . $file->name . '.info.yml')) {
        // Get the .info.yml file for the module or theme this file belongs to.
        $info = drupal_parse_info_file($info_file);

        // If the module or theme is incompatible with Drupal core, remove it
        // from the array for the current search directory, so it is not
        // overwritten when merged with the $files array.
        if (isset($info['core']) && $info['core'] != DRUPAL_CORE_COMPATIBILITY) {
          unset($files_to_add[$file_key]);
        }
      }
    }
    return $files_to_add;
  }

  /**
   * Overrides Drupal\Core\SystemListing::processFile().
   */
  protected function processFile($file) {
    $file->name = basename($file->name, '.info');
  }

}
