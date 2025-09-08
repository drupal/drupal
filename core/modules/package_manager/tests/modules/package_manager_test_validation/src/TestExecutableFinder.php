<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_validation;

use Drupal\Core\State\StateInterface;
use Drupal\package_manager\TranslatableStringAdapter;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * A test-only executable finder that can be rigged to throw an exception.
 */
final class TestExecutableFinder implements ExecutableFinderInterface {

  public function __construct(
    private readonly ExecutableFinderInterface $decorated,
    private readonly StateInterface $state,
  ) {}

  /**
   * Sets up an exception to throw when trying to find a specific executable.
   *
   * @param string $name
   *   The name of an executable to look for.
   */
  public static function throwFor(string $name): void {
    \Drupal::state()->set("throw for $name", TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $name): string {
    if ($this->state->get("throw for $name")) {
      $message = new TranslatableStringAdapter("$name is not a thing");
      throw new LogicException($message);
    }
    return $this->decorated->find($name);
  }

}
