<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ValidRegexConstraint.
 */
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class ValidRegexConstraintTest extends KernelTestBase {

  /**
   * Tests regex values.
   *
   * @param string $regex
   *   The value to test.
   * @param string|null $message
   *   The expected error message, if any.
   */
  #[DataProvider('validRegexConstraintDataProvider')]
  public function testValidRegexConstraint(string $regex, ?string $message = NULL): void {
    $definition = DataDefinition::create('string')
      ->addConstraint('ValidRegex');
    $test_string = $this->container->get('typed_data_manager')->create($definition);

    $test_string->setValue($regex);
    $violations = $test_string->validate();
    if (!$message) {
      $this->assertCount(0, $violations);
      return;
    }
    $this->assertCount(1, $violations);
    $this->assertSame($message, (string) $violations->get(0)->getMessage());

  }

  /**
   * Provides data for testValidRegexConstraint().
   *
   * @return array[]
   *   The test cases.
   */
  public static function validRegexConstraintDataProvider(): array {
    return [
      'invalid no ending delimiter' => [
        'regex' => '/test',
        'message' => 'The value "/test" is not a valid regular expression: Internal error.',
      ],
      'invalid bad character class' => [
        'regex' => '%[0-9%',
        'message' => 'The value "%[0-9%" is not a valid regular expression: Internal error.',
      ],
      'invalid no delimiters' => [
        'regex' => 'no_delimiters',
        'message' => 'The value "no_delimiters" is not a valid regular expression: Internal error.',
      ],
      'valid simple regex' => [
        'regex' => '/test/',
      ],
      'valid complex regex' => [
        'regex' => '%^<\s*(/\s*)?([a-zA-Z0-9\-]+)\s*([^>]*)>?|(<!--.*?-->)$%',
      ],
      'valid with hashtag delimiters' => [
        'regex' => '#[0-9].*#',
      ],
    ];
  }

}
