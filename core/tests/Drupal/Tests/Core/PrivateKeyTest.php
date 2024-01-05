<?php

declare(strict_types=1);

namespace Drupal\Tests\Core;

use Drupal\Core\PrivateKey;
use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\Crypt;

/**
 * Tests the PrivateKey class.
 *
 * @group PrivateKeyTest
 */
class PrivateKeyTest extends UnitTestCase {

  /**
   * The state mock class.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * The private key service mock.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The random key to use in tests.
   *
   * @var string
   */
  protected $key;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->key = Crypt::randomBytesBase64(55);

    $this->state = $this->createMock('Drupal\Core\State\StateInterface');

    $this->privateKey = new PrivateKey($this->state);
  }

  /**
   * Tests PrivateKey::get().
   */
  public function testGet() {
    $this->state->expects($this->once())
      ->method('get')
      ->with('system.private_key')
      ->willReturn($this->key);

    $this->assertEquals($this->key, $this->privateKey->get());
  }

  /**
   * Tests PrivateKey::get() with no private key from state.
   */
  public function testGetNoState() {
    $this->assertIsString($this->privateKey->get());
  }

  /**
   * Tests PrivateKey::setPrivateKey().
   */
  public function testSet() {
    $random_name = $this->randomMachineName();

    $this->state->expects($this->once())
      ->method('set')
      ->with('system.private_key', $random_name)
      ->willReturn(TRUE);

    $this->privateKey->set($random_name);
  }

}
