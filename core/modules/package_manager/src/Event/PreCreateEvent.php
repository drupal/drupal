<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

use Drupal\package_manager\ImmutablePathList;
use Drupal\package_manager\StageBase;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

/**
 * Event fired before a stage directory is created.
 */
final class PreCreateEvent extends PreOperationStageEvent {

  /**
   * The list of paths to exclude from the stage directory.
   *
   * @var \Drupal\package_manager\ImmutablePathList
   */
  public readonly ImmutablePathList $excludedPaths;

  /**
   * Constructs a PreCreateEvent object.
   *
   * @param \Drupal\package_manager\StageBase $stage
   *   The stage which fired this event.
   * @param \PhpTuf\ComposerStager\API\Path\Value\PathListInterface $excluded_paths
   *   The list of paths to exclude. These will not be copied into the stage
   *   directory when it is created.
   */
  public function __construct(StageBase $stage, PathListInterface $excluded_paths) {
    parent::__construct($stage);
    $this->excludedPaths = new ImmutablePathList($excluded_paths);
  }

}
