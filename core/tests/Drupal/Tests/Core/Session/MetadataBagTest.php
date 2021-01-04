<?php

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\MetadataBag;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Session\MetadataBag
 * @group Session
 */
class MetadataBagTest extends UnitTestCase {

  /**
   * @covers ::stampNew
   */
  public function testStampNew() {
    $metadata = new MetadataBag(new Settings([]));
    $metadata->setCsrfTokenSeed('a_cryptographically_secure_long_random_string_should_used_here');
    $metadata->stampNew();
    $this->assertNotEquals('a_cryptographically_secure_long_random_string_should_used_here', $metadata->getCsrfTokenSeed());
  }

  /**
   * @covers ::clearCsrfTokenSeed
   * @group legacy
   */
  public function testDeprecatedClearCsrfTokenSeed() {
    $this->expectDeprecation('Calling Drupal\Core\Session\MetadataBag::clearCsrfTokenSeed() is deprecated in drupal:9.2.0 and will be removed in drupal:10.0.0. Use \Drupal\Core\Session\MetadataBag::stampNew() instead. See https://www.drupal.org/node/3187914');

    $metadata = new MetadataBag(new Settings([]));
    $metadata->clearCsrfTokenSeed();
  }

}
