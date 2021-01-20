<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\Url;

/**
 * Class for generating the readiness checkers requirement.
 *
 * @see update_requirements()
 *
 * @internal
 *   This class implements logic output the messages from readiness checkers. It
 *   should not be called directly.
 */
final class ReadinessRequirement {

  /**
   * The readm
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;

  /**
   * ReadinessRequirement constructor.
   */
  public function __construct(ReadinessCheckerManager $readinessCheckerManager) {
    $this->readinessCheckerManager = $readinessCheckerManager;
  }

  /**
   * Gets the security coverage requirement, if any.
   *
   * @return array
   *   Requirements array as specified by hook_requirements(), or an empty array
   *   if no requirements can be determined.
   */
  public function getRequirement() {
    /** @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $this->readinessCheckerManager */
    $this->readinessCheckerManager = \Drupal::service('auto_updates.readiness_checker_manager');

    $readiness_check_url = Url::fromRoute('auto_updates.status_update_readiness');
    $last_check_timestamp = $this->readinessCheckerManager->getMostRecentRunTime();
    if ($last_check_timestamp === NULL) {
      $requirement['severity'] = REQUIREMENT_WARNING;
      // @todo Link "automatic updates" to documentation in
      //    https://www.drupal.org/node/3168405.
      $requirement['value'] = t('Your site has never checked if it is ready to apply automatic updates.');
      if ($readiness_check_url->access()) {
        $requirement['description'] = t('<a href=":link">Run readiness checks</a> now.', [
          ':link' => $readiness_check_url->toString(),
        ]);
      }
    }
    elseif (!$this->readinessCheckerManager->hasRunRecently()) {
      $requirement['severity'] = REQUIREMENT_WARNING;
      $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($last_check_timestamp);
      // @todo Link "automatic updates" to documentation in
      //    https://www.drupal.org/node/3168405.
      $requirement['value'] = t('Your site has not recently checked if it is ready to apply automatic updates.');
      if ($readiness_check_url->access()) {
        $requirement['description'] = t('Readiness checks were last run @time ago. <a href=":url">Run readiness checks</a> now.', [
          '@time' => $time_ago,
          ':url' => $readiness_check_url->toString(),
        ]);
      }
      else {
        $requirement['description'] = t('Readiness checks were last run @time ago.', ['@time' => $time_ago]);
      }
    }
    else {
      $results = $this->readinessCheckerManager->getResults();
      if (!empty($results)) {
        $errors = $this->getSection('errors');
        $warnings = $this->getSection('warnings');
        $requirement['severity'] = $errors ? REQUIREMENT_ERROR : REQUIREMENT_WARNING;
        // $requirement['value'] = new PluralTranslatableMarkup(count($errors) + count($warnings), '@count check failed:', '@count checks failed:');
        $requirement['description'] = [
          'errors' => $errors,
          'warnings' => $warnings,
        ];
      }
      else {
        $requirement = [
          'severity' => REQUIREMENT_OK,
          // @todo Link "automatic updates" to documentation in
          //    https://www.drupal.org/node/3168405.
          'value' => t('Your site is ready for automatic updates.'),
        ];
      }
    }
    if ($requirement) {
      $requirement['title'] = t('Update readiness checks');
      return $requirement;
    }
    return [];
  }

  private function getSection(string $severity) {
    $section = [];
    foreach ($this->readinessCheckerManager->getResults() as $result) {
      if ($severity === 'errors') {
        $summary = $result->getErrorsSummary();
        $messages = $result->getErrorMessages();
      }
      elseif ($severity === 'warnings') {
        $summary = $result->getWarningsSummary();
        $messages = $result->getWarningMessages();
      }
      else {
        throw new \UnexpectedValueException('Unknown severity type: ' . $severity);
      }
      if ($summary) {
        $section[] = [
          '#type' => 'details',
          '#title' => $summary,
          '#open' => FALSE,
          'messages' => [
            '#theme' => 'item_list',
            '#items' => $messages,
          ],
        ];
      }
      elseif ($messages) {
        $section[] = [
          '#theme' => 'item_list',
          '#items' => $messages,
        ];
      }
    }
    return $section;
  }

}
