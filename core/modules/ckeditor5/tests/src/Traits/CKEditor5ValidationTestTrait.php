<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Traits;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Provides methods to test CKEditor 5 validation.
 */
trait CKEditor5ValidationTestTrait {

  /**
   * Asserts CKEditor5 validation errors match an expected array of strings.
   */
  protected function assertExpectedCkeditor5Violations(array $expected = []): void {
    $this->assertSame($expected, array_map(
      static fn (ConstraintViolation $v) => (string) $v->getMessage(),
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
  }

}
