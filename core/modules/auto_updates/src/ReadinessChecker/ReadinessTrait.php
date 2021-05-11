<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\SystemManager;

/**
 * Common methods for working with readiness checkers.
 */
trait ReadinessTrait {

  /**
   * Gets a message, based on severity, when readiness checkers fail.
   *
   * @param int $severity
   *   The severity. Should be one of the SystemManager::REQUIREMENT_*
   *   constants.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The message.
   *
   * @see \Drupal\system\SystemManager::REQUIREMENT_ERROR
   * @see \Drupal\system\SystemManager::REQUIREMENT_WARNING
   */
  protected function getFailureMessageForSeverity(int $severity): TranslatableMarkup {
    return $severity === SystemManager::REQUIREMENT_WARNING ?
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      $this->t('Your site does not pass some readiness checks for automatic updates. Depending on the nature of the failures, it might affect the eligibility for automatic updates.') :
      $this->t('Your site does not pass some readiness checks for automatic updates. It cannot be automatically updated until further action is performed.');
  }

  /**
   * Gets readiness checker results by severity.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[] $results
   *   The results to filter.
   * @param int $severity
   *   The severity. Should be one of the SystemManager::REQUIREMENT_*
   *   constants.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]
   *   The readiness checker results by category.
   *
   * @see \Drupal\system\SystemManager::REQUIREMENT_ERROR
   * @see \Drupal\system\SystemManager::REQUIREMENT_WARNING
   */
  protected static function getResultsBySeverity(array $results, int $severity): array {
    return array_filter(
      $results,
      function (ReadinessCheckerResult $result) use ($severity) {
        return $result->getSeverity() === $severity;
      }
    );
  }

}
