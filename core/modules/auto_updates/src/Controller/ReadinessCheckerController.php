<?php

namespace Drupal\auto_updates\Controller;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager;
use Drupal\auto_updates\ReadinessChecker\ReadinessTrait;
use Drupal\auto_updates\ReadinessChecker\ReadinessRequirement;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A controller for running readiness checkers.
 *
 * @internal
 *   Controller classes are internal.
 */
class ReadinessCheckerController extends ControllerBase {

  use ReadinessTrait;

  /**
   * The readiness checker manager.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;

  /**
   * ReadinessCheckerController constructor.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $checker_manager
   *   The readiness checker manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessRequirement $readiness_requirement
   *   A readiness requirement object.
   */
  public function __construct(ReadinessCheckerManager $checker_manager, TranslationInterface $string_translation, ReadinessRequirement $readiness_requirement) {
    $this->readinessCheckerManager = $checker_manager;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container):self {
    return new static(
      $container->get('auto_updates.readiness_checker_manager'),
      $container->get('string_translation'),
      ReadinessRequirement::create($container)
    );
  }

  /**
   * Run the readiness checkers.
   *
   * @param bool $display_message_on_fails
   *   (optional) Determines whether a message should be displayed if there are
   *   any messages from the checkers.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the status report page.
   */
  public function run(bool $display_message_on_fails = FALSE): RedirectResponse {
    $results = $this->readinessCheckerManager->getResults(TRUE);
    if (!$results) {
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      // If there are no messages from the readiness checkers display a message
      // that site is ready. If there are messages the page will display them.
      $this->messenger()->addStatus($this->t('No issues found. Your site is ready for automatic updates'));
    }
    elseif ($display_message_on_fails) {
      $severity = $this->getResultsBySeverity($results, SystemManager::REQUIREMENT_ERROR) ? SystemManager::REQUIREMENT_ERROR : SystemManager::REQUIREMENT_WARNING;
      $message = $this->getFailureMessageForSeverity($severity);
      if ($severity === SystemManager::REQUIREMENT_ERROR) {
        $this->messenger()->addError($message);
      }
      else {
        $this->messenger()->addWarning($message);
      }
    }
    // Set a redirect to the status report page. Any other page that provides a
    // link to this controller should include 'destination' in the query string
    // to ensure this redirect is overridden.
    // @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
    return $this->redirect('system.status');
  }

}
