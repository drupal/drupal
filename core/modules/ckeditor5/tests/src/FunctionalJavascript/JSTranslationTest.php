<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

// cspell:ignore drupalmediatoolbar

/**
 * Tests for CKEditor 5 plugins using Drupal's translation system.
 *
 * @group ckeditor5
 * @internal
 */
class JSTranslationTest extends CKEditor5TestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
    'media_library',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
  }

  /**
   * Integration test to ensure that CKEditor 5 Plugins translations are loaded.
   */
  public function test(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-drupalMedia'));
    $this->click('#edit-filters-media-embed-status');
    $assert_session->assertExpectedAjaxRequest(2);
    $this->triggerKeyUp('.ckeditor5-toolbar-item-drupalMedia', 'ArrowDown');
    $assert_session->assertExpectedAjaxRequest(3);
    $this->saveNewTextFormat($page, $assert_session);

    $langcode = 'fr';
    ConfigurableLanguage::createFromLangcode($langcode)->save();
    $this->config('system.site')->set('default_langcode', $langcode)->save();

    // Visit a page that will trigger a JavaScript file parsing for
    // translatable strings.
    $this->drupalGet('node/add');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));

    // Ensure a string from the CKEditor 5 plugin is picked up by translation.
    // @see core/modules/ckeditor5/js/ckeditor5_plugins/drupalMedia/src/drupalmediatoolbar.js
    $locale_storage = $this->container->get('locale.storage');
    $string = $locale_storage->findString(['source' => 'Drupal Media toolbar', 'context' => '']);
    $this->assertNotEmpty($string, 'String from JavaScript file saved.');
  }

}
