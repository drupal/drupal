<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for generating the readiness checkers' output for hook_requirements.
 *
 * @see auto_updates_requirements()
 *
 * @internal
 *   This class implements logic to output the messages from readiness checkers
 *   on the status report page. It should not be called directly.
 */
final class ReadinessRequirement implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use ReadinessTrait;

  /**
   * The readiness checker manager.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructor ReadinessRequirement object.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $readiness_checker_manager
   *   The readiness checker manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(ReadinessCheckerManager $readiness_checker_manager, TranslationInterface $translation, DateFormatterInterface $date_formatter) {
    $this->readinessCheckerManager = $readiness_checker_manager;
    $this->setStringTranslation($translation);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('auto_updates.readiness_checker_manager'),
      $container->get('string_translation'),
      $container->get('date.formatter')
    );
  }

  /**
   * Gets requirements arrays as specified in hook_requirements.
   *
   * @return mixed[]
   *   Requirements arrays as specified by hook_requirements().
   */
  public function getRequirements(): array {
    $run_link = $this->createRunLink();

    $last_check_timestamp = $this->readinessCheckerManager->getLastRunTime();
    if ($last_check_timestamp === NULL) {
      $requirement['title'] = $this->t('Update readiness checks');
      $requirement['severity'] = SystemManager::REQUIREMENT_WARNING;
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      $requirement['value'] = $this->t('Your site has never checked if it is ready to apply automatic updates.');
      if ($run_link) {
        $requirement['description'] = $run_link;
      }
      return ['auto_updates_readiness' => $requirement];
    }
    else {
      $results = $this->readinessCheckerManager->runIfNoStoredValidResults()->getResults();
      $requirements = [];
      if (empty($results)) {
        $requirements['auto_updates_readiness'] = [
          'title' => $this->t('Update readiness checks'),
          'severity' => SystemManager::REQUIREMENT_OK,
          // @todo Link "automatic updates" to documentation in
          //   https://www.drupal.org/node/3168405.
          'value' => $this->t('Your site is ready for automatic updates.'),
        ];
        if ($run_link) {
          $requirements['auto_updates_readiness']['description'] = $run_link;
        }
      }
      else {
        foreach ([SystemManager::REQUIREMENT_WARNING, SystemManager::REQUIREMENT_ERROR] as $severity) {
          if ($requirement = $this->createRequirementForSeverity($severity)) {
            $requirements["auto_updates_readiness_$severity"] = $requirement;
          }
        }
      }
      return $requirements;
    }
  }

  /**
   * Creates a requirement for checker results of a specific severity.
   *
   * @param int $severity
   *   The severity for requirement. Should be one of the
   *   SystemManager::REQUIREMENT_* constants.
   *
   * @return mixed[]|null
   *   Requirements array as specified by hook_requirements(), or NULL
   *   if no requirements can be determined.
   */
  protected function createRequirementForSeverity(int $severity): ?array {
    $severity_messages = [];
    $results = $this->readinessCheckerManager->getResults($severity);
    if (!$results) {
      return NULL;
    }
    foreach ($results as $result) {
      $checker_messages = $result->getMessages();
      if (count($checker_messages) === 1) {
        $severity_messages[] = ['#markup' => array_pop($checker_messages)];
      }
      else {
        $severity_messages[] = [
          '#type' => 'details',
          '#title' => $result->getSummary(),
          '#open' => FALSE,
          'messages' => [
            '#theme' => 'item_list',
            '#items' => $checker_messages,
          ],
        ];
      }
    }
    $requirement = [
      'title' => $this->t('Update readiness checks'),
      'severity' => $severity,
      'value' => $this->getFailureMessageForSeverity($severity),
      'description' => [
        'messages' => $severity_messages,
      ],
    ];
    if ($run_link = $this->createRunLink()) {
      $requirement['description']['run_link'] = [
        '#type' => 'container',
        '#markup' => $run_link,
      ];
    }
    return $requirement;
  }

  /**
   * Creates a link to run the readiness checkers.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A link, if the user has access to run the readiness checkers, otherwise
   *   NULL.
   */
  protected function createRunLink(): ?TranslatableMarkup {
    $readiness_check_url = Url::fromRoute('auto_updates.update_readiness');
    if ($readiness_check_url->access()) {
      return $this->t(
        '<a href=":link">Run readiness checks</a> now.',
        [':link' => $readiness_check_url->toString()]
      );
    }
    return NULL;
  }

}
