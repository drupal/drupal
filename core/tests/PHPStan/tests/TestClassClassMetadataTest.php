<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Tests;

// cspell:ignore analyse

use Drupal\PHPStan\Rules\TestClassClassMetadata;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests TestClassClassMetadata rule.
 *
 * @extends RuleTestCase<TestClassClassMetadata>
 */
#[Group('PHPStan')]
class TestClassClassMetadataTest extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new TestClassClassMetadata(
      self::getContainer()->getByType(ReflectionProvider::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testRule(): void {
    $this->analyse(
      [__DIR__ . '/../fixtures/test-classes-with-metadata.php'],
      [
        [
          'Abstract test class Drupal\Tests\Core\Foo\BarTest must not add attribute PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses.',
          17,
        ],
        [
          'Abstract test class Drupal\Tests\Core\Foo\BarTest must not add annotation @group.',
          17,
        ],
        [
          'Abstract test class Drupal\Tests\Core\Foo\BarTest must not add annotation @coversNothing.',
          17,
        ],
        [
          'Test class Drupal\Tests\Core\Foo\ConcreteWithAnnotationTest must not add annotation @group.',
          35,
        ],
        [
          'Test class Drupal\Tests\Core\Foo\ConcreteWithAnnotationTest must have attribute \PHPUnit\Framework\Attributes\Group.',
          35,
        ],
      ]
    );

    // This is in a separate file to avoid autoloading the classes in the test
    // fixture.
    $this->analyse(
      [__DIR__ . '/../fixtures/test-classes-missing-attributes.php'],
      [
        [
          'Test class Drupal\Tests\Core\Foo\MissingAttributes must have attribute \PHPUnit\Framework\Attributes\RunInSeparateProcesses.',
          18,
        ],
        [
          'Test class Drupal\Tests\Core\Foo\MissingAttributes must have attribute \PHPUnit\Framework\Attributes\Group.',
          18,
        ],
        [
          'Test class Drupal\Tests\Core\Foo\MissingGroup must have attribute \PHPUnit\Framework\Attributes\Group.',
          21,
        ],
        [
          'Test class Drupal\Tests\Core\Foo\MissingRunTestsInSeparateProcesses must have attribute \PHPUnit\Framework\Attributes\RunInSeparateProcesses.',
          25,
        ],
      ]
    );

  }

}
