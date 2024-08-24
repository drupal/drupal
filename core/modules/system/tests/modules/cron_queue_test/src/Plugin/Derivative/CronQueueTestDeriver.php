<?php

declare(strict_types=1);

namespace Drupal\cron_queue_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides a deriver for testing cron queues.
 */
class CronQueueTestDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $example_data = [
      'foo' => 'Foo',
      'bar' => 'Bar',
    ];

    $derivatives = [];
    foreach ($example_data as $key => $label) {
      $derivatives[$key] = [
        'title' => strtr('Cron queue test: @label', [
          '@label' => $label,
        ]),
      ] + $base_plugin_definition;
    }

    return $derivatives;
  }

}
