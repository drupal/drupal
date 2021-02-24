<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Composer\Autoload\ClassLoader;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A readiness checker that ensures there is enough disk space for updates.
 */
class DiskSpace implements ReadinessCheckerInterface {

  use StringTranslationTrait;

  /**
   * The root file path.
   *
   * @var string
   */
  protected $rootPath;

  /**
   * The path of the vendor folder.
   *
   * @var string
   */
  protected $vendorDir;

  /**
   * Minimum disk space (in bytes) is 1 GB.
   *
   * @todo Determine how much the minimum should be now that we will be using
   *   Composer in https://www.drupal.org/node/3166416.
   */
  protected const MINIMUM_DISK_SPACE = 1073741824;

  /**
   * Constructs a DiskSpace object.
   *
   * @param string $app_root
   *   The app root.
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   The class loader service.
   */
  public function __construct(string $app_root, ClassLoader $class_loader) {
    $this->rootPath = $app_root;
    $class_loader_reflection = new \ReflectionObject($class_loader);
    $this->vendorDir = dirname($class_loader_reflection->getFileName(), 2);
  }

  /**
   * Determines if the root and vendor directories are on the same logical disk.
   *
   * @param string $root
   *   Root file path.
   * @param string $vendor
   *   Vendor file path.
   *
   * @return bool
   *   TRUE if they are on the same logical disk, FALSE otherwise.
   *
   * @throws \RuntimeException
   *   Thrown if an error is found trying get the directory information.
   */
  protected function areSameLogicalDisk(string $root, string $vendor): bool {
    $root_statistics = stat($root);
    $vendor_statistics = stat($vendor);
    if ($root_statistics === FALSE || $vendor_statistics === FALSE) {
      throw new \RuntimeException('Unable to determine if the root and vendor directories are on the same logical disk.');
    }
    return $root_statistics['dev'] === $vendor_statistics['dev'];
  }

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
    $has_valid_root = file_exists(implode(DIRECTORY_SEPARATOR, [$this->rootPath, 'core', 'core.api.php']));
    $has_valid_vendor = file_exists($this->vendorDir . DIRECTORY_SEPARATOR . 'autoload.php');
    if (!$has_valid_root && !$has_valid_vendor) {
      return [$this->t('Free disk space cannot be determined because the web root and vendor directories could not be located.')];
    }
    elseif (!$has_valid_root) {
      return [$this->t('Free disk space cannot be determined because the web root directory could not be located.')];
    }
    elseif (!$has_valid_vendor) {
      return [$this->t('Free disk space cannot be determined because the vendor directory could not be located.')];
    }
    $messages = [];
    $minimum_megabytes = static::MINIMUM_DISK_SPACE / 1000000;
    if (!$this->areSameLogicalDisk($this->rootPath, $this->vendorDir)) {
      // If the root and vendor paths are not on the same logical disk check
      // that each have at least half of the minimum required disk space.
      if (static::getFreeSpace($this->rootPath) < (static::MINIMUM_DISK_SPACE / 2)) {
        $messages[] = $this->t('Drupal root filesystem "@root" has insufficient space. There must be at least @space MB free.', [
          '@root' => $this->rootPath,
          '@space' => $minimum_megabytes / 2,
        ]);
      }
      if (static::getFreeSpace($this->vendorDir) < (static::MINIMUM_DISK_SPACE / 2)) {
        $messages[] = $this->t('Vendor filesystem "@vendor" has insufficient space. There must be at least @space MB free.', [
          '@vendor' => $this->vendorDir,
          '@space' => $minimum_megabytes / 2,
        ]);
      }
    }
    elseif (static::getFreeSpace($this->rootPath) < static::MINIMUM_DISK_SPACE) {
      $messages[] = $this->t('Logical disk "@root" has insufficient space. There must be at least @space MB free.', [
        '@root' => $this->rootPath,
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
    $summary = count($errors) === 1 ? $errors[0] : $this->t('There is not enough disk space to perform an automatic update.');
    return new ReadinessCheckerResult($this, $summary, $errors, NULL, []);
  }

}
