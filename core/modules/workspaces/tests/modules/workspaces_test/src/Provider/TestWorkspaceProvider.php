<?php

declare(strict_types=1);

namespace Drupal\workspaces_test\Provider;

use Drupal\workspaces\Provider\WorkspaceProviderBase;

/**
 * Defines a test workspace provider.
 */
class TestWorkspaceProvider extends WorkspaceProviderBase {

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'test';
  }

}
