<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Core\Extension\ExtensionDiscovery;
use PHPUnit\Util\Test;
use PHPUnit\Framework\SkippedTestError;

/**
 * Allows test classes to require Drupal modules as dependencies.
 *
 * This trait is assumed to be on a subclass of \PHPUnit\Framework\TestCase, and
 * overrides \PHPUnit\Framework\TestCase::checkRequirements(). This allows the
 * test to be marked as skipped before any kernel boot processes have happened.
 */
trait TestRequirementsTrait {

  /**
   * Returns the Drupal root directory.
   *
   * @return string
   */
  protected static function getDrupalRoot() {
    return dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
  }

  /**
   * Check module requirements for the Drupal use case.
   *
   * This method is assumed to override
   * \PHPUnit\Framework\TestCase::checkRequirements().
   *
   * @throws \PHPUnit\Framework\SkippedTestError
   *   Thrown when the requirements are not met, and this test should be
   *   skipped. Callers should not catch this exception.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is
   *   no replacement.
   *
   * @see https://www.drupal.org/node/3418480
   */
  protected function checkRequirements() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3418480', E_USER_DEPRECATED);

    if (!$this->getName(FALSE) || !method_exists($this, $this->getName(FALSE))) {
      return;
    }

    $missingRequirements = Test::getMissingRequirements(
      static::class,
      $this->getName(FALSE)
    );

    if (!empty($missingRequirements)) {
      $this->markTestSkipped(implode(PHP_EOL, $missingRequirements));
    }

    $root = static::getDrupalRoot();

    // Check if required dependencies exist.
    $annotations = Test::parseTestMethodAnnotations(
      static::class,
      $this->getName()
    );
    if (!empty($annotations['class']['requires'])) {
      $this->checkModuleRequirements($root, $annotations['class']['requires']);
    }
    if (!empty($annotations['method']['requires'])) {
      $this->checkModuleRequirements($root, $annotations['method']['requires']);
    }
  }

  /**
   * Checks missing module requirements.
   *
   * Iterates through a list of requires annotations and looks for missing
   * modules. The test will be skipped if any of the required modules is
   * missing.
   *
   * @param string $root
   *   The path to the root of the Drupal installation to scan.
   * @param string[] $annotations
   *   A list of requires annotations from either a method or class annotation.
   *
   * @throws \PHPUnit\Framework\SkippedTestError
   *   Thrown when the requirements are not met, and this test should be
   *   skipped. Callers should not catch this exception.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is
   *   no replacement.
   *
   * @see https://www.drupal.org/node/3418480
   */
  private function checkModuleRequirements($root, array $annotations) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3418480', E_USER_DEPRECATED);

    // Make a list of required modules.
    $required_modules = [];
    foreach ($annotations as $requirement) {
      if (str_starts_with($requirement, 'module ')) {
        $required_modules[] = trim(str_replace('module ', '', $requirement));
      }
    }

    // If there are required modules, check if they're available.
    if (!empty($required_modules)) {
      // Scan for modules.
      $discovery = new ExtensionDiscovery($root, FALSE);
      $discovery->setProfileDirectories([]);
      $list = array_keys($discovery->scan('module'));
      $not_available = array_diff($required_modules, $list);
      if (!empty($not_available)) {
        throw new SkippedTestError('Required modules: ' . implode(', ', $not_available));
      }
    }
  }

}
