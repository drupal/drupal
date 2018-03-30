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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install system tables to test the key/value storage without installing a
    // full Drupal environment.
    $this->installSchema('system', ['key_value_expire']);

    $session = $this->container->get('session');
    $request = Request::create('/');
    $request->setSession($session);

    $stack = $this->container->get('request_stack');
    $stack->pop();
    $stack->push($request);

  }

  /**
   * Tests anonymous can use the PrivateTempStore.
   */
  public function testAnonymousCanUsePrivateTempStore() {
    $temp_store = $this->container->get('tempstore.private')->get('anonymous_private_temp_store');
    $temp_store->set('foo', 'bar');
    $metadata1 = $temp_store->getMetadata('foo');

    $this->assertEquals('bar', $temp_store->get('foo'));
    $this->assertNotEmpty($metadata1->owner);

    $temp_store->set('foo', 'bar2');
    $metadata2 = $temp_store->getMetadata('foo');
    $this->assertEquals('bar2', $temp_store->get('foo'));
    $this->assertNotEmpty($metadata2->owner);
    $this->assertEquals($metadata2->owner, $metadata1->owner);
  }

}
