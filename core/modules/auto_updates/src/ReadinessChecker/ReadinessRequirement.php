<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for generating the readiness checkers requirement.
 *
 * @see update_requirements()
 *
 * @internal
 *   This class implements logic output the messages from readiness checkers. It
 *   should not be called directly.
 */
final class ReadinessRequirement implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The readiness checker manager service.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ReadinessRequirement constructor.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $readinessCheckerManager
   *   The readiness checker manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   */
  public function __construct(ReadinessCheckerManager $readinessCheckerManager, LoggerInterface $logger, TranslationInterface $translation) {
    $this->readinessCheckerManager = $readinessCheckerManager;
    $this->logger = $logger;
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('auto_updates.readiness_checker_manager'),
      $container->get('logger.channel.auto_updates'),
      $container->get('string_translation')
    );
  }

  /**
   * Gets the security coverage requirement, if any.
   *
   * @return array
   *   Requirements array as specified by hook_requirements(), or an empty array
   *   if no requirements can be determined.
   */
  public function getRequirements() {
    /** @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $this->readinessCheckerManager */
    $this->readinessCheckerManager = \Drupal::service('auto_updates.readiness_checker_manager');

    $readiness_check_url = Url::fromRoute('auto_updates.status_update_readiness');

    $last_check_timestamp = $this->readinessCheckerManager->getMostRecentRunTime();
    if ($last_check_timestamp === NULL) {
      $requirement['title'] = $this->t('Update readiness checks');
      $requirement['severity'] = REQUIREMENT_WARNING;
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      $requirement['value'] = $this->t('Your site has never checked if it is ready to apply automatic updates.');
      if ($readiness_check_url->access()) {
        $requirement['description'] = $this->t('<a href=":link">Run readiness checks</a> now.', [
          ':link' => $readiness_check_url->toString(),
        ]);
      }
      return ['auto_updates_readiness' => $requirement];
    }
    elseif (!$this->readinessCheckerManager->hasRunRecently()) {
      $requirement['title'] = $this->t('Update readiness checks');
      $requirement['severity'] = REQUIREMENT_WARNING;
      $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($last_check_timestamp);
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      $requirement['value'] = $this->t('Your site has not recently checked if it is ready to apply automatic updates.');
      if ($readiness_check_url->access()) {
        $requirement['description'] = $this->t('Readiness checks were last run @time ago. <a href=":url">Run readiness checks</a> now.', [
          '@time' => $time_ago,
          ':url' => $readiness_check_url->toString(),
        ]);
      }
      else {
        $requirement['description'] = $this->t('Readiness checks were last run @time ago.', ['@time' => $time_ago]);
      }
      return ['auto_updates_readiness' => $requirement];
    }
    else {
      $requirements = [];
      foreach ([REQUIREMENT_WARNING => 'warnings', REQUIREMENT_ERROR => 'errors'] as $severity => $severity_type) {
        if ($requirement = $this->createRequirementForSeverity($severity)) {
          $requirements["auto_updates_readiness_$severity_type"] = $requirement;
        }
      }
      if (empty($requirements)) {
        $requirements['auto_updates_readiness'] = [
          'title' => $this->t('Update readiness checks'),
          'severity' => REQUIREMENT_OK,
          // @todo Link "automatic updates" to documentation in
          //   https://www.drupal.org/node/3168405.
          'value' => $this->t('Your site is ready for automatic updates.'),
        ];
      }
      return $requirements;
    }
  }

  /**
   * Creates a requirements section for readiness checker results.
   *
   * @param int $severity
   *   The severity for requirement section.
   *
   * @return array|null
   *   Requirements array as specified by hook_requirements(), or NULL
   *   if no requirements can be determined.
   */
  private function createRequirementForSeverity(int $severity):?array {
    $severity_messages = [];
    foreach ($this->readinessCheckerManager->getResults() as $result) {
      if ($severity === REQUIREMENT_ERROR) {
        $summary = $result->getErrorsSummary();
        $checker_messages = $result->getErrorMessages();
      }
      elseif ($severity === REQUIREMENT_WARNING) {
        $summary = $result->getWarningsSummary();
        $checker_messages = $result->getWarningMessages();
      }
      else {
        throw new \UnexpectedValueException('Unknown severity type: ' . $severity);
      }
      if (count($checker_messages) === 1) {
        $severity_messages[] = ['#markup' => array_pop($checker_messages)];
      }
      else {
        $severity_messages[] = [
          '#type' => 'details',
          '#title' => $summary,
          '#open' => FALSE,
          'messages' => [
            '#theme' => 'item_list',
            '#items' => $checker_messages,
          ],
        ];
      }
    }
    if ($severity_messages) {
      $requirement = [
        'title' => $this->t('Update readiness checks'),
        'severity' => $severity,
        'description' => $severity_messages,
      ];
      $requirement['value'] = $severity === REQUIREMENT_WARNING ?
        $this->t('Your site does not pass some readiness checks for automatic updates. Depending on the nature of the failures, it might effect the eligibility for automatic updates.') :
        $this->t('Your site does not pass some readiness checks for automatic updates. It cannot be automatically updated until further action is performed.');
      return $requirement;
    }
    return NULL;
  }

}
