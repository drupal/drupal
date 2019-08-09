<?php

namespace Drupal\help_topics_test\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverInterface;

/**
 * A test discovery deriver for fake help topics.
 */
class TestHelpTopicDeriver implements DeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $prefix = $base_plugin_definition['id'];
    $id = 'test_derived_topic';
    $plugin_id = $prefix . ':' . $id;
    $definitions[$id] = [
      'plugin_id' => $plugin_id,
      'id' => $plugin_id,
      'class' => 'Drupal\\help_topics_test\\Plugin\\HelpTopic\\TestHelpTopicPlugin',
      'label' => 'Label for ' . $id,
      'body' => 'Body for ' . $id,
      'top_level' => TRUE,
      'related' => [],
      'provider' => 'help_topics_test',
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    return $base_plugin_definition;
  }

}
