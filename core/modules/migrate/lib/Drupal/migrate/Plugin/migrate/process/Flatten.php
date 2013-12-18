<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Flatten.
 */

namespace Drupal\migrate\Plugin\migrate\process;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin flattens the current value.
 *
 * During some types of processing (e.g. user permission splitting), what was
 * once a single value gets transformed into multiple values. This plugin will
 * flatten them back down to single values again.
 *
 * @see https://drupal.org/node/2154215
 *
 * @MigrateProcessPlugin(
 *   id = "flatten",
 *   handle_multiples = TRUE
 * )
 */
class Flatten extends ProcessPluginBase {

  /**
