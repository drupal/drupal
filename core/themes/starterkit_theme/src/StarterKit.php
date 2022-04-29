<?php

namespace Drupal\starterkit_theme;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Theme\StarterKitInterface;

final class StarterKit implements StarterKitInterface {

  /**
   * {@inheritdoc}
   */
  public static function postProcess(string $working_dir, string $machine_name, string $theme_name): void {
    $info_file = "$working_dir/$machine_name.info.yml";
    $info = Yaml::decode(file_get_contents($info_file));
    unset($info['hidden']);
    file_put_contents($info_file, Yaml::encode($info));
  }

}
