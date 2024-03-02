<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Drupal\views_ui\ViewUI;

/**
 * @group views_ui
 */
class ViewsUiObjectTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views', 'views_ui'];

  /**
   * Tests serialization of the ViewUI object.
   */
  public function testSerialization(): void {
    $storage = new View([], 'view');
    $executable = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->setConstructorArgs([$storage])
      ->getMock();
    $storage->set('executable', $executable);

    $view_ui = new ViewUI($storage);

    // Make sure the executable is returned before serializing.
    $this->assertInstanceOf(ViewExecutable::class, $view_ui->getExecutable());

    $serialized = serialize($view_ui);

    // Make sure the ViewExecutable class is not found in the serialized string.
    $this->assertStringNotContainsString('"Drupal\views\ViewExecutable"', $serialized);

    $unserialized = unserialize($serialized);
    $this->assertInstanceOf(ViewUI::class, $unserialized);
    // Ensure serialization magic repopulated the object with the executable.
    $this->assertInstanceOf(ViewExecutable::class, $unserialized->getExecutable());
  }

}
