<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Url;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the replacement of user tokens.
 *
 * @group user
 */
class UserTokenReplaceTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'user_hooks_test'];

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
  }

  /**
   * Creates a user, then tests the tokens generated from it.
   */
  public function testUserTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $url_options = [
      'absolute' => TRUE,
      'language' => $language_interface,
    ];

    \Drupal::state()->set('user_hooks_test_user_format_name_alter', TRUE);
    \Drupal::state()->set('user_hooks_test_user_format_name_alter_safe', TRUE);

    // Create two users and log them in one after another.
    $user1 = $this->drupalCreateUser([]);
    $user2 = $this->drupalCreateUser([]);
    $this->drupalLogin($user1);
    $this->drupalLogout();
    $this->drupalLogin($user2);

    $account = User::load($user1->id());
    $global_account = User::load(\Drupal::currentUser()->id());

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');

    // Generate and test tokens.
    $tests = [];
    $tests['[user:uid]'] = $account->id();
    $tests['[user:name]'] = $account->getAccountName();
    $tests['[user:account-name]'] = $account->getAccountName();
    $tests['[user:display-name]'] = $account->getDisplayName();
    $tests['[user:mail]'] = $account->getEmail();
    $tests['[user:url]'] = $account->toUrl('canonical', $url_options)->toString();
    $tests['[user:edit-url]'] = $account->toUrl('edit-form', $url_options)->toString();
    $tests['[user:last-login]'] = $date_formatter->format($account->getLastLoginTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[user:last-login:short]'] = $date_formatter->format($account->getLastLoginTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[user:created]'] = $date_formatter->format($account->getCreatedTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[user:created:short]'] = $date_formatter->format($account->getCreatedTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[current-user:name]'] = $global_account->getAccountName();
    $tests['[current-user:account-name]'] = $global_account->getAccountName();
    $tests['[current-user:display-name]'] = $global_account->getDisplayName();

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($account);
    $metadata_tests = [];
    $metadata_tests['[user:uid]'] = $base_bubbleable_metadata;
    $metadata_tests['[user:name]'] = $base_bubbleable_metadata;
    $metadata_tests['[user:account-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[user:display-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[user:mail]'] = $base_bubbleable_metadata;
    $metadata_tests['[user:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[user:edit-url]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    // This test runs with the Language module enabled, which means config is
    // overridden by LanguageConfigFactoryOverride (to provide translations of
    // config). This causes the interface language cache context to be added for
    // config entities. The four next tokens use DateFormat Config entities, and
    // therefore have the interface language cache context.
    $bubbleable_metadata->addCacheContexts(['languages:language_interface']);
    $metadata_tests['[user:last-login]'] = $bubbleable_metadata->addCacheTags(['rendered']);
    $metadata_tests['[user:last-login:short]'] = $bubbleable_metadata;
    $metadata_tests['[user:created]'] = $bubbleable_metadata;
    $metadata_tests['[user:created:short]'] = $bubbleable_metadata;
    $metadata_tests['[current-user:name]'] = $base_bubbleable_metadata->merge(BubbleableMetadata::createFromObject($global_account)->addCacheContexts(['user']));
    $metadata_tests['[current-user:account-name]'] = $base_bubbleable_metadata->merge(BubbleableMetadata::createFromObject($global_account)->addCacheContexts(['user']));
    $metadata_tests['[current-user:display-name]'] = $base_bubbleable_metadata->merge(BubbleableMetadata::createFromObject($global_account)->addCacheContexts(['user']));

    // Test to make sure that we generated something for each token.
    $this->assertNotContains(0, array_map('strlen', $tests), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_service->replace($input, ['user' => $account], ['langcode' => $language_interface->getId()], $bubbleable_metadata);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
      $this->assertEquals($metadata_tests[$input], $bubbleable_metadata);
    }

    // Generate tokens for the anonymous user.
    $anonymous_user = User::load(0);
    $tests = [];
    $tests['[user:uid]'] = 'not yet assigned';
    $tests['[user:display-name]'] = $anonymous_user->getDisplayName();

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($anonymous_user);
    $metadata_tests = [];
    $metadata_tests['[user:uid]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $bubbleable_metadata->addCacheableDependency(\Drupal::config('user.settings'));
    $metadata_tests['[user:display-name]'] = $bubbleable_metadata;

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_service->replace($input, ['user' => $anonymous_user], ['langcode' => $language_interface->getId()], $bubbleable_metadata);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
      $this->assertEquals($metadata_tests[$input], $bubbleable_metadata);
    }

    // Generate login and cancel link.
    $tests = [];
    $tests['[user:one-time-login-url]'] = user_pass_reset_url($account);
    $tests['[user:cancel-url]'] = user_cancel_url($account);

    // Generate tokens with interface language.
    $link = Url::fromRoute('user.page', [], ['absolute' => TRUE])->toString();
    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, ['user' => $account], ['langcode' => $language_interface->getId(), 'callback' => 'user_mail_tokens', 'clear' => TRUE]);
      $this->assertStringStartsWith($link, $output, 'Generated URL is in interface language.');
    }

    // Generate tokens with the user's preferred language.
    $account->preferred_langcode = 'de';
    $account->save();
    $link = Url::fromRoute('user.page', [], ['language' => \Drupal::languageManager()->getLanguage($account->getPreferredLangcode()), 'absolute' => TRUE])->toString();
    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, ['user' => $account], ['callback' => 'user_mail_tokens', 'clear' => TRUE]);
      $this->assertStringStartsWith($link, $output, "Generated URL is in the user's preferred language.");
    }

    // Generate tokens with one specific language.
    $link = Url::fromRoute('user.page', [], ['language' => \Drupal::languageManager()->getLanguage('de'), 'absolute' => TRUE])->toString();
    foreach ($tests as $input => $expected) {
      foreach ([$user1, $user2] as $account) {
        $output = $token_service->replace($input, ['user' => $account], ['langcode' => 'de', 'callback' => 'user_mail_tokens', 'clear' => TRUE]);
        $this->assertStringStartsWith($link, $output, "Generated URL in the requested language.");
      }
    }

    // Generate user display name tokens when safe markup is returned.
    // @see user_hooks_test_user_format_name_alter()
    \Drupal::state()->set('user_hooks_test_user_format_name_alter_safe', TRUE);
    $input = '[user:display-name] [current-user:display-name]';
    $expected = "<em>{$user1->id()}</em> <em>{$user2->id()}</em>";
    $output = $token_service->replace($input, ['user' => $user1]);
    $this->assertSame($expected, (string) $output);
  }

}
