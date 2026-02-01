<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that the value is the name of an installed extension.
 */
#[Constraint(
  id: 'ExtensionExists',
  label: new TranslatableMarkup('Extension exists', [], ['context' => 'Validation'])
)]
class ExtensionExistsConstraint extends SymfonyConstraint {

  /**
   * The type of extension to look for. Can be 'module' or 'theme'.
   *
   * @var string
   */
  public string $type;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $type = NULL,
    public string $moduleMessage = "Module '@name' is not installed.",
    public string $themeMessage = "Theme '@name' is not installed.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->type = $type ?? $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'type';
  }

}
