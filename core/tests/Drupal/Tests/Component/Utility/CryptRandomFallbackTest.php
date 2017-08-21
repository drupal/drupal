<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Crypt;
use PHPUnit\Framework\TestCase;

/**
 * Tests random byte generation fallback exception situations.
 *
 * @group Utility
 *
 * @runTestsInSeparateProcesses
 *
 * @coversDefaultClass \Drupal\Component\Utility\Crypt
 */
class CryptRandomFallbackTest extends TestCase {

  static protected $functionCalled = 0;

  /**
   * Allows the test to confirm that the namespaced random_bytes() was called.
   */
  public static function functionCalled() {
    static::$functionCalled++;
  }

  /**
   * Tests random byte generation using the fallback generator.
   *
   * If the call to random_bytes() throws an exception, Crypt::random_bytes()
   * should still return a useful string of random bytes.
   *
   * @covers ::randomBytes
   *
   * @see \Drupal\Tests\Component\Utility\CryptTest::testRandomBytes()
   */
  public function testRandomBytesFallback() {
    // This loop is a copy of
    // \Drupal\Tests\Component\Utility\CryptTest::testRandomBytes().
    for ($i = 0; $i < 10; $i++) {
      $count = rand(10, 10000);
      // Check that different values are being generated.
      $this->assertNotEquals(Crypt::randomBytes($count), Crypt::randomBytes($count));
      // Check the length.
      $this->assertEquals($count, strlen(Crypt::randomBytes($count)));
    }
    $this->assertEquals(30, static::$functionCalled, 'The namespaced function was called the expected number of times.');
  }

}

namespace Drupal\Component\Utility;

use  Drupal\Tests\Component\Utility\CryptRandomFallbackTest;

/**
 * Defines a function in same namespace as Drupal\Component\Utility\Crypt.
 *
 * Forces throwing an exception in this test environment because the function
 * in the namespace is used in preference to the global function.
 *
 * @param int $count
 *   Matches the global function definition.
 *
 * @throws \Exception
 */
function random_bytes($count) {
  CryptRandomFallbackTest::functionCalled();
  throw new \Exception($count);
}
