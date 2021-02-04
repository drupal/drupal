<?php

namespace Drupal\auto_updates\Controller;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager;
use Drupal\auto_updates\ReadinessChecker\ReadinessRequirement;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A controller for running readiness checkers.
 *
 * @internal
 *   Controller classes are internal.
 */
class ReadinessCheckerController extends ControllerBase {

  /**
   * The readiness checker manager.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $checkerManager;

  /**
   * A readiness checker requirement object.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessRequirement
   */
  protected $readinessRequirement;

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
    $this->checkerManager = $checker_manager;
    $this->setStringTranslation($string_translation);
    $this->readinessRequirement = $readiness_requirement;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
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
  public function run($display_message_on_fails = FALSE) {
    $results = $this->checkerManager->getResults(TRUE);
    if (!$results) {
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      // If there are no messages from the readiness checkers display a message
      // that site is ready. If there are messages the page will display them.
      $this->messenger()->addStatus($this->t('No issues found. Your site is ready for automatic updates'));
    }
    elseif ($display_message_on_fails) {
      $severity = SystemManager::REQUIREMENT_WARNING;
      foreach ($results as $result) {
        if ($result->getErrorMessages()) {
          $severity = SystemManager::REQUIREMENT_ERROR;
          break;
        }
      }
      $message = $this->readinessRequirement->getMessageForSeverity($severity);
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

  /**
   * Run the readiness checkers on the status report page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the status report page.
   */
  public function runAndDisplayWarning() {
    return $this->run(TRUE);
  }

}
