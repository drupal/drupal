<?php

namespace Drupal\BuildTests\Framework;

use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Util\Test;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Allows test classes to require external command line applications.
 *
 * Use annotation such as '(at)requires externalCommand git'.
 */
trait ExternalCommandRequirementsTrait {

  /**
   * A list of existing external commands we've already discovered.
   *
   * @var string[]
   */
  private static $existingCommands = [];

  /**
   * Checks whether required external commands are available per test class.
   *
   * @throws \PHPUnit\Framework\SkippedTestError
   *   Thrown when the requirements are not met, and this test should be
   *   skipped. Callers should not catch this exception.
   */
  private static function checkClassCommandRequirements() {
    $annotations = Test::parseTestMethodAnnotations(static::class);
    if (!empty($annotations['class']['requires'])) {
      static::checkExternalCommandRequirements($annotations['class']['requires']);
    }
  }

  /**
   * Checks whether required external commands are available per method.
   *
   * @throws \PHPUnit\Framework\SkippedTestError
   *   Thrown when the requirements are not met, and this test should be
   *   skipped. Callers should not catch this exception.
   */
  private static function checkMethodCommandRequirements($name) {
    $annotations = Test::parseTestMethodAnnotations(static::class, $name);
    if (!empty($annotations['method']['requires'])) {
      static::checkExternalCommandRequirements($annotations['method']['requires']);
    }
  }

  /**
   * Checks missing external command requirements.
   *
   * @param string[] $annotations
   *   A list of requires annotations from either a method or class annotation.
   *
   * @throws \PHPUnit\Framework\SkippedTestError
   *   Thrown when the requirements are not met, and this test should be
   *   skipped. Callers should not catch this exception.
   */
  private static function checkExternalCommandRequirements(array $annotations) {
    // Make a list of required commands.
    $required_commands = [];
    foreach ($annotations as $requirement) {
      if (strpos($requirement, 'externalCommand ') === 0) {
        $command = trim(str_replace('externalCommand ', '', $requirement));
        // Use named keys to avoid duplicates.
        $required_commands[$command] = $command;
      }
    }

    // Figure out which commands are not available.
    $unavailable = [];
    foreach ($required_commands as $required_command) {
      if (!in_array($required_command, self::$existingCommands)) {
        if (static::externalCommandIsAvailable($required_command)) {
          // Cache existing commands so we don't have to ask again.
          self::$existingCommands[] = $required_command;
        }
        else {
          $unavailable[] = $required_command;
        }
      }
    }

    // Skip the test if there were some we couldn't find.
    if (!empty($unavailable)) {
      throw new SkippedTestError('Required external commands: ' . implode(', ', $unavailable));
    }
  }

  /**
   * Determine if an external command is available.
   *
   * @param $command
   *   The external command.
   *
   * @return bool
   *   TRUE if external command is available, else FALSE.
   */
  private static function externalCommandIsAvailable($command) {
    $finder = new ExecutableFinder();
    return (bool) $finder->find($command);
  }

}
