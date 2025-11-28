<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Tests;

// cspell:ignore analyse

use Drupal\PHPStan\Rules\TestClassMethodMetadata;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests TestClassMethodMetadata rule.
 *
 * @extends RuleTestCase<TestClassMethodMetadata>
 */
#[TestDox('Check method-level test metadata')]
#[Group('PHPStan')]
class TestClassMethodMetadataTest extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new TestClassMethodMetadata(
      self::getContainer()->getByType(ReflectionProvider::class),
      self::getContainer()->getByType(FileTypeMapper::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testRule(): void {
    $this->analyse(
      [__DIR__ . '/../fixtures/test-methods-with-metadata.php'],
      [
        [
          'Test method testWithAttributeAndForbiddenAnnotation must not add annotation @group.',
          21,
        ],
        [
          'Test method testWithForbiddenAnnotation must not add annotation @group.',
          35,
        ],
        [
          'Test method testInTraitWithAttributeAndForbiddenAnnotation must not add annotation @group.',
          69,
        ],
        [
          'Test method testInTraitWithForbiddenAnnotation must not add annotation @group.',
          83,
        ],
      ],
    );
  }

}
