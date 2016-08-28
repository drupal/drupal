<?php

namespace Drupal\KernelTests;

use Drupal\Component\Diff\Diff;

/**
 * Trait to help with diffing config.
 */
trait AssertConfigTrait {

  /**
   * Ensures that a specific config diff does not contain unwanted changes.
   *
   * @param \Drupal\Component\Diff\Diff $result
   *   The diff result for the passed in config name.
   * @param string $config_name
   *   The config name to check.
   * @param array $skipped_config
   *   An array of skipped config, keyed by string. If the value is TRUE, the
   *   entire file will be ignored, otherwise it's an array of strings which are
   *   ignored.
   *
   * @throws \Exception
   *   Thrown when a configuration is different.
   */
  protected function assertConfigDiff(Diff $result, $config_name, array $skipped_config) {
    foreach ($result->getEdits() as $op) {
      switch (get_class($op)) {
        case 'Drupal\Component\Diff\Engine\DiffOpCopy':
          // Nothing to do, a copy is what we expect.
          break;
        case 'Drupal\Component\Diff\Engine\DiffOpDelete':
        case 'Drupal\Component\Diff\Engine\DiffOpChange':
          // It is not part of the skipped config, so we can directly throw the
          // exception.
          if (!in_array($config_name, array_keys($skipped_config))) {
            throw new \Exception($config_name . ': ' . var_export($op, TRUE));
          }

          // Allow to skip entire config files.
          if ($skipped_config[$config_name] === TRUE) {
            continue;
          }

          // Allow to skip some specific lines of imported config files.
          // Ensure that the only changed lines are the ones we marked as
          // skipped.
          $all_skipped = TRUE;

          $changes = get_class($op) == 'Drupal\Component\Diff\Engine\DiffOpDelete' ? $op->orig : $op->closing;
          foreach ($changes as $closing) {
            // Skip some of the changes, as they are caused by module install
            // code.
            $found = FALSE;
            if (!empty($skipped_config[$config_name])) {
              foreach ($skipped_config[$config_name] as $line) {
                if (strpos($closing, $line) !== FALSE) {
                  $found = TRUE;
                  break;
                }
              }
            }
            $all_skipped = $all_skipped && $found;
          }

          if (!$all_skipped) {
            throw new \Exception($config_name . ': ' . var_export($op, TRUE));
          }
          break;
        case 'Drupal\Component\Diff\Engine\DiffOpAdd':
          // The _core property does not exist in the default config.
          if ($op->closing[0] === '_core:') {
            continue;
          }
          foreach ($op->closing as $closing) {
            // The UUIDs don't exist in the default config.
            if (strpos($closing, 'uuid: ') === 0) {
              continue;
            }
            throw new \Exception($config_name . ': ' . var_export($op, TRUE));
          }
          break;
        default:
          throw new \Exception($config_name . ': ' . var_export($op, TRUE));
      }
    }
  }

}
