<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks a string consists of specific values found in the mapping.
 */
#[Constraint(
  id: 'StringEqualsConcatenatedValues',
  label: new TranslatableMarkup('String consists of specific values.', [], ['context' => 'Validation'])
)]
class StringEqualsConcatenatedValuesConstraint extends SymfonyConstraint {

  /**
   * The error message if the string does not match.
   *
   * @var string
   */
  public string $message = "Expected '@expected_string', not '@value'. Format: '@expected_format'.";

  /**
   * The separator separating the values.
   *
   * @var string
   */
  public string $separator;

  /**
   * Reserved characters — if any — that are to be substituted in each value.
   *
   * @var string[]
   */
  public array $reservedCharacters = [];

  /**
   * Any reserved characters that will be substituted by this character.
   *
   * @var ?string
   */
  public ?string $reservedCharactersSubstitute;

  /**
   * The mappings to values to concatenate together.
   *
   * @var array
   */
  public array $values;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['separator', 'values'];
  }

}
