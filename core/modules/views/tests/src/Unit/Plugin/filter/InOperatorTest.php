<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\filter;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\filter\InOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\filter\InOperator.
 */
#[CoversClass(InOperator::class)]
#[Group('views')]
class InOperatorTest extends UnitTestCase {

  /**
   * Tests validate.
   */
  public function testValidate(): void {
    $definition = [
      'title' => 'Is InOperator Test',
      'group' => 'Test',
      'options callback' => '\Drupal\Tests\views\Unit\Plugin\filter\InOperatorTest::validate_options_callback',
    ];
    $filter = new InOperator([], 'in_operator', $definition);
    $filter->value = 'string';
    $filter->operator = 'in';
    $translation_stub = $this->getStringTranslationStub();
    $filter->setStringTranslation($translation_stub);
    $errors = $filter->validate();
    $this->assertSame('The value &#039;string&#039; is not an array for in on filter: ' . $filter->adminLabel(TRUE), (string) $errors[0]);
  }

  /**
   * Views filter callback: Generate the values for the options.
   *
   * Returns 'yes' and 'no' options.
   *
   * @return array
   *   The options for the filter.
   */
  public static function validate_options_callback() {
    return ['Yes', 'No'];
  }

}
