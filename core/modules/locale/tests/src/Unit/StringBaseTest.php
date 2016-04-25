<?php

namespace Drupal\Tests\locale\Unit;

use Drupal\locale\SourceString;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\locale\StringBase
 * @group locale
 */
class StringBaseTest extends UnitTestCase {

  /**
   * @covers ::save
   * @expectedException \Drupal\locale\StringStorageException
   * @expectedExceptionMessage The string cannot be saved because its not bound to a storage: test
   */
  public function testSaveWithoutStorage() {
    $string = new SourceString(['source' => 'test']);
    $string->save();
  }


  /**
   * @covers ::delete
   * @expectedException \Drupal\locale\StringStorageException
   * @expectedExceptionMessage The string cannot be deleted because its not bound to a storage: test
   */
  public function testDeleteWithoutStorage() {
    $string = new SourceString(['lid' => 1, 'source' => 'test']);
    $string->delete();
  }

}
