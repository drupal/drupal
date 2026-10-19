<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\argument_validator;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument\Standard;
use Drupal\views\Plugin\views\argument_validator\None;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests the None argument validator.
 */
#[CoversClass(None::class)]
#[Group('views')]
class NoneTest extends UnitTestCase {

  /**
   * Tests validation of the None argument.
   */
  #[TestWith([TRUE, 3, ['numeric' => TRUE], []], 'numeric value')]
  #[TestWith([FALSE, 'string', ['numeric' => TRUE], []], 'nonnumeric value')]
  #[TestWith([TRUE, 'string', ['numeric' => TRUE], ['break_phrase' => TRUE]], 'break phrase bypass')]
  #[TestWith([TRUE, 'string', ['numeric' => TRUE], ['break_phrase' => FALSE]], 'false break phrase bypass')]
  #[TestWith([FALSE, NULL, [], []], 'null argument')]
  #[TestWith([FALSE, '', [], []], 'empty argument')]
  #[TestWith([TRUE, NULL, [], ['must_not_be' => TRUE]], 'must not be with null')]
  #[TestWith([FALSE, '', [], ['must_not_be' => TRUE]], 'must not be with empty string')]
  public function testValidateNone(bool $expected, mixed $argument, array $definition, array $options): void {
    $argument_handler = new Standard([], 'standard', []);
    $argument_handler->definition = $definition;
    $argument_handler->options = $options;

    $validator = new None([], 'none_test', []);
    $validator->setArgument($argument_handler);
    $this->assertSame($expected, $validator->validateArgument($argument));
  }

}
