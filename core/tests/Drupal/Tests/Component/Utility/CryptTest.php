<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\CryptTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\Crypt;

/**
 * Tests random bytes generation.
 *
 * @see \Drupal\Component\Utility\Crypt
 */
class CryptTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Crypt generator tests',
      'description' => 'Test functionality of Crypt component.',
    );
  }

  /**
   * Tests \Drupal\Component\Utility\Crypt::randomBytes().
   */
  public function testRandomBytes() {
    for ($i = 1; $i < 10; $i++) {
      $count = rand(10, 10000);
      // Check that different values are being generated.
      $this->assertNotEquals(Crypt::randomBytes($count), Crypt::randomBytes($count));
      // Check the length.
      $this->assertEquals(strlen(Crypt::randomBytes($count)), $count);
    }
  }

}
