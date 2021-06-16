<?php

namespace Drupal\help_page_test\Plugin\HelpSection;

use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;

/**
 * Provides an empty section for the help page, for testing.
 *
 * @HelpSection(
 *   id = "empty_section",
 *   title = @Translation("Empty section"),
 *   description = @Translation("This description should appear."),
 * )
 */
class EmptyHelpSection extends HelpSectionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    return [];
  }

}
