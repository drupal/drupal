<?php

namespace Drupal\help_page_test\Plugin\HelpSection;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;
use Drupal\help\Attribute\HelpSection;

/**
 * Provides an empty section for the help page, for testing.
 */
#[HelpSection(
  id: 'empty_section',
  title: new TranslatableMarkup('Empty section'),
  description: new TranslatableMarkup('This description should appear.')
)]
class EmptyHelpSection extends HelpSectionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    return [];
  }

}
