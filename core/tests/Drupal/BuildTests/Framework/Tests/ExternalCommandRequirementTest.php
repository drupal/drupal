<?php

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait
 * @group Build
 */
class ExternalCommandRequirementTest extends TestCase {

  /**
   * @covers ::checkExternalCommandRequirements
   */
  public function testCheckExternalCommandRequirementsNotAvailable() {
    $requires = new UsesCommandRequirements();
    $ref_check_requirements = new \ReflectionMethod($requires, 'checkExternalCommandRequirements');
    $ref_check_requirements->setAccessible(TRUE);

    // Use a try/catch block because otherwise PHPUnit might think this test is
    // legitimately skipped.
    try {
      $ref_check_requirements->invokeArgs($requires, [
        ['externalCommand not_available', 'externalCommand available_command'],
      ]);
      $this->fail('Unavailable external command requirement should throw a skipped test error exception.');
    }
    catch (SkippedTestError $exception) {
      $this->assertEquals('Required external commands: not_available', $exception->getMessage());
    }
  }

  /**
   * @covers ::checkExternalCommandRequirements
   */
  public function testCheckExternalCommandRequirementsAvailable() {
    $requires = new UsesCommandRequirements();
    $ref_check_requirements = new \ReflectionMethod($requires, 'checkExternalCommandRequirements');
    $ref_check_requirements->setAccessible(TRUE);

    // Use a try/catch block because otherwise PHPUnit might think this test is
    // legitimately skipped.
    try {
      $this->assertNull(
        $ref_check_requirements->invokeArgs($requires, [['externalCommand available_command']])
      );
    }
    catch (SkippedTestError $exception) {
      $this->fail(sprintf('The external command should be available: %s', $exception->getMessage()));
    }
  }

  /**
   * @covers ::checkClassCommandRequirements
   */
  public function testClassRequiresAvailable() {
    $requires = new ClassRequiresAvailable();
    $ref_check = new \ReflectionMethod($requires, 'checkClassCommandRequirements');
    $ref_check->setAccessible(TRUE);
    // Use a try/catch block because otherwise PHPUnit might think this test is
    // legitimately skipped.
    try {
      $this->assertNull($ref_check->invoke($requires));
    }
    catch (SkippedTestError $exception) {
      $this->fail(sprintf('The external command should be available: %s', $exception->getMessage()));
    }
  }

  /**
   * @covers ::checkClassCommandRequirements
   */
  public function testClassRequiresUnavailable() {
    $requires = new ClassRequiresUnavailable();
    $ref_check = new \ReflectionMethod($requires, 'checkClassCommandRequirements');
    $ref_check->setAccessible(TRUE);
    // Use a try/catch block because otherwise PHPUnit might think this test is
    // legitimately skipped.
    try {
      $this->assertNull($ref_check->invoke($requires));
      $this->fail('Unavailable external command requirement should throw a skipped test error exception.');
    }
    catch (SkippedTestError $exception) {
      $this->assertEquals('Required external commands: unavailable_command', $exception->getMessage());
    }
  }

  /**
   * @covers ::checkMethodCommandRequirements
   */
  public function testMethodRequiresAvailable() {
    $requires = new MethodRequires();
    $ref_check = new \ReflectionMethod($requires, 'checkMethodCommandRequirements');
    $ref_check->setAccessible(TRUE);
    // Use a try/catch block because otherwise PHPUnit might think this test is
    // legitimately skipped.
    try {
      $this->assertNull($ref_check->invoke($requires, 'testRequiresAvailable'));
    }
    catch (SkippedTestError $exception) {
      $this->fail(sprintf('The external command should be available: %s', $exception->getMessage()));
    }
  }

  /**
   * @covers ::checkMethodCommandRequirements
   */
  public function testMethodRequiresUnavailable() {
    $requires = new MethodRequires();
    $ref_check = new \ReflectionMethod($requires, 'checkMethodCommandRequirements');
    $ref_check->setAccessible(TRUE);
    // Use a try/catch block because otherwise PHPUnit might think this test is
    // legitimately skipped.
    try {
      $this->assertNull($ref_check->invoke($requires, 'testRequiresUnavailable'));
      $this->fail('Unavailable external command requirement should throw a skipped test error exception.');
    }
    catch (SkippedTestError $exception) {
      $this->assertEquals('Required external commands: unavailable_command', $exception->getMessage());
    }
  }

}

class UsesCommandRequirements {

  use ExternalCommandRequirementsTrait;

  protected static function externalCommandIsAvailable($command) {
    return in_array($command, ['available_command']);
  }

}

/**
 * @requires externalCommand available_command
 */
class ClassRequiresAvailable {

  use ExternalCommandRequirementsTrait;

  protected static function externalCommandIsAvailable($command) {
    return in_array($command, ['available_command']);
  }

}

/**
 * @requires externalCommand unavailable_command
 */
class ClassRequiresUnavailable {

  use ExternalCommandRequirementsTrait;

}

class MethodRequires {

  use ExternalCommandRequirementsTrait;

  /**
   * @requires externalCommand available_command
   */
  public function testRequiresAvailable() {

  }

  /**
   * @requires externalCommand unavailable_command
   */
  public function testRequiresUnavailable() {

  }

  protected static function externalCommandIsAvailable($command) {
    return in_array($command, ['available_command']);
  }

}
