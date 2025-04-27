<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\PathLocator;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoUnsupportedLinksExistInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flags errors if unsupported symbolic links are detected.
 *
 * @see https://github.com/php-tuf/composer-stager/tree/develop/src/Domain/Service/Precondition#symlinks
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class SymlinkValidator implements EventSubscriberInterface {

  use BaseRequirementValidatorTrait;

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly NoUnsupportedLinksExistInterface $precondition,
    private readonly PathFactoryInterface $pathFactory,
    private readonly PathListFactoryInterface $pathListFactory,
  ) {}

  /**
   * Flags errors if the project root or stage directory contain symbolic links.
   */
  public function validate(SandboxValidationEvent $event): void {
    if ($event instanceof PreRequireEvent) {
      // We don't need to check symlinks again during PreRequireEvent; this was
      // already just validated during PreCreateEvent.
      return;
    }
    $active_dir = $this->pathFactory->create($this->pathLocator->getProjectRoot());

    // The precondition requires us to pass both an active and stage directory,
    // so if the stage hasn't been created or claimed yet, use the directory
    // that contains this file, which contains only a few files and no symlinks,
    // as the stage directory. The precondition itself doesn't care if the
    // directory actually exists or not.
    $stage_dir = __DIR__;
    if ($event->sandboxManager->sandboxDirectoryExists()) {
      $stage_dir = $event->sandboxManager->getSandboxDirectory();
    }
    $stage_dir = $this->pathFactory->create($stage_dir);

    // Return early if no excluded paths were collected because this validator
    // is dependent on knowing which paths to exclude when searching for
    // symlinks.
    // @see \Drupal\package_manager\StatusCheckTrait::runStatusCheck()
    if ($event->excludedPaths === NULL) {
      return;
    }

    // The list of excluded paths is immutable, but the precondition may need to
    // mutate it, so convert it back to a normal, mutable path list.
    $exclusions = $this->pathListFactory->create(...$event->excludedPaths->getAll());

    try {
      $this->precondition->assertIsFulfilled($active_dir, $stage_dir, $exclusions);
    }
    catch (PreconditionException $e) {
      $event->addErrorFromThrowable($e);
    }
  }

}
