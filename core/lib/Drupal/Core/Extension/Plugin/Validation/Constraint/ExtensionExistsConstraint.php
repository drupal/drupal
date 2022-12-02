<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the value is the name of an installed extension.
 *
 * @Constraint(
 *   id = "ExtensionExists",
 *   label = @Translation("Extension exists", context = "Validation")
 * )
 */
class ExtensionExistsConstraint extends Constraint {

  /**
   * The error message for a non-existent module.
   *
   * @var string
   */
  public string $moduleMessage = "Module '@name' is not installed.";

  /**
   * The error message for a non-existent theme.
   *
   * @var string
   */
  public string $themeMessage = "Theme '@name' is not installed.";

  /**
   * The type of extension to look for. Can be 'module' or 'theme'.
   *
   * @var string
   */
  public string $type;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'type';
  }

}
