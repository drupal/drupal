<?php

declare(strict_types = 1);

namespace Drupal\workspaces;

use Drupal\Core\CronInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Decorates the cron service.
 */
class WorkspacesCron implements CronInterface {

  public function __construct(
    #[AutowireDecorated]
    protected CronInterface $inner,
    protected WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Ensure that cron tasks run without an active workspace.
    return $this->workspaceManager->executeOutsideWorkspace(fn() => $this->inner->run());
  }

}
