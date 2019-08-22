<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * List of all commands provided by this package.
 */
class CommandProvider implements CommandProviderCapability {

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    return [new ComposerScaffoldCommand()];
  }

}
