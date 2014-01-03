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
 *
 * @group Drupal
 * @group Crypt
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

  /**
   * Tests \Drupal\Component\Utility\Crypt::hashBase64().
   *
   * @param string $data
   *   Data to hash.
   * @param string $expected_hash
   *   Expected result from hashing $data.
   *
   * @dataProvider providerTestHashBase64
   */
  public function testHashBase64($data, $expected_hash) {
    $hash = Crypt::hashBase64($data);
    $this->assertEquals($expected_hash, $hash, 'The correct hash was not calculated.');
  }

  /**
   * Tests \Drupal\Component\Utility\Crypt::hmacBase64().
   *
   * @param string $data
   *   Data to hash.
   * @param string $key
   *   Key to use in hashing process.
   * @param string $expected_hmac
   *   Expected result from hashing $data using $key.
   *
   * @dataProvider providerTestHmacBase64
   */
  public function testHmacBase64($data, $key, $expected_hmac) {
    $hmac = Crypt::hmacBase64($data, $key);
    $this->assertEquals($expected_hmac, $hmac, 'The correct hmac was not calculated.');
  }

  /**
   * Tests the hmacBase64 method with invalid parameters.
   *
   * @param string $data
   *   Data to hash.
   * @param string $key
   *   Key to use in hashing process.
   *
   * @dataProvider providerTestHmacBase64Invalid
   * @expectedException InvalidArgumentException
   */
  public function testHmacBase64Invalid($data, $key) {
    Crypt::hmacBase64($data, $key);
  }

  /**
   * Provides data for self::testHashBase64().
   *
   * @return array Test data.
   */
  public function providerTestHashBase64() {
    return array(
      array(
        'data' => 'The SHA (Secure Hash Algorithm) is one of a number of cryptographic hash functions. A cryptographic hash is like a signature for a text or a data file. SHA-256 algorithm generates an almost-unique, fixed size 256-bit (32-byte) hash. Hash is a one way function – it cannot be decrypted back. This makes it suitable for password validation, challenge hash authentication, anti-tamper, digital signatures.',
        'expectedHash' => '034rT6smZAVRxpq8O98cFFNLIVx_Ph1EwLZQKcmRR_s',
      ),
      array(
        'data' => 'SHA-256 is one of the successor hash functions to SHA-1, and is one of the strongest hash functions available.',
        'expected_hash' => 'yuqkDDYqprL71k4xIb6K6D7n76xldO4jseRhEkEE6SI',
      ),
    );
  }

  /**
   * Provides data for self::testHmacBase64().
   *
   * @return array Test data.
   */
  public function providerTestHmacBase64() {
    return array(
      array(
        'data' => 'Calculates a base-64 encoded, URL-safe sha-256 hmac.',
        'key' => 'secret-key',
        'expected_hmac' => '2AaH63zwjhekWZlEpAiufyfhAHIzbQhl9Hd9oCi3_c8',
      ),
    );
  }

  /**
   * Provides data for self::testHmacBase64().
   *
   * @return array Test data.
   */
  public function providerTestHmacBase64Invalid() {
    return array(
      array(new \stdClass(), new \stdClass()),
      array(new \stdClass(), 'string'),
      array(new \stdClass(), 1),
      array(new \stdClass(), 0),
      array(NULL, new \stdClass()),
      array('string', new \stdClass()),
      array(1, new \stdClass()),
      array(0, new \stdClass()),
      array(array(), array()),
      array(array(), NULL),
      array(array(), 'string'),
      array(array(), 1),
      array(array(), 0),
      array(NULL, array()),
      array(1, array()),
      array(0, array()),
      array('string', array()),
      array(array(), NULL),
      array(NULL, NULL),
      array(NULL, 'string'),
      array(NULL, 1),
      array(NULL, 0),
      array(1, NULL),
      array(0, NULL),
      array('string', NULL),
    );
  }

}
