<?php

namespace Drupal\Core\Theme;

/**
 * Allows starter kits to interact with theme generation.
 */
interface StarterKitInterface {

  /**
   * Performs post-processing of a generated theme.
   *
   * @param string $working_dir
   *   The working directory of the template being generated.
   * @param string $machine_name
   *   The theme's machine name.
   * @param string $theme_name
   *   The theme's name.
   */
  public static function postProcess(string $working_dir, string $machine_name, string $theme_name): void;

}
