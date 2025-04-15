<?php

declare(strict_types=1);

// cspell:ignore analyse
namespace Drupal\PHPStan\Tests;

use Drupal\PHPStan\Rules\ComponentTestDoesNotExtendCoreTest;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Tests ComponentTestDoesNotExtendCoreTest rule.
 */
class ComponentTestDoesNotExtendCoreTestTest extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new ComponentTestDoesNotExtendCoreTest(
      self::getContainer()->getByType(ReflectionProvider::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testRule(): void {
    $this->analyse(
      [__DIR__ . '/../fixtures/component-tests.php'],
      [
        [
          'Component tests should not extend Drupal\Tests\UnitTestCase.',
          19,
        ],
        [
          'Component tests should not extend Drupal\BuildTests\Framework\BuildTestBase.',
          22,
        ],
        [
          'Component tests should not extend Drupal\KernelTests\KernelTestBase.',
          25,
        ],
        [
          'Component tests should not extend Drupal\Tests\BrowserTestBase.',
          28,
        ],
        [
          'Component tests should not extend Drupal\Tests\BrowserTestBase.',
          31,
        ],
      ]
    );
  }

}
