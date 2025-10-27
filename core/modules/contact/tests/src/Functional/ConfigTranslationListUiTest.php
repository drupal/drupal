<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Ensure the "translate" link is added to contact forms.
 *
 * @see \Drupal\Tests\config_translation\Functional\ConfigTranslationListUiTest
 */
#[Group('contact')]
#[RunTestsInSeparateProcesses]
class ConfigTranslationListUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'config_translation',
    'contact',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user with all needed permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access site-wide contact form',
      'administer contact forms',
      'translate configuration',
    ];

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the contact forms listing for the translate operation.
   */
  public function testContactFormsList(): void {
    // Create a test contact form to decouple looking for translate operations
    // link so this does not test more than necessary.
    $contact_form = ContactForm::create([
      'id' => $this->randomMachineName(16),
      'label' => $this->randomMachineName(),
    ]);
    $contact_form->save();

    // Get the contact form listing.
    $this->drupalGet('admin/structure/contact');

    $translate_link = 'admin/structure/contact/manage/' . $contact_form->id() . '/translate';
    // Test if the link to translate the contact form is on the page.
    $this->assertSession()->linkByHrefExists($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertSession()->responseContains('<th>Language</th>');
  }

}
