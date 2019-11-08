<?php

namespace Drupal\Tests;

/**
 * Provides methods remove deprecated modules from tests.
 */
trait DeprecatedModulesTestTrait {

  /**
   * List of deprecated modules names to not be enabled for tests.
   *
   * @var array
   */
  protected $deprecatedModules = ['block_place'];

  /**
   * Flag to exclude deprecated modules from the tests.
   *
   * Legacy*Test -test may set this to FALSE to include also deprecated
   * modules for tests.
   *
   * @var bool
   */
  protected $excludeDeprecated = TRUE;

  /**
   * Removes deprecated modules from the provided modules.list.
   *
   * @param array $modules
   *   Array of modules names.
   *
   * @return array
   *   The filtered $modules array.
   */
  protected function removeDeprecatedModules(array $modules) {
    return $this->excludeDeprecated ? array_diff($modules, $this->deprecatedModules) : $modules;
  }

  /**
   * Overrides \Drupal\KernelTests\KernelTestBase::enableModules().
   *
   * For kernel tests this override will ensure that deprecated modules are not
   * enabled if \Drupal\Tests\DeprecatedModulesTestTrait::$excludeDeprecated
   * is set to true.
   */
  protected function enableModules(array $modules) {
    parent::enableModules($this->removeDeprecatedModules($modules));
  }

}
