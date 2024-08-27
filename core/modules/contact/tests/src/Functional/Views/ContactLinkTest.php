<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\Views;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the contact link field.
 *
 * @group contact
 * @see \Drupal\contact\Plugin\views\field\ContactLink.
 */
class ContactLinkTest extends ViewTestBase {

  /**
   * Stores the user data service used by the test.
   *
   * @var \Drupal\user\UserDataInterface
   */
  public $userData;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_contact_link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['contact_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->userData = $this->container->get('user.data');
  }

  /**
   * Tests contact link.
   */
  public function testContactLink(): void {
    $accounts = [];
    $accounts['root'] = User::load(1);
    // Create an account with access to all contact pages.
    $admin_account = $this->drupalCreateUser(['administer users']);
    $accounts['admin'] = $admin_account;
    // Create an account with no access to contact pages.
    $no_contact_account = $this->drupalCreateUser();
    $accounts['no_contact'] = $no_contact_account;

    // Create an account with access to contact pages.
    $contact_account = $this->drupalCreateUser(['access user contact forms']);
    $accounts['contact'] = $contact_account;

    $this->drupalLogin($admin_account);
    $this->drupalGet('test-contact-link');
    // The admin user has access to all contact links beside their own.
    $this->assertContactLinks($accounts, ['root', 'no_contact', 'contact']);

    $this->drupalLogin($no_contact_account);
    $this->drupalGet('test-contact-link');
    // Ensure that the user without the permission doesn't see any link.
    $this->assertContactLinks($accounts, []);

    $this->drupalLogin($contact_account);
    $this->drupalGet('test-contact-link');
    $this->assertContactLinks($accounts, ['root', 'admin', 'no_contact']);

    // Disable contact link for no_contact.
    $this->userData->set('contact', $no_contact_account->id(), 'enabled', FALSE);
    // @todo Remove cache invalidation in https://www.drupal.org/node/2477903.
    Cache::invalidateTags($no_contact_account->getCacheTagsToInvalidate());
    $this->drupalGet('test-contact-link');
    $this->assertContactLinks($accounts, ['root', 'admin']);
  }

  /**
   * Asserts whether certain users contact links appear on the page.
   *
   * @param array $accounts
   *   All user objects used by the test.
   * @param array $names
   *   Users which should have contact links.
   *
   * @internal
   */
  public function assertContactLinks(array $accounts, array $names): void {
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "views-field-contact")]//a', count($names));
    foreach ($names as $name) {
      $account_url = $accounts[$name]->toUrl('contact-form')->toString();
      $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'views-field-contact')]//a[contains(@href, '$account_url')]");
    }
  }

}
