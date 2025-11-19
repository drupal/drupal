#!/usr/bin/env php
<?php

/**
 * @file
 * Provides CLI commands for Drupal.
 */

use Drupal\Core\Command\GenerateTheme;
use Drupal\Core\Command\QuickStartCommand;
use Drupal\Core\Command\InstallCommand;
use Drupal\Core\Command\ServerCommand;
use Drupal\Core\DefaultContent\ContentExportCommand;
use Drupal\Core\Recipe\RecipeCommand;
use Drupal\Core\Recipe\RecipeInfoCommand;
use Symfony\Component\Console\Application;

if (PHP_SAPI !== 'cli') {
  return;
}

$classloader = require_once __DIR__ . '/../../autoload.php';

$application = new Application('drupal', \Drupal::VERSION);

$application->addCommand(new QuickStartCommand());
$application->addCommand(new InstallCommand($classloader));
$application->addCommand(new ServerCommand($classloader));
$application->addCommand(new GenerateTheme());
$application->addCommand(new RecipeCommand($classloader));
$application->addCommand(new RecipeInfoCommand($classloader));
$application->addCommand(new ContentExportCommand($classloader));

$application->run();
