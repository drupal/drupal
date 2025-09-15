<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Tests;

// cspell:ignore analyse

use Drupal\PHPStan\Rules\NoClassMetadataOnAbstractTestClasses;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Tests NoClassMetadataOnAbstractTestClasses rule.
 *
 * @extends RuleTestCase<NoClassMetadataOnAbstractTestClasses>
 */
class NoClassMetadataOnAbstractTestClassesTest extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new NoClassMetadataOnAbstractTestClasses(
      self::getContainer()->getByType(ReflectionProvider::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testRule(): void {
    $this->analyse(
      [__DIR__ . '/../fixtures/abstract-test-classes.php'],
      [
        [
          'Abstract test class Drupal\Tests\Core\Foo\BarTest must not add attribute PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses.',
          16,
        ],
        [
          'Abstract test class Drupal\Tests\Core\Foo\BarTest must not add annotation @group.',
          16,
        ],
        [
          'Abstract test class Drupal\Tests\Core\Foo\BarTest must not add annotation @coversNothing.',
          16,
        ],
      ]
    );
  }

}
