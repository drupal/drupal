<?php

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
  public function coreModuleListDataProvider() {
    $module_dirs = array_keys(iterator_to_array(new \FilesystemIterator(__DIR__ . '/../../../modules/')));
    $module_names = array_map(function ($path) {
      return str_replace(__DIR__ . '/../../../modules/', '', $path);
    }, $module_dirs);
    $modules_keyed = array_combine($module_names, $module_names);

    $data = array_map(function ($module) {
      return [$module];
    }, $modules_keyed);

    return $data;
  }

}
