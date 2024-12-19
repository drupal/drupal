<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UnitTestCase;

/**
 * Contains helpful methods for testing stage validators.
 *
 * @internal
 */
trait ValidationTestTrait {

  /**
   * Asserts two validation result sets are equal.
   *
   * This assertion is sensitive to the order of results. For example,
   * ['a', 'b'] is not equal to ['b', 'a'].
   *
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   * @param \Drupal\package_manager\ValidationResult[] $actual_results
   *   The actual validation results.
   * @param \Drupal\package_manager\PathLocator|null $path_locator
   *   (optional) The path locator (when this trait is used in unit tests).
   * @param string|null $stage_dir
   *   (optional) The stage directory.
   */
  protected function assertValidationResultsEqual(array $expected_results, array $actual_results, ?PathLocator $path_locator = NULL, ?string $stage_dir = NULL): void {
    if ($path_locator) {
      assert(is_a(get_called_class(), UnitTestCase::class, TRUE));
    }
    $expected_results = array_map(
      function (array $result) use ($path_locator, $stage_dir): array {
        $result['messages'] = $this->resolvePlaceholdersInArrayValuesWithRealPaths($result['messages'], $path_locator, $stage_dir);
        return $result;
      },
      $this->getValidationResultsAsArray($expected_results)
    );
    $actual_results = $this->getValidationResultsAsArray($actual_results);

    self::assertSame($expected_results, $actual_results);
  }

  /**
   * Resolves <PROJECT_ROOT>, <VENDOR_DIR>, <STAGE_ROOT>, <STAGE_ROOT_PARENT>.
   *
   * @param array $subject
   *   An array with arbitrary keys, and values potentially containing the
   *   placeholders <PROJECT_ROOT>, <VENDOR_DIR>, <STAGE_ROOT>, or
   *   <STAGE_ROOT_PARENT>. <STAGE_DIR> is the placeholder for $stage_dir, if
   *   passed.
   * @param \Drupal\package_manager\PathLocator|null $path_locator
   *   (optional) The path locator (when this trait is used in unit tests).
   * @param string|null $stage_dir
   *   (optional) The stage directory.
   *
   * @return array
   *   The same array, with unchanged keys, and with the placeholders resolved.
   */
  protected function resolvePlaceholdersInArrayValuesWithRealPaths(array $subject, ?PathLocator $path_locator = NULL, ?string $stage_dir = NULL): array {
    if (!$path_locator) {
      // Only kernel and browser tests have $this->container.
      assert($this instanceof KernelTestBase || $this instanceof BrowserTestBase);
      $path_locator = $this->container->get(PathLocator::class);
    }
    $subject = str_replace(
      ['<PROJECT_ROOT>', '<VENDOR_DIR>', '<STAGE_ROOT>', '<STAGE_ROOT_PARENT>'],
      [$path_locator->getProjectRoot(), $path_locator->getVendorDirectory(), $path_locator->getStagingRoot(), dirname($path_locator->getStagingRoot())],
      $subject
    );
    if ($stage_dir) {
      $subject = str_replace(['<STAGE_DIR>'], [$stage_dir], $subject);
    }
    foreach ($subject as $message) {
      if (str_contains($message, '<STAGE_DIR>')) {
        throw new \LogicException("No stage directory passed to replace '<STAGE_DIR>' in message '$message'");
      }
    }
    return $subject;
  }

  /**
   * Gets an array representation of validation results for easy comparison.
   *
   * @param \Drupal\package_manager\ValidationResult[] $results
   *   An array of validation results.
   *
   * @return array
   *   An array of validation results details:
   *   - severity: (int) The severity code.
   *   - messages: (array) An array of strings.
   *   - summary: (string|null) A summary string if there is one or NULL if not.
   */
  protected function getValidationResultsAsArray(array $results): array {
    $string_translation_stub = NULL;
    if (is_a(get_called_class(), UnitTestCase::class, TRUE)) {
      assert($this instanceof UnitTestCase);
      $string_translation_stub = $this->getStringTranslationStub();
    }
    return array_values(array_map(static function (ValidationResult $result) use ($string_translation_stub) {
      $messages = array_map(static function ($message) use ($string_translation_stub): string {
        // Support data providers in unit tests using TranslatableMarkup.
        if ($message instanceof TranslatableMarkup && is_a(get_called_class(), UnitTestCase::class, TRUE)) {
          // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
          $message = new TranslatableMarkup($message->getUntranslatedString(), $message->getArguments(), $message->getOptions(), $string_translation_stub);
        }
        return (string) $message;
      }, $result->messages);

      $summary = $result->summary;
      if ($summary !== NULL) {
        $summary = (string) $result->summary;
      }

      return [
        'severity' => $result->severity,
        'messages' => $messages,
        'summary' => $summary,
      ];
    }, $results));
  }

}
