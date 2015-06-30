<?php

/**
 * @file
 * Contains \Drupal\Core\Command\GenerateProxyClassApplication.
 */

namespace Drupal\Core\Command;

use Drupal\Component\ProxyBuilder\ProxyBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides a console command to generate proxy classes.
 */
class GenerateProxyClassApplication extends Application {

  /**
   * The proxy builder.
   *
   * @var \Drupal\Component\ProxyBuilder\ProxyBuilder
   */
  protected $proxyBuilder;

  /**
   * Constructs a new GenerateProxyClassApplication instance.
   *
   * @param \Drupal\Component\ProxyBuilder\ProxyBuilder $proxy_builder
   *   The proxy builder.
   */
  public function __construct(ProxyBuilder $proxy_builder) {
    $this->proxyBuilder = $proxy_builder;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCommandName(InputInterface $input) {
    return 'generate-proxy-class';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands() {
    // Even though this is a single command, keep the HelpCommand (--help).
    $default_commands = parent::getDefaultCommands();
    $default_commands[] = new GenerateProxyClassCommand($this->proxyBuilder);
    return $default_commands;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden so the application doesn't expect the command name as the first
   * argument.
   */
  public function getDefinition() {
    $definition = parent::getDefinition();
    // Clears the normal first argument (the command name).
    $definition->setArguments();
    return $definition;
  }

}
