<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_validation;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * A test-only executable finder that can be rigged to throw an exception.
 */
final class TestExecutableFinder implements ExecutableFinderInterface {

  public function __construct(
    private readonly ExecutableFinderInterface $decorated,
  ) {}

  /**
   * Throws an exception when finding a specific executable, for testing.
   *
   * @param string $name
   *   The name of the executable for which ::find() will throw an exception.
   */
  public static function throwFor(string $name): void {
    \Drupal::keyValue('package_manager_test.executable_errors')
      ->set($name, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $name): string {
    $should_throw = \Drupal::keyValue('package_manager_test.executable_errors')
      ->get($name);

    if ($should_throw) {
      throw new LogicException(new class () implements TranslatableInterface {

        /**
         * {@inheritdoc}
         */
        public function trans(?TranslatorInterface $translator = NULL, ?string $locale = NULL): string {
          return 'Not found!';
        }

        /**
         * {@inheritdoc}
         */
        public function __toString(): string {
          return $this->trans();
        }

      });
    }
    return $this->decorated->find($name);
  }

}
