<?php

namespace Drupal\auto_updates\Controller;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
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
   * ReadinessCheckerController constructor.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $checker_manager
   *   The readiness checker manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ReadinessCheckerManager $checker_manager, TranslationInterface $string_translation) {
    $this->checkerManager = $checker_manager;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('auto_updates.readiness_checker_manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Run the readiness checkers.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the automatic updates settings page.
   */
  public function run() {
    if (!$this->checkerManager->getErrors(TRUE) && !$this->checkerManager->getWarnings()) {
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      // If there are no messages from the readiness checkers display a message
      // that site is ready. If there are messages the page will display them.
      $this->messenger()->addStatus($this->t('No issues found. Your site is ready for automatic updates'));
    }
    return $this->redirect('system.status');
  }

}
