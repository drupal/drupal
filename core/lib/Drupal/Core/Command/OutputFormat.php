<?php

namespace Drupal\Core\Command;

/**
 * A list of common output formats.
 */
enum OutputFormat: string {
  case Yaml = 'yaml';
  case Json = 'json';
}
