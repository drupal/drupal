<?php

declare(strict_types=1);

namespace phpcs;

use PHP_CodeSniffer\Filters\Filter;

/**
 * Allows .sh files explicitly listed in phpcs.xml.dist to be scanned as PHP.
 *
 * PHPCS rejects files whose extension is not in the configured extensions list.
 * Some Drupal scripts use a .sh extension but contain PHP code. This filter
 * accepts any .sh file that appears in $config->files, i.e. was explicitly
 * listed via a <file> element in the active ruleset.
 *
 * The filter value in phpcs.xml.dist must be a path relative to the directory
 * from which phpcs is invoked (the project root for Drupal).
 */
class PhpScriptFilter extends Filter {

  /**
   * {@inheritdoc}
   */
  protected function shouldProcessFile($path): bool {
    $path = (string) $path;
    $realPath = realpath($path);
    if ($realPath !== FALSE) {
      foreach ($this->config->files as $listed) {
        if ($listed === $realPath) {
          return TRUE;
        }
      }
    }
    return parent::shouldProcessFile($path);
  }

}
