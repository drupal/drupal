<?php

declare(strict_types = 1);

namespace Drupal\Tests\fixture\Composer\Plugin;

use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginInterface;
use Composer\Composer;
use Composer\IO\IOInterface;
use Drupal\Composer\Plugin\Scaffold\Handler;

/**
 * A fixture composer plugin implement Drupal scaffold events.
 */
class ComposerPluginImplementsScaffoldEvents implements PluginInterface, EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      Handler::PRE_DRUPAL_SCAFFOLD_CMD => 'preDrupalScaffoldCmd',
      Handler::POST_DRUPAL_SCAFFOLD_CMD => 'postDrupalScaffoldCmd',
    ];
  }

  /**
   * Implements pre Drupal scaffold cmd.
   */
  public static function preDrupalScaffoldCmd(Event $event): void {
    print 'Hello preDrupalScaffoldCmd' . PHP_EOL;
  }

  /**
   * Implements post Drupal scaffold cmd.
   */
  public static function postDrupalScaffoldCmd(Event $event): void {
    print 'Hello postDrupalScaffoldCmd' . PHP_EOL;
  }

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {

  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io) {

  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io) {

  }

}
