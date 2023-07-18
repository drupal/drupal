<?php

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests invocation of services performing deferred tasks after response flush.
 *
 * @see \Drupal\Core\DestructableInterface
 *
 * @group Http
 */
class DestructableServiceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'destructable_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testDestructableServiceExecutionOrder(): void {
    $file_system = $this->container->get('file_system');
    assert($file_system instanceof FileSystemInterface);
    $semaphore = $file_system
      ->tempnam($file_system->getTempDirectory(), 'destructable_semaphore');
    $this->drupalGet(Url::fromRoute('destructable', [], ['query' => ['semaphore' => $semaphore]]));
    // This should be false as the response should flush before running the
    // test service.
    $this->assertEmpty(file_get_contents($semaphore), 'Destructable service did not run when response flushed to client.');
    // The destructable service will sleep for 3 seconds, then run.
    // To ensure no race conditions on slow test runners, wait another 3s.
    sleep(6);
    $this->assertTrue(file_get_contents($semaphore) === 'ran', 'Destructable service did run.');
  }

}
