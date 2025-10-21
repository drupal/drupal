<?php

declare(strict_types=1);

namespace Drupal\workspaces\Provider;

/**
 * Defines the default workspace provider.
 */
class DefaultWorkspaceProvider extends WorkspaceProviderBase {

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'default';
  }

}
