<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TempStore;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the PrivateTempStore for anonymous users.
 */
#[Group('TempStore')]
#[RunTestsInSeparateProcesses]
class AnonymousPrivateTempStoreTest extends KernelTestBase {

  /**
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->tempStore = $this->container->get('tempstore.private')->get('anonymous_private_temp_store');
  }

  /**
   * Tests anonymous can get without a previous set.
   */
  public function testAnonymousCanUsePrivateTempStoreGet(): void {
    $actual = $this->tempStore->get('foo');
    $this->assertNull($actual);
  }

  /**
   * Tests anonymous can use the PrivateTempStore.
   */
  public function testAnonymousCanUsePrivateTempStoreSet(): void {
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
