<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Annotation;

use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Annotation\PluralTranslation.
 */
#[CoversClass(PluralTranslation::class)]
#[Group('Annotation')]
class PluralTranslationTest extends UnitTestCase {

  /**
   * Tests get.
   *
   * @legacy-covers ::get
   */
  #[DataProvider('providerTestGet')]
  public function testGet(array $values): void {
    $annotation = new PluralTranslation($values);

    $default_values = [
      'context' => NULL,
    ];
    $this->assertEquals($values + $default_values, $annotation->get());
  }

  /**
   * Provides data to self::testGet().
   */
  public static function providerTestGet(): array {
    $data = [];
    $data[] = [
      [
        'singular' => Random::machineName(),
        'plural' => Random::machineName(),
        'context' => Random::machineName(),
      ],
    ];
    $data[] = [
      [
        'singular' => Random::machineName(),
        'plural' => Random::machineName(),
      ],
    ];

    return $data;
  }

  /**
 * Tests missing data.
 */
  #[DataProvider('providerTestMissingData')]
  public function testMissingData($data): void {
    $this->expectException(\InvalidArgumentException::class);
    new PluralTranslation($data);
  }

  public static function providerTestMissingData(): array {
    $data = [];
    $data['all-missing'] = [[]];
    $data['singular-missing'] = [['plural' => 'muh']];
    $data['plural-missing'] = [['singular' => 'muh']];
    return $data;
  }

}
