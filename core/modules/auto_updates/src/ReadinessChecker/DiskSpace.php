<?php

namespace Drupal\auto_updates\ReadinessChecker;

/**
 * A readiness checker that ensures there is enough disk space for updates.
 */
class DiskSpace extends FileSystemBase {

  /**
   * Minimum disk space (in bytes) is 1 GB.
   *
   * @todo Determine how much the minimum should be now that we will be using
   *   Composer in https://www.drupal.org/node/3166416.
   */
  protected const MINIMUM_DISK_SPACE = 1073741824;

  /**
   * Gets the free disk space.
   *
   * @param string $path
   *   The path to check.
   *
   * @throws \RuntimeException
   *   Thrown if the call to disk_free_space() fails.
   */
  protected static function getFreeSpace(string $path): float {
    $free_space = disk_free_space($path);
    if ($free_space === FALSE) {
      throw new \RuntimeException('disk_free_space() failed.');
    }
    return $free_space;
  }

  /**
   * Gets the errors if any.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The error messages.
   */
  protected function getErrors(): array {
    $has_valid_root = $this->hasValidRootPath();
    $has_valid_vendor = $this->hasValidVendorPath();
    if (!$has_valid_root && !$has_valid_vendor) {
      return [$this->t('Free disk space cannot be determined because the web root and vendor directories could not be located.')];
    }
    elseif (!$has_valid_root) {
      return [$this->t('Free disk space cannot be determined because the web root directory could not be located.')];
    }
    if (!$has_valid_vendor) {
      return [$this->t('Free disk space cannot be determined because the vendor directory could not be located.')];
    }
    $messages = [];
    $minimum_megabytes = static::MINIMUM_DISK_SPACE / 1000000;
    $root_path = $this->getRootPath();
    $vendor_path = $this->getVendorPath();
    if (!$this->areSameLogicalDisk($root_path, $vendor_path)) {
      // If the root and vendor paths are not on the same logical disk check
      // that each have at least half of the minimum required disk space.
      if (static::getFreeSpace($root_path) < (static::MINIMUM_DISK_SPACE / 2)) {
        $messages[] = $this->t('Drupal root filesystem "@root" has insufficient space. There must be at least @space megabytes free.', [
          '@root' => $root_path,
          '@space' => $minimum_megabytes / 2,
        ]);
      }
      if (static::getFreeSpace($vendor_path) < (static::MINIMUM_DISK_SPACE / 2)) {
        $messages[] = $this->t('Vendor filesystem "@vendor" has insufficient space. There must be at least @space megabytes free.', [
          '@vendor' => $vendor_path,
          '@space' => $minimum_megabytes / 2,
        ]);
      }
    }
    elseif (static::getFreeSpace($root_path) < static::MINIMUM_DISK_SPACE) {
      $messages[] = $this->t('Logical disk "@root" has insufficient space. There must be at least @space megabytes free.', [
        '@root' => $root_path,
        '@space' => $minimum_megabytes,
      ]);
    }
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(): ?ReadinessCheckerResult {
    $errors = $this->getErrors();
    if (empty($errors)) {
      return NULL;
    }
    elseif (count($errors) === 1) {
      $summary = $errors[0];
    }
    else {
      $summary = $this->t('There is not enough disk space to perform an automatic update.');
    }
    return new ReadinessCheckerResult($this, $summary, $errors);
  }

}
