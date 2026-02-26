<?php

declare(strict_types=1);

namespace Drupal\Core\Extension\Requirement;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The requirements severity enum.
 */
enum RequirementSeverity: int {

  /*
   * Informational message only.
   */
  case Info = -1;

  /*
   * Requirement successfully met.
   */
  case OK = 0;

  /*
   * Warning condition; proceed but flag warning.
   */
  case Warning = 1;

  /*
   * Error condition; abort installation.
   */
  case Error = 2;

  /**
   * Returns the translated title of the severity.
   */
  public function title(): TranslatableMarkup {
    return match ($this) {
      self::Info => new TranslatableMarkup('Checked'),
      self::OK => new TranslatableMarkup('OK'),
      self::Warning => new TranslatableMarkup('Warnings found'),
      self::Error => new TranslatableMarkup('Errors found'),
    };
  }

  /**
   * Returns the status of the severity.
   *
   * This string representation can be used as an array key when grouping
   * requirements checks by severity, or in other places where the int-backed
   * value is not appropriate.
   */
  public function status(): string {
    return match ($this) {
      self::Info => 'checked',
      self::OK => 'ok',
      self::Warning => 'warning',
      self::Error => 'error',
    };

  }

  /**
   * Determines the most severe requirement in a list of requirements.
   *
   * @param array<string, array{'title': \Drupal\Core\StringTranslation\TranslatableMarkup, 'value': mixed, description: \Drupal\Core\StringTranslation\TranslatableMarkup, 'severity': \Drupal\Core\Extension\Requirement\RequirementSeverity}> $requirements
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements(), hook_runtime_requirements(),
   *   hook_update_requirements(), and
   *   \Drupal\Core\Extension\InstallRequirementsInterface.
   *
   * @return \Drupal\Core\Extension\Requirement\RequirementSeverity
   *   The most severe requirement.
   *
   * @see \Drupal\Core\Extension\InstallRequirementsInterface::getRequirements()
   * @see \hook_requirements()
   * @see \hook_runtime_requirements()
   * @see \hook_update_requirements()
   */
  public static function maxSeverityFromRequirements(array $requirements): RequirementSeverity {
    return array_reduce(
      $requirements,
      function (RequirementSeverity $severity, $requirement) {
        $requirementSeverity = $requirement['severity'] ?? RequirementSeverity::OK;
        return RequirementSeverity::from(max($severity->value, $requirementSeverity->value));
      },
      RequirementSeverity::OK
    );
  }

}
