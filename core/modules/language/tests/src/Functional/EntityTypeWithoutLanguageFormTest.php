<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\language\Form\ContentLanguageSettingsForm;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests entity type without language support.
 *
 * This is to ensure that an entity type without language support can not
 * enable the language select from the content language settings page.
 */
#[Group('language')]
#[CoversClass(ContentLanguageSettingsForm::class)]
#[RunTestsInSeparateProcesses]
class EntityTypeWithoutLanguageFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'language_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in administrative user.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests configuration options with an entity without language definition.
   */
  public function testEmptyLangcode(): void {
    // Assert that we can not enable language select from
    // content language settings page.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->fieldNotExists('entity_types[no_language_entity_test]');
  }

}
