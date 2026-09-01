<?php

declare(strict_types=1);

namespace Drupal\locale\Model;

/**
 * The locale source type.
 *
 * This indicates what the next step in the process is:
 * - Remote means there is a more recent remote state than local, and it will
 *   be downloaded.
 * - Local means the current local file is more recent/changed than the last
 *   imported version and will be imported.
 * - Current means that the last imported file is the most recent known state
 *   and nothing needs to be done.
 */
enum SourceType: string {
  case Remote = 'remote';
  case Local = 'local';
  case Current = 'current';
}
