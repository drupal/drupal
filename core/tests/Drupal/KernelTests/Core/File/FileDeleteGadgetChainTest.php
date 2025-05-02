<?php

declare(strict_types=1);

// cSpell:ignore phpggc

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests protection against SA-CORE-2024-006 File Delete Gadget Chain.
 *
 * @group file
 */
class FileDeleteGadgetChainTest extends KernelTestBase {

  /**
   * Tests unserializing a File Delete payload.
   */
  public function testFileDeleteGadgetChain(): void {
    file_put_contents('public://canary.txt', 'now you see me');
    // ./phpggc --public-properties Drupal/FD1 public://canary.txt
    $payload = 'O:34:"Drupal\Core\Config\StorageComparer":1:{s:18:"targetCacheStorage";O:39:"Drupal\Component\PhpStorage\FileStorage":1:{s:9:"directory";s:19:"public://canary.txt";}}';

    try {
      unserialize($payload);
      $this->fail('No exception was thrown');
    }
    catch (\Throwable $e) {
      $this->assertInstanceOf(\TypeError::class, $e);
      $this->assertStringContainsString('Cannot assign Drupal\Component\PhpStorage\FileStorage to property Drupal\Core\Config\StorageComparer::$targetCacheStorage', $e->getMessage());
    }

    $this->assertTrue(file_exists('public://canary.txt'));
    unlink('public://canary.txt');
  }

}
