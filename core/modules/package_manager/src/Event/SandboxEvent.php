<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

use Drupal\package_manager\SandboxManagerBase;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for all events related to the life cycle of the stage.
 */
abstract class SandboxEvent extends Event {

  /**
   * Constructs a StageEvent object.
   *
   * @param \Drupal\package_manager\SandboxManagerBase $sandboxManager
   *   The stage which fired this event.
   */
  public function __construct(public readonly SandboxManagerBase $sandboxManager) {
  }

}
