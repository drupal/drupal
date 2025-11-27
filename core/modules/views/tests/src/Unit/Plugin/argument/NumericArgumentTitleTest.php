<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\argument;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument\NumericArgument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the title() method of NumericArgument.
 */
#[CoversClass(NumericArgument::class)]
#[Group('Views')]
class NumericArgumentTitleTest extends UnitTestCase {

  /**
   * Tests title() method with single numeric value.
   */
  public function testTitleWithSingleValue(): void {
    $numeric_argument = $this->createNumericArgument();
    $numeric_argument->argument = '123';

    $result = $numeric_argument->title();
    $this->assertEquals('123', $result);
  }

  /**
   * Tests title() method with break_phrase enabled and OR operator.
   */
  public function testTitleWithBreakPhraseOrOperator(): void {
    $numeric_argument = $this->createNumericArgument();
    $numeric_argument->argument = '123+456+789';
    $numeric_argument->options['break_phrase'] = TRUE;

    $result = $numeric_argument->title();
    $this->assertEquals('123 + 456 + 789', $result);
  }

  /**
   * Tests title() method with break_phrase enabled token value.
   */
  public function testTitleWithBreakPhraseAndTokenValue(): void {
    $numeric_argument = $this->createNumericArgument();
    $numeric_argument->argument = '[token:value], string';
    $numeric_argument->options['break_phrase'] = TRUE;

    $result = $numeric_argument->title();
    $this->assertEquals('Uncategorized', (string) $result);
  }

  /**
   * Creates a NumericArgument instance for testing.
   *
   * @return \Drupal\views\Plugin\views\argument\NumericArgument
   *   A NumericArgument instance.
   */
  protected function createNumericArgument(): NumericArgument {
    $numeric_argument = new NumericArgument([], 'numeric', []);

    $translation_stub = $this->getStringTranslationStub();
    $numeric_argument->setStringTranslation($translation_stub);

    // Set default options
    $numeric_argument->options = [
      'break_phrase' => FALSE,
      'not' => FALSE,
    ];

    // Set default definition
    $numeric_argument->definition = [];

    return $numeric_argument;
  }

}
