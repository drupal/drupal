<?php

declare(strict_types=1);

namespace Drupal\destructable_test;

use Drupal\Core\DestructableInterface;

/**
 * Manages a semaphore file and performs an action upon destruction.
 */
final class Destructable implements DestructableInterface {

  /**
   * Semaphore filename.
   *
   * @var string
   */
  protected string $semaphore;

  /**
   * Set the destination for the semaphore file.
   *
   * @param string $semaphore
   *   Temporary file to set a semaphore flag.
   */
  public function setSemaphore(string $semaphore): void {
    $this->semaphore = $semaphore;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    sleep(3);
    file_put_contents($this->semaphore, 'ran');
  }

}
