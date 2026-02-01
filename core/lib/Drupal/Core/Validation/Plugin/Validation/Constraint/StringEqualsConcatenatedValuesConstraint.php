<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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
   * Constructs a StringEqualsConcatenatedValuesConstraint object.
   *
   * @param array|null $options
   *   The options (as associative array) or the value for the default option
   *   (any other type)
   * @param string|null $separator
   *   The separator separating the values.
   * @param array|null $values
   *   The mappings to values to concatenate together.
   * @param array|null $reservedCharacters
   *   Reserved characters — if any — that are to be substituted in each value.
   * @param string|null $reservedCharactersSubstitute
   *   Any reserved characters that will be substituted by this character.
   * @param string $message
   *   The error message if the string does not match.
   * @param array|null $groups
   *   An array of validation groups.
   * @param mixed|null $payload
   *   Domain-specific data attached to a constraint.
   */
  #[HasNamedArguments]
  public function __construct(
    ?array $options = NULL,
    public ?string $separator = NULL,
    public ?array $values = NULL,
    public ?array $reservedCharacters = [],
    public ?string $reservedCharactersSubstitute = NULL,
    public string $message = "Expected '@expected_string', not '@value'. Format: '@expected_format'.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['separator', 'values'];
  }

}
