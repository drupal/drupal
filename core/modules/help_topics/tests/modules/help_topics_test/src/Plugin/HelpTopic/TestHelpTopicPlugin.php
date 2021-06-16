<?php

namespace Drupal\help_topics_test\Plugin\HelpTopic;

use Drupal\Core\Cache\Cache;
use Drupal\help_topics\HelpTopicPluginBase;

/**
 * A fake help topic plugin for testing.
 */
class TestHelpTopicPlugin extends HelpTopicPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return [
      '#type' => 'markup',
      '#markup' => $this->pluginDefinition['body'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['foobar'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
