<?php

declare(strict_types=1);

namespace Drupal\Tests\fixture\Composer\Plugin\DownloadsModifier;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * A do-nothing Composer plugin flagged with 'plugin-modifies-downloads'.
 *
 * Composer moves the install operation of such a plugin in front of all
 * other plugin operations. Tests use this to force a post-package-install
 * event to fire before the Scaffold plugin's own update operation executes.
 *
 * @see \Composer\DependencyResolver\Transaction::movePluginsToFront()
 */
class DownloadsModifierPlugin implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io): void {
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io): void {
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io): void {
  }

}
