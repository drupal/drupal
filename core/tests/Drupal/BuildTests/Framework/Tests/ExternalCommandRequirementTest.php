<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait
 * @group Build
 * @group legacy
 */
class ExternalCommandRequirementTest extends TestCase {

  use ExpectDeprecationTrait;

  /**
   * @covers ::checkExternalCommandRequirements
   */
  public function testCheckExternalCommandRequirementsNotAvailable() {
    $this->expectDeprecation('Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait::checkExternalCommandRequirements() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $this->expectDeprecation('The \'@require externalCommand\' annotation for tests is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $requires = new UsesCommandRequirements();
    $ref_check_requirements = new \ReflectionMethod($requires, 'checkExternalCommandRequirements');

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
    $this->expectDeprecation('Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait::checkExternalCommandRequirements() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $this->expectDeprecation('The \'@require externalCommand\' annotation for tests is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $requires = new UsesCommandRequirements();
    $ref_check_requirements = new \ReflectionMethod($requires, 'checkExternalCommandRequirements');

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
    $this->expectDeprecation('Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait::checkClassCommandRequirements() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $requires = new ClassRequiresAvailable();
    $ref_check = new \ReflectionMethod($requires, 'checkClassCommandRequirements');
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
    $this->expectDeprecation('Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait::checkClassCommandRequirements() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $requires = new ClassRequiresUnavailable();
    $ref_check = new \ReflectionMethod($requires, 'checkClassCommandRequirements');
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
    $this->expectDeprecation('Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait::checkMethodCommandRequirements() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $requires = new MethodRequires();
    $ref_check = new \ReflectionMethod($requires, 'checkMethodCommandRequirements');
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
    $this->expectDeprecation('Drupal\BuildTests\Framework\ExternalCommandRequirementsTrait::checkMethodCommandRequirements() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use Drupal\\TestTools\\Extension\\RequiresComposerTrait instead. See https://www.drupal.org/node/3362239');
    $requires = new MethodRequires();
    $ref_check = new \ReflectionMethod($requires, 'checkMethodCommandRequirements');
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
