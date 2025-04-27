<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\package_manager\ImmutablePathList;
use Drupal\package_manager\SandboxManagerBase;
use Drupal\package_manager\ValidationResult;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

/**
 * Event fired to check the status of the system to use Package Manager.
 */
final class StatusCheckEvent extends SandboxValidationEvent {

  /**
   * The paths to exclude, or NULL if there was an error collecting them.
   *
   * @var \Drupal\package_manager\ImmutablePathList|null
   *
   * @see ::__construct()
   */
  public readonly ?ImmutablePathList $excludedPaths;

  /**
   * Constructs a StatusCheckEvent object.
   *
   * @param \Drupal\package_manager\SandboxManagerBase $sandboxManager
   *   The stage which fired this event.
   * @param \PhpTuf\ComposerStager\API\Path\Value\PathListInterface|\Throwable $excluded_paths
   *   The list of paths to exclude or, if an error occurred while they were
   *   being collected, the throwable from that error. If this is a throwable,
   *   it will be converted to a validation error.
   */
  public function __construct(SandboxManagerBase $sandboxManager, PathListInterface|\Throwable $excluded_paths) {
    parent::__construct($sandboxManager);

    // If there was an error collecting the excluded paths, convert it to a
    // validation error so we can still run status checks that don't need to
    // examine the list of excluded paths.
    if ($excluded_paths instanceof \Throwable) {
      $this->addErrorFromThrowable($excluded_paths);
      $excluded_paths = NULL;
    }
    else {
      $excluded_paths = new ImmutablePathList($excluded_paths);
    }
    $this->excludedPaths = $excluded_paths;
  }

  /**
   * Adds warning information to the event.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   One or more warning messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   A summary of warning messages. Must be passed if there is more than one
   *   message.
   */
  public function addWarning(array $messages, ?TranslatableMarkup $summary = NULL): void {
    $this->addResult(ValidationResult::createWarning($messages, $summary));
  }

  /**
   * {@inheritdoc}
   */
  public function addResult(ValidationResult $result): void {
    // Override the parent to also allow warnings.
    $this->results[] = $result;
  }

}
