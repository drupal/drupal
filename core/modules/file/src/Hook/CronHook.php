<?php

declare(strict_types=1);

namespace Drupal\file\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Implements hook_cron().
 */
#[Hook('cron')]
class CronHook {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly StreamWrapperManagerInterface $streamWrapperManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileUsageInterface $fileUsage,
    private readonly TimeInterface $time,
    #[Autowire('@logger.channel.file')]
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Implements hook_cron().
   */
  public function __invoke(): void {
    $age = $this->configFactory->get('system.file')->get('temporary_maximum_age');
    $fileStorage = $this->entityTypeManager->getStorage('file');
    // Only delete temporary files if older than $age. Note that automatic
    // cleanup is disabled if $age set to 0.
    if ($age) {
      $fids = $fileStorage->getQuery()->accessCheck(FALSE)->condition('status', FileInterface::STATUS_PERMANENT, '<>')->condition('changed', $this->time->getRequestTime() - $age, '<')->range(0, 100)->execute();
      /** @var \Drupal\file\FileInterface[] $files */
      $files = $fileStorage->loadMultiple($fids);
      foreach ($files as $file) {
        $references = $this->fileUsage->listUsage($file);
        if (empty($references)) {
          if (!file_exists($file->getFileUri())) {
            if (!$this->streamWrapperManager->isValidUri($file->getFileUri())) {
              $this->logger->warning('Temporary file "%path" that was deleted during garbage collection did not exist on the filesystem. This could be caused by a missing stream wrapper.', ['%path' => $file->getFileUri()]);
            }
            else {
              $this->logger->warning('Temporary file "%path" that was deleted during garbage collection did not exist on the filesystem.', ['%path' => $file->getFileUri()]);
            }
          }
          // Delete the file entity. If the file does not exist, this will
          // generate a second notice in the watchdog.
          $file->delete();
        }
        else {
          $this->logger->info('Did not delete temporary file "%path" during garbage collection because it is in use by the following modules: %modules.', [
            '%path' => $file->getFileUri(),
            '%modules' => implode(', ', array_keys($references)),
          ]);
        }
      }
    }
  }

}
