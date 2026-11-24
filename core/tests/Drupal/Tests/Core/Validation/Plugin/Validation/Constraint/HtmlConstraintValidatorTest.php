<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\HtmlConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\HtmlConstraintValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the Html constraint validator.
 */
#[CoversClass(HtmlConstraintValidator::class)]
#[Group('Validation')]
class HtmlConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests the violations reported for an HTML string.
   *
   * @param string $html
   *   The html string to test.
   * @param array $errors
   *   An array of errors to expect.
   * @param string $mode
   *   Set mode to document or fragment.
   */
  #[DataProvider('provideTestValidate')]
  public function testValidate(string $html, array $errors, string $mode): void {
    $violations = [];
    $context = $this->createStub(ExecutionContextInterface::class);
    $context->method('addViolation')
      ->willReturnCallback(function (string $message) use (&$violations): void {
        $violations[] = $message;
      });

    $validator = new HtmlConstraintValidator();
    $validator->validateInContext($html, new HtmlConstraint($mode), $context);

    $this->assertCount(count($errors), $violations);
    foreach ($violations as $violation) {
      $this->assertTrue(in_array($violation, $errors, TRUE));
    }
  }

  /**
   * Provides data for testValidate().
   *
   * @return array[]
   *   The test cases.
   */
  public static function provideTestValidate(): array {
    return [
      'valid_document' => [
        'html' => "<!doctype html>\n<html>\n<head></head><body><p>test</p></body></html>",
        'errors' => [],
        'mode' => 'document',
      ],
      'invalid_document' => [
        'html' => "<!doctype html>\n<html>\n<head></head><body><p>test</a></body></html>",
        'errors' => ['Line 0, Col 0: Could not find closing tag for a'],
        'mode' => 'document',
      ],
      'valid_document_parsed_as_fragment' => [
        'html' => "<!doctype html>\n<html>\n<head></head><body><p>test</p></body></html>",
        // cspell:disable-next-line
        'errors' => ['Line 0, Col 0: Illegal placement of DOCTYPE tag. Ignoring: html'],
        'mode' => 'fragment',
      ],
      'valid_fragment' => [
        'html' => '<p>test</p>',
        'errors' => [],
        'mode' => 'fragment',
      ],
      'invalid_fragment' => [
        'html' => '<p>test</a>',
        'errors' => ['Line 0, Col 0: Could not find closing tag for a'],
        'mode' => 'fragment',
      ],
      'valid_fragment_parsed_as_document' => [
        'html' => '<p>test</p>',
        'errors' => ['Line 0, Col 0: No DOCTYPE specified.'],
        'mode' => 'document',
      ],
    ];
  }

}
