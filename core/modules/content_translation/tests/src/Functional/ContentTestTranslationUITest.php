<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

/**
 * Tests the test content translation UI with the test entity.
 *
 * @group content_translation
 */
class ContentTestTranslationUITest extends ContentTranslationUITestBase {

  /**
   * {@inheritdoc}
   */
  protected $testHTMLEscapeForAllLanguages = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'theme',
    'url.query_args:_wrapper_format',
    'user.permissions',
    'url.site',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Use the entity_test_mul as this has multilingual property support.
    $this->entityTypeId = 'entity_test_mul_changed';
    parent::setUp();
    $this->doSetup();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer entity_test content', 'view test entity']);
  }

}
