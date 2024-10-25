<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

/**
 * Defines a path list that cannot be changed.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ImmutablePathList implements PathListInterface {

  public function __construct(private readonly PathListInterface $decorated) {}

  /**
   * {@inheritdoc}
   */
  public function add(string ...$paths): never {
    throw new \LogicException('Immutable path lists cannot be changed.');
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(): array {
    return $this->decorated->getAll();
  }

}
