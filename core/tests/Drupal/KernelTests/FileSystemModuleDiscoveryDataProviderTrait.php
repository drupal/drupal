<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

/**
 * A trait used in testing for providing a list of modules in a dataProvider.
 */
trait FileSystemModuleDiscoveryDataProviderTrait {

  /**
   * A data provider that lists every module in core.
   *
   * @return array
   *   An array of module names to test.
   */
  public static function coreModuleListDataProvider(): array {
    $prefix = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'modules';
    $module_dirs = array_keys(iterator_to_array(new \FilesystemIterator($prefix)));
    $module_names = array_map(function ($path) use ($prefix) {
      return str_replace($prefix . DIRECTORY_SEPARATOR, '', $path);
    }, $module_dirs);
    $modules_keyed = array_combine($module_names, $module_names);

    $data = array_map(function ($module) {
      return [$module];
    }, $modules_keyed);

    return $data;
  }

}
