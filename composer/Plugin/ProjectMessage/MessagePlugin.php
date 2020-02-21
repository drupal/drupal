<?php

namespace Drupal\Composer\Plugin\ProjectMessage;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * A Composer plugin to display a message after creating a project.
 *
 * @internal
 */
class MessagePlugin implements PluginInterface, EventSubscriberInterface {

  /**
   * Composer object.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * IO object.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * Configuration.
   *
   * @var \Drupal\Composer\VendorHardening\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_CREATE_PROJECT_CMD => 'displayPostCreateMessage',
      ScriptEvents::POST_INSTALL_CMD => 'displayPostCreateMessage',
    ];
  }

  public function displayPostCreateMessage(Event $event) {
    $message = new Message($this->composer->getPackage(), $event->getName());
    if ($message = $message->getText()) {
      $this->io->write($message);
    }
  }

}
