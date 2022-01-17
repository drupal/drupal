<?php

namespace Drupal\Tests\Core\Http;

use Drupal\Core\Http\InputBag;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Http\InputBag
 *
 * @group Http
 */
class InputBagTest extends UnitTestCase {

  /**
   * @covers ::all
   */
  public function testAll() {
    $input = [
      'bad' => 'bad',
      'good' => ['good'],
    ];
    $input_bag = new InputBag();
    $input_bag->replace($input);
    $this->assertSame($input_bag->all(), $input);
    $this->assertSame($input_bag->all('good'), ['good']);
    $this->expectException(\UnexpectedValueException::class);
    $input_bag->all('bad');
  }

}
