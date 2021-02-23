<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for displaying messages on admin pages.
 *
 * @internal
 *   This class implements logic output the messages from readiness checkers. It
 *   should not be called directly.
 */
final class ReadinessCheckerMessages implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use RedirectDestinationTrait;
  use ReadinessTrait;

  /**
   * The readiness checker manager.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * ReadinessRequirement constructor.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $readiness_checker_manager
   *   The readiness checker manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route
   *   The current route.
   */
  public function __construct(ReadinessCheckerManager $readiness_checker_manager, MessengerInterface $messenger, AdminContext $admin_context, AccountProxyInterface $current_user, TranslationInterface $translation, CurrentRouteMatch $current_route) {
    $this->readinessCheckerManager = $readiness_checker_manager;
    $this->messenger = $messenger;
    $this->adminContext = $admin_context;
    $this->currentUser = $current_user;
    $this->setStringTranslation($translation);
    $this->currentRoute = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('auto_updates.readiness_checker_manager'),
      $container->get('messenger'),
      $container->get('router.admin_context'),
      $container->get('current_user'),
      $container->get('string_translation'),
      $container->get('current_route_match')
    );
  }

  /**
   * Displays the checker results messages on admin pages.
   */
  public function adminPageMessages(): void {
    if (!$this->displayResultsOnCurrentPage()) {
      return;
    }
    $results = $this->readinessCheckerManager->getResults();
    if (is_null($results)) {
      $checker_url = Url::fromRoute('auto_updates.update_readiness')->setOption('query', $this->getDestinationArray());
      if ($checker_url->access()) {
        $this->messenger->addError(t('Your site has not recently run an update readiness check. <a href=":url">Run readiness checks now.</a>', [
          ':url' => $checker_url->toString(),
        ]));
      }
    }
    else {
      if ($error_results = $this->getResultsBySeverity($results, SystemManager::REQUIREMENT_ERROR)) {
        // @todo Link "automatic updates" to documentation in
        //    https://www.drupal.org/node/3168405.
        $this->messenger->addError($this->getFailureMessageForSeverity(SystemManager::REQUIREMENT_ERROR));
        foreach ($error_results as $result) {
          $error_messages = $result->getErrorMessages();
          $message = count($error_messages) === 1 ? $error_messages[0] : $result->getErrorsSummary();
          $this->messenger->addError($message);
        }
      }
      else {
        // Only display warning summaries if no errors were displayed.
        $warning_results = $this->getResultsBySeverity($results, SystemManager::REQUIREMENT_WARNING);
        if ($warning_results) {
          // @todo Link "automatic updates" to documentation in
          //    https://www.drupal.org/node/3168405.
          $this->messenger->addWarning($this->getFailureMessageForSeverity(SystemManager::REQUIREMENT_WARNING));
          foreach ($warning_results as $result) {
            if ($warning_messages = $result->getWarningMessages()) {
              $message = count($warning_messages) === 1 ? $warning_messages[0] : $result->getWarningsSummary();
              $this->messenger->addWarning($message);
            }
          }
        }
      }
    }
  }

  /**
   * Determines whether the messages should be displayed on the current page.
   *
   * @return bool
   *   Whether the messages should be displayed on the current page.
   */
  protected function displayResultsOnCurrentPage(): bool {
    if ($this->adminContext->isAdminRoute() && $this->currentUser->hasPermission('administer site configuration')) {
      $disabled_routes = [
        'update.theme_update',
        'system.theme_install',
        'update.module_update',
        'update.module_install',
        'update.status',
        'update.report_update',
        'update.report_install',
        'update.settings',
        'system.status',
        'update.confirmation_page',
      ];
      // These routes don't need additional nagging.
      return !in_array($this->currentRoute->getRouteName(), $disabled_routes, TRUE);
    }
    return FALSE;
  }

}
