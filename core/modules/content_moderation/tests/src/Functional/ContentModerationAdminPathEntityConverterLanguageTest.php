<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\language\Functional\AdminPathEntityConverterLanguageTest;

/**
 * Test administration path based entity conversion when moderation enabled.
 *
 * @group content_moderation
 */
class ContentModerationAdminPathEntityConverterLanguageTest extends AdminPathEntityConverterLanguageTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'language_test',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
