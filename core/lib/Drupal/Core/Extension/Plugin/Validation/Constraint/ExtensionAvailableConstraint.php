<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
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
   * The error message for a module that is not available.
   *
   * @var string
   */
  public string $moduleNotExistsMessage = "Module '@name' is not available.";

  /**
   * The error message for a theme that is not available.
   *
   * @var string
   */
  public string $themeNotExistsMessage = "Theme '@name' is not available.";

  /**
   * The error message for a profile that is not available.
   *
   * @var string
   */
  public string $profileNotExistsMessage = "Profile '@name' is not available.";

  /**
   * The error message for a module that is not available in the profile.
   *
   * @var string
   */
  public string $couldNotLoadProfileToCheckExtension = "Profile '@profile' could not be loaded to check if the extension '@extension' is available.";

  /**
   * The type of extension to look for. Can be 'module', 'theme' or 'profile'.
   *
   * @var string
   */
  public string $type;

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
