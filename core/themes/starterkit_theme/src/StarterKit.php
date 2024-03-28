<?php

namespace Drupal\starterkit_theme;

use Drupal\Core\Theme\StarterKitInterface;

final class StarterKit implements StarterKitInterface {

  /**
   * {@inheritdoc}
   */
  public static function postProcess(string $working_dir, string $machine_name, string $theme_name): void {
    $readme_file = "$working_dir/README.md";
    try {
      file_put_contents($readme_file, "$theme_name theme, generated from starterkit_theme. Additional information on generating themes can be found in the [Starterkit documentation](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme).");
    }
    catch (\Throwable $th) {
    }
  }

}
