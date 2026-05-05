<?php

declare(strict_types=1);

namespace Drupal\update\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\update\MailHandler;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\UpdateProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Hook implementations for update.
 */
class UpdateCronHooks {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly StateInterface $state,
    protected readonly TimeInterface $time,
    /**
     * @var \Closure(): \Drupal\update\MailHandler
     */
    #[AutowireServiceClosure(MailHandler::class)]
    protected readonly \Closure $mailHandler,
    /**
     * @var \Closure(): \Drupal\update\UpdateManagerInterface
     */
    #[AutowireServiceClosure(UpdateManagerInterface::class)]
    protected readonly \Closure $updateManager,
    /**
     * @var \Closure(): \Drupal\update\UpdateProcessorInterface
     */
    #[AutowireServiceClosure(UpdateProcessorInterface::class)]
    protected readonly \Closure $updateProcessor,
  ) {
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $update_config = $this->configFactory->get('update.settings');
    $frequency = $update_config->get('check.interval_days');
    $interval = 60 * 60 * 24 * $frequency;
    $last_check = $this->state->get('update.last_check', 0);
    $request_time = $this->time->getRequestTime();
    if ($request_time - $last_check > $interval) {
      // If the configured update interval has elapsed, we want to invalidate
      // the data for all projects, attempt to re-fetch, and trigger any
      // configured notifications about the new status.
      ($this->updateManager)()->refreshUpdateData();
      ($this->updateProcessor)()->fetchData();
    }
    else {
      // Otherwise, see if any individual projects are now stale or still
      // missing data, and if so, try to fetch the data.
      update_get_available(TRUE);
    }
    $last_email_notice = $this->state->get('update.last_email_notification', 0);
    if ($request_time - $last_email_notice > $interval) {
      // If configured time between notifications elapsed, send email about
      // updates possibly available.
      if ($this->notify()) {
        // Track when the last mail was successfully sent to avoid sending
        // too many emails.
        $this->state->set('update.last_email_notification', $request_time);
      }
    }
  }

  /**
   * Performs any notifications that should be done once cron fetches new data.
   *
   * This method checks the status of the site using the new data and, depending
   * on the configuration of the site, notifies administrators via email if
   * there are new releases or missing security updates.
   *
   * @return bool
   *   TRUE if any notifications were sent, otherwise FALSE.
   *
   * @see Drupal\update\Hook\UpdateRequirements::runtime()
   */
  protected function notify(): bool {
    $update_config = $this->configFactory->get('update.settings');
    $notify_all = ($update_config->get('notification.threshold') == 'all');
    $recipients = $update_config->get('notification.emails');
    if (empty($recipients)) {
      return FALSE;
    }

    $status = $this->moduleHandler->invoke('update', 'runtime_requirements');
    $items = [];
    foreach (['core', 'contrib'] as $report_type) {
      $type = 'update_' . $report_type;
      if (isset($status[$type]['severity'])
          && ($status[$type]['severity'] == RequirementSeverity::Error || ($notify_all && $status[$type]['reason'] == UpdateManagerInterface::NOT_CURRENT))) {
        $items[$report_type] = $status[$type]['reason'];
      }
    }
    if (empty($items)) {
      return FALSE;
    }

    return ($this->mailHandler)()->sendUpdateNotifications($recipients, $items);
  }

}
