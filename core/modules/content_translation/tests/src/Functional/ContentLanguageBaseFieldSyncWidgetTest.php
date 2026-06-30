<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests base-field translation sync options on content language settings form.
 */
#[RunTestsInSeparateProcesses]
#[Group('content_translation')]
final class ContentLanguageBaseFieldSyncWidgetTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'media',
    'media_test_source',
  ];

  /**
   * Tests translation sync options appear for translatable base fields.
   */
  public function testBaseFieldSyncOptionsVisibleOnFirstLoad(): void {
    $account = $this->drupalCreateUser([
      'administer languages',
      'administer content translation',
      'administer media',
      'administer media types',
    ]);
    $this->drupalLogin($account);

    $media_type = $this->createMediaType('test');

    $this->drupalGet('admin/config/regional/content-language');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);

    // Regression test: before any save/create of a base field override, the
    // column-group sync options for a translatable base field must be present.
    $assert_session->elementExists('css', 'input[name="settings[media][' . $media_type->id() . '][fields][thumbnail]"]');
    $assert_session->elementExists('css', 'input[name="settings[media][' . $media_type->id() . '][columns][thumbnail][file]"]');
    $assert_session->elementExists('css', 'input[name="settings[media][' . $media_type->id() . '][columns][thumbnail][alt]"]');
    $assert_session->elementExists('css', 'input[name="settings[media][' . $media_type->id() . '][columns][thumbnail][title]"]');

    // Ensure the form can be saved without config schema errors.
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('Settings successfully updated.');
  }

}
