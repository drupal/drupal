<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Package\PackageInterface;

/**
 * A collection with packages to unpack.
 *
 * @internal
 */
final class UnpackCollection implements \Iterator, \Countable {

  /**
   * The queue of packages to unpack.
   *
   * @var \Composer\Package\PackageInterface[]
   */
  private array $packagesToUnpack = [];

  /**
   * The list of packages that have been unpacked.
   *
   * @var array<string, \Composer\Package\PackageInterface>
   */
  private array $unpackedPackages = [];

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    reset($this->packagesToUnpack);
  }

  /**
   * {@inheritdoc}
   */
  public function current(): PackageInterface|false {
    return current($this->packagesToUnpack);
  }

  /**
   * {@inheritdoc}
   */
  public function key(): ?string {
    return key($this->packagesToUnpack);
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    next($this->packagesToUnpack);
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return current($this->packagesToUnpack) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return count($this->packagesToUnpack);
  }

  /**
   * Adds a package to the queue of packages to unpack.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to add to the queue.
   */
  public function add(PackageInterface $package): self {
    $this->packagesToUnpack[$package->getUniqueName()] = $package;
    return $this;
  }

  /**
   * Marks a package as unpacked.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that has been unpacked.
   */
  public function markPackageUnpacked(PackageInterface $package): void {
    $this->unpackedPackages[$package->getUniqueName()] = $package;
  }

  /**
   * Checks if a package has been unpacked, or it's queued for unpacking.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to check.
   *
   * @return bool
   *   TRUE if the package has been unpacked.
   */
  public function isUnpacked(PackageInterface $package): bool {
    return isset($this->unpackedPackages[$package->getUniqueName()]);
  }

}
