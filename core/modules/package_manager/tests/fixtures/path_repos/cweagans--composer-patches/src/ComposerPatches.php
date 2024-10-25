<?php

namespace cweagans\Fake;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Dummy composer plugin implementation.
 */
class ComposerPatches implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {}

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io) {}

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io) {}

}
