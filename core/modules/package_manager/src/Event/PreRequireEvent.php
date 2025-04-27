<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

/**
 * Event fired before packages are updated to the stage directory.
 */
final class PreRequireEvent extends SandboxValidationEvent {

  use EventWithPackageListTrait;

}
