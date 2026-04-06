<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cSpell:ignore Angemeldeter Benutzer userroleauthenticated

/**
 * Tests the config translation behaviors when editing roles and permissions.
 */
#[Group('config_translation')]
#[RunTestsInSeparateProcesses]
class ConfigTranslationRoleUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'config_translation',
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

    ConfigurableLanguage::createFromLangcode('de')->save();
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'translate configuration',
      'administer users',
      'administer permissions',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Test that permissions can be saved with translated role labels.
   */
  public function testRoleUi(): void {
    $this->drupalGet('admin/people/roles/manage/authenticated/translate/de/add');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $page->fillField('Label', 'Angemeldeter Benutzer');
    $page->pressButton('Save translation');
    $assert_session->pageTextContains('Successfully saved German translation.');

    $this->drupalGet('de/admin/people/permissions');
    $page->checkField('authenticated[change own username]');
    $page->pressButton('Save permissions');
    $assert_session->pageTextContains('The changes have been saved.');

    $page->uncheckField('authenticated[change own username]');
    $page->pressButton('Save permissions');
    $assert_session->pageTextContains('The changes have been saved.');

    $this->drupalGet('admin/people/roles');
    $assert_session->pageTextContains('Authenticated user');

    $this->drupalGet('de/admin/people/roles/manage/authenticated');
    $assert_session->fieldValueEquals('Role name', 'Authenticated user');

    $this->drupalGet('admin/people/roles/manage/authenticated/translate/de/edit');
    $assert_session->fieldValueEquals('Label', 'Angemeldeter Benutzer');
    $assert_session->elementContains('css', '.form-item-source-config-names-userroleauthenticated-label', 'Authenticated user');

  }

}
