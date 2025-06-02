<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\RsyncIsAvailableInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * Allows certain Composer Stager preconditions to be bypassed.
 *
 * Only certain preconditions can be bypassed; this class implements all of
 * those interfaces, and only accepts them in its constructor.
 *
 * @internal
 *    This is an internal part of Package Manager and may be changed or removed
 *    at any time without warning. External code should not interact with this
 *    class.
 */
final class DirectWritePreconditionBypass implements ActiveAndStagingDirsAreDifferentInterface, RsyncIsAvailableInterface {

  use StringTranslationTrait;

  /**
   * Whether or not the decorated precondition is being bypassed.
   *
   * @var bool
   */
  private static bool $isBypassed = FALSE;

  public function __construct(
    private readonly ActiveAndStagingDirsAreDifferentInterface|RsyncIsAvailableInterface $decorated,
  ) {}

  /**
   * Bypasses the decorated precondition.
   */
  public static function activate(): void {
    static::$isBypassed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): TranslatableInterface {
    return $this->decorated->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableInterface {
    return $this->decorated->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMessage(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): TranslatableInterface {
    if (static::$isBypassed) {
      return new TranslatableStringAdapter('This precondition has been skipped because it is not needed in direct-write mode.');
    }
    return $this->decorated->getStatusMessage($activeDir, $stagingDir, $exclusions, $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): bool {
    if (static::$isBypassed) {
      return TRUE;
    }
    return $this->decorated->isFulfilled($activeDir, $stagingDir, $exclusions, $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function assertIsFulfilled(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    if (static::$isBypassed) {
      return;
    }
    $this->decorated->assertIsFulfilled($activeDir, $stagingDir, $exclusions, $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaves(): array {
    return [$this];
  }

}
