<?php

namespace Drupal\KernelTests\Core\TempStore;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the PrivateTempStore for anonymous users.
 *
 * @group TempStore
 */
class AnonymousPrivateTempStoreTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install system tables to test the key/value storage without installing a
    // full Drupal environment.
    $this->installSchema('system', ['key_value_expire']);

    $request = Request::create('/');
    $stack = $this->container->get('request_stack');
    $stack->pop();
    $stack->push($request);

    $this->tempStore = $this->container->get('tempstore.private')->get('anonymous_private_temp_store');
  }

  /**
   * Tests anonymous can get without a previous set.
   */
  public function testAnonymousCanUsePrivateTempStoreGet() {
    $actual = $this->tempStore->get('foo');
    $this->assertNull($actual);
  }

  /**
   * Tests anonymous can use the PrivateTempStore.
   */
  public function testAnonymousCanUsePrivateTempStoreSet() {
    $this->tempStore->set('foo', 'bar');
    $metadata1 = $this->tempStore->getMetadata('foo');

    $this->assertEquals('bar', $this->tempStore->get('foo'));
    $this->assertNotEmpty($metadata1->getOwnerId());

    $this->tempStore->set('foo', 'bar2');
    $metadata2 = $this->tempStore->getMetadata('foo');
    $this->assertEquals('bar2', $this->tempStore->get('foo'));
    $this->assertNotEmpty($metadata2->getOwnerId());
    $this->assertEquals($metadata2->getOwnerId(), $metadata1->getOwnerId());
  }

}
