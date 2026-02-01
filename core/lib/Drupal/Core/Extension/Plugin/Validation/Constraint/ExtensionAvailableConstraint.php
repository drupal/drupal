<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Check if an extension (module, theme, or profile) is available.
 */
#[Constraint(
  id: 'ExtensionAvailable',
  label: new TranslatableMarkup('Extension is available', [], ['context' => 'Validation'])
)]
class ExtensionAvailableConstraint extends SymfonyConstraint {

  /**
   * The type of extension to look for. Can be 'module', 'theme' or 'profile'.
   *
   * @var string
   */
  public string $type;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $type = NULL,
    public string $moduleNotExistsMessage = "Module '@name' is not available.",
    public string $themeNotExistsMessage = "Theme '@name' is not available.",
    public string $profileNotExistsMessage = "Profile '@name' is not available.",
    public string $couldNotLoadProfileToCheckExtension = "Profile '@profile' could not be loaded to check if the extension '@extension' is available.",
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
