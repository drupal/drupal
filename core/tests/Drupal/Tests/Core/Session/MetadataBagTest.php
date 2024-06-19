<?php

declare(strict_types=1);

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
  public function testStampNew(): void {
    $metadata = new MetadataBag(new Settings([]));
    $metadata->setCsrfTokenSeed('a_cryptographically_secure_long_random_string_should_used_here');
    $metadata->stampNew();
    $this->assertNotEquals('a_cryptographically_secure_long_random_string_should_used_here', $metadata->getCsrfTokenSeed());
  }

}
