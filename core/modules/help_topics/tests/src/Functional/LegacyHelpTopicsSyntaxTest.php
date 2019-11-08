<?php

namespace Drupal\Tests\help_topics\Functional;

/**
 * Extends HelpTopicsSyntaxTest to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group help_topics
 * @group legacy
 */
class LegacyHelpTopicsSyntaxTest extends HelpTopicsSyntaxTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
