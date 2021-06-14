<?php

namespace Drupal\Tests\Component\Uuid;

use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Component\Uuid\Com;
use Drupal\Component\Uuid\Pecl;
use Drupal\Component\Uuid\Php;
use PHPUnit\Framework\TestCase;

/**
 * Tests the handling of Universally Unique Identifiers (UUIDs).
 *
 * @group Uuid
 */
class UuidTest extends TestCase {

  /**
   * Tests generating valid UUIDs.
   *
   * @dataProvider providerUuidInstances
   */
  public function testGenerateUuid(UuidInterface $instance) {
    $this->assertTrue(Uuid::isValid($instance->generate()), sprintf('UUID generation for %s works.', get_class($instance)));
  }

  /**
   * Tests that generated UUIDs are unique.
   *
   * @dataProvider providerUuidInstances
   */
  public function testUuidIsUnique(UuidInterface $instance) {
    $this->assertNotEquals($instance->generate(), $instance->generate(), sprintf('Same UUID was not generated twice with %s.', get_class($instance)));
  }

  /**
   * Data provider for UUID instance tests.
   *
   * @return array
   */
  public function providerUuidInstances() {

    $instances = [];
    $instances[][] = new Php();

    // If valid PECL extensions exists add to list.
    if (function_exists('uuid_create') && !function_exists('uuid_make')) {
      $instances[][] = new Pecl();
    }

    // If we are on Windows add the com implementation as well.
    if (function_exists('com_create_guid')) {
      $instances[][] = new Com();
    }

    return $instances;
  }

  /**
   * Tests UUID validation.
   *
   * @param string $uuid
   *   The uuid to check against.
   * @param bool $is_valid
   *   Whether the uuid is valid or not.
   * @param string $message
   *   The message to display on failure.
   *
   * @dataProvider providerTestValidation
   */
  public function testValidation($uuid, $is_valid, $message) {
    $this->assertSame($is_valid, Uuid::isValid($uuid), $message);
  }

  /**
   * Data provider for UUID instance tests.
   *
   * @return array
   *   An array of arrays containing
   *   - The Uuid to check against.
   *   - (bool) Whether or not the Uuid is valid.
   *   - Failure message.
   */
  public function providerTestValidation() {
    return [
      // These valid UUIDs.
      ['6ba7b810-9dad-11d1-80b4-00c04fd430c8', TRUE, 'Basic FQDN UUID did not validate'],
      ['00000000-0000-0000-0000-000000000000', TRUE, 'Minimum UUID did not validate'],
      ['ffffffff-ffff-ffff-ffff-ffffffffffff', TRUE, 'Maximum UUID did not validate'],
      // These are invalid UUIDs.
      ['0ab26e6b-f074-4e44-9da-601205fa0e976', FALSE, 'Invalid format was validated'],
      ['0ab26e6b-f074-4e44-9daf-1205fa0e9761f', FALSE, 'Invalid length was validated'],
    ];
  }

}
