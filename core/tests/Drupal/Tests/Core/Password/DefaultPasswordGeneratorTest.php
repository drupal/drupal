<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Password;

use Drupal\Core\Password\DefaultPasswordGenerator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for password generator.
 */
#[CoversClass(DefaultPasswordGenerator::class)]
#[Group('System')]
class DefaultPasswordGeneratorTest extends UnitTestCase {

  /**
   * Tests generate.
   *
   * @legacy-covers ::generate
   */
  public function testGenerate(): void {
    $generator = new DefaultPasswordGenerator();
    $password = $generator->generate();
    $this->assertEquals(10, strlen($password));

    $password = $generator->generate(32);
    $this->assertEquals(32, strlen($password));
  }

}
