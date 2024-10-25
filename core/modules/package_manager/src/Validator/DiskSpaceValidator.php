<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Utility\Bytes;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates that there is enough free disk space to do stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class DiskSpaceValidator implements EventSubscriberInterface {

  use BaseRequirementValidatorTrait;
  use StringTranslationTrait;

  public function __construct(private readonly PathLocator $pathLocator) {
  }

  /**
   * Wrapper around the disk_free_space() function.
   *
   * @param string $path
   *   The path for which to retrieve the amount of free disk space.
   *
   * @return float
   *   The number of bytes of free space on the disk.
   *
   * @throws \RuntimeException
   *   If the amount of free space could not be determined.
   */
  protected function freeSpace(string $path): float {
    $free_space = disk_free_space($path);
    if ($free_space === FALSE) {
      throw new \RuntimeException("Cannot get disk information for $path.");
    }
    return $free_space;
  }

  /**
   * Wrapper around the stat() function.
   *
   * @param string $path
   *   The path to check.
   *
   * @return mixed[]
   *   The statistics for the path.
   *
   * @throws \RuntimeException
   *   If the statistics could not be determined.
   */
  protected function stat(string $path): array {
    $stat = stat($path);
    if ($stat === FALSE) {
      throw new \RuntimeException("Cannot get information for $path.");
    }
    return $stat;
  }

  /**
   * Checks if two paths are located on the same logical disk.
   *
   * @param string $root
   *   The path of the project root.
   * @param string $vendor
   *   The path of the vendor directory.
   *
   * @return bool
   *   TRUE if the project root and vendor directory are on the same logical
   *   disk, FALSE otherwise.
   */
  protected function areSameLogicalDisk(string $root, string $vendor): bool {
    $root_statistics = $this->stat($root);
    $vendor_statistics = $this->stat($vendor);
    return $root_statistics['dev'] === $vendor_statistics['dev'];
  }

  /**
   * Validates that there is enough free disk space to do stage operations.
   */
  public function validate(PreOperationStageEvent $event): void {
    $root_path = $this->pathLocator->getProjectRoot();
    $vendor_path = $this->pathLocator->getVendorDirectory();
    $messages = [];

    // @todo Make this configurable or set to a different value in
    //   https://www.drupal.org/i/3166416.
    $minimum_mb = 1024;
    $minimum_bytes = Bytes::toNumber($minimum_mb . 'M');

    if (!$this->areSameLogicalDisk($root_path, $vendor_path)) {
      if ($this->freeSpace($root_path) < $minimum_bytes) {
        $messages[] = $this->t('Drupal root filesystem "@root" has insufficient space. There must be at least @space megabytes free.', [
          '@root' => $root_path,
          '@space' => $minimum_mb,
        ]);
      }
      if (is_dir($vendor_path) && $this->freeSpace($vendor_path) < $minimum_bytes) {
        $messages[] = $this->t('Vendor filesystem "@vendor" has insufficient space. There must be at least @space megabytes free.', [
          '@vendor' => $vendor_path,
          '@space' => $minimum_mb,
        ]);
      }
    }
    elseif ($this->freeSpace($root_path) < $minimum_bytes) {
      $messages[] = $this->t('Drupal root filesystem "@root" has insufficient space. There must be at least @space megabytes free.', [
        '@root' => $root_path,
        '@space' => $minimum_mb,
      ]);
    }
    $temp = $this->temporaryDirectory();
    if ($this->freeSpace($temp) < $minimum_bytes) {
      $messages[] = $this->t('Directory "@temp" has insufficient space. There must be at least @space megabytes free.', [
        '@temp' => $temp,
        '@space' => $minimum_mb,
      ]);
    }

    if ($messages) {
      $summary = count($messages) > 1
        ? $this->t("There is not enough disk space to create a stage directory.")
        : NULL;
      $event->addError($messages, $summary);
    }
  }

  /**
   * Returns the path of the system temporary directory.
   *
   * @return string
   *   The absolute path of the system temporary directory.
   */
  protected function temporaryDirectory(): string {
    return FileSystem::getOsTemporaryDirectory();
  }

}
