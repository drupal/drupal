<?php

declare(strict_types=1);

namespace Drupal\Tests\book\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Test installation of Book module.
 *
 * @group book
 * @group legacy
 */
class BookInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'system',
  ];

  /**
   * Tests Book install with pre-existing content type.
   *
   * Tests that Book module can be installed if content type with machine name
   * 'book' already exists.
   */
  public function testBookInstallWithPreexistingContentType(): void {
    // Create a 'book' content type.
    NodeType::create([
      'type' => 'book',
      'name' => 'Book',
    ])->save();
    // Install the Book module. Using the module installer service ensures that
    // all the install rituals, including default and optional configuration
    // import, are performed.
    $status = $this->container->get('module_installer')->install(['book']);
    $this->assertTrue($status, 'Book module installed successfully');
  }

}
