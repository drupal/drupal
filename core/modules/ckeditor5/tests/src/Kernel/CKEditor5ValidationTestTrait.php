<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterFormatInterface;

/**
 * Defines a trait for testing CKEditor 5 validity.
 */
trait CKEditor5ValidationTestTrait {

  /**
   * Decorator for CKEditor5::validatePair() that returns an assertable array.
   *
   * @param \Drupal\editor\EditorInterface $text_editor
   *   The paired text editor to validate.
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The paired text format to validate.
   * @param bool $all_compatibility_problems
   *   Only fundamental compatibility violations are returned unless TRUE.
   *
   * @return array
   *   An array with property paths as keys and violation messages as values.
   *
   * @see \Drupal\ckeditor5\Plugin\Editor\CKEditor5::validatePair
   */
  private function validatePairToViolationsArray(EditorInterface $text_editor, FilterFormatInterface $text_format, bool $all_compatibility_problems): array {
    $violations = CKEditor5::validatePair($text_editor, $text_format, $all_compatibility_problems);
    $actual_violations = [];
    foreach ($violations as $violation) {
      if (!isset($actual_violations[$violation->getPropertyPath()])) {
        $actual_violations[$violation->getPropertyPath()] = (string) $violation->getMessage();
      }
      else {
        // Transform value from string to array.
        if (is_string($actual_violations[$violation->getPropertyPath()])) {
          $actual_violations[$violation->getPropertyPath()] = (array) $actual_violations[$violation->getPropertyPath()];
        }
        // And append.
        $actual_violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
      }
    }
    return $actual_violations;
  }

}
