<?php

namespace Drupal\Tests\Core\Annotation;

use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;

/**
 * @coversDefaultClass \Drupal\Core\Annotation\PluralTranslation
 * @group Annotation
 */
class PluralTranslationTest extends UnitTestCase {

  /**
   * @covers ::get
   *
   * @dataProvider providerTestGet
   */
  public function testGet(array $values) {
    $annotation = new PluralTranslation($values);

    $default_values = [
      'context' => NULL,
    ];
    $this->assertEquals($values + $default_values, $annotation->get());
  }

  /**
   * Provides data to self::testGet().
   */
  public static function providerTestGet() {
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
   * @dataProvider providerTestMissingData
   */
  public function testMissingData($data) {
    $this->expectException(\InvalidArgumentException::class);
    new PluralTranslation($data);
  }

  public function providerTestMissingData() {
    $data = [];
    $data['all-missing'] = [[]];
    $data['singular-missing'] = [['plural' => 'muh']];
    $data['plural-missing'] = [['singular' => 'muh']];
    return $data;
  }

}
