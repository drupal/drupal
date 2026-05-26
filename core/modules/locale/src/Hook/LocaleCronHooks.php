<?php

namespace Drupal\locale\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Queue\QueueFactory;
use Drupal\locale\CurrentImportStorage;
use Drupal\locale\LocaleDefaultOptions;
use Drupal\locale\LocaleFetch;
use Drupal\locale\LocaleTranslatableProject;
use Drupal\locale\LocaleProjectRepository;

/**
 * Cron Hook implementation for locale.
 */
class LocaleCronHooks {

  public function __construct(
    protected readonly LocaleFetch $localeFetch,
    protected readonly LocaleProjectRepository $localeProjectRepository,
    protected readonly CurrentImportStorage $currentImportStorage,
    protected readonly TimeInterface $time,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly QueueFactory $queueFactory,
  ) {
  }

  /**
   * Implements hook_cron().
   *
   * @see \Drupal\locale\Plugin\QueueWorker\LocaleTranslation
   */
  #[Hook('cron')]
  public function cron(): void {
    $config = $this->configFactory->get('locale.settings');
    // Update translations only when an update frequency was set by the admin
    // and a translatable language was set.
    // Update tasks are added to the queue here but processed by Drupal's cron.
    if ($config->get('translation.update_interval_days') && locale_translatable_language_list()) {
      // Determine which project+language should be updated.
      $request_time = $this->time->getRequestTime();
      $check_time = $request_time - $config->get('translation.update_interval_days') * 3600 * 24;
      $projects = $this->localeProjectRepository->getAll();
      $projects = array_filter($projects, fn(LocaleTranslatableProject $project): bool => $project->getStatus());
      $outdatedImports = $this->currentImportStorage->getOutdatedImports(array_keys($projects), $request_time, $check_time);

      // For each project+language combination a number of tasks are added to
      // the queue.
      if ($outdatedImports) {
        $options = LocaleDefaultOptions::updateOptions();
        $queue = $this->queueFactory->get('locale_translation', TRUE);

        foreach ($outdatedImports as $project => $languages) {
          $batch = $this->localeFetch->buildUpdateBatch([$project], $languages, $options);
          foreach ($batch['operations'] as $item) {
            $queue->createItem($item);
          }
        }
      }
    }
  }

}
