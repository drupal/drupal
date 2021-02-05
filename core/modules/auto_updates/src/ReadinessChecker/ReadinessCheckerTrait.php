<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\SystemManager;

/**
 * Common methods for readiness checkers.
 */
trait ReadinessCheckerTrait {

  /**
   * The readiness checker manager.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;

  /**
   * Gets a message when readiness checkers not pass passed on severity.
   *
   * @param int $severity
   *   The severity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The message.
   */
  protected function getFailureMessageForSeverity(int $severity): TranslatableMarkup {
    return $severity === SystemManager::REQUIREMENT_WARNING ?
      $this->t('Your site does not pass some readiness checks for automatic updates. Depending on the nature of the failures, it might affect the eligibility for automatic updates.') :
      $this->t('Your site does not pass some readiness checks for automatic updates. It cannot be automatically updated until further action is performed.');
  }

  /**
   * Get readiness checker results by severity.
   *
   * @param int $severity
   *   The severity.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]
   *   The readiness checker results by category.
   */
  protected function getResultsWithMessagesForSeverity(int $severity):array {
    $filtered_results = [];
    foreach ($this->readinessCheckerManager->getResults() as $result) {
      if (($severity === SystemManager::REQUIREMENT_ERROR && $result->getErrorMessages()) || ($severity === SystemManager::REQUIREMENT_WARNING && $result->getWarningMessages())) {
        $filtered_results[] = $result;
      }
    }
    return $filtered_results;
  }

}
