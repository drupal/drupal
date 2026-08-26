<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Hook\UserHooks;
use Drupal\user\NotificationHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;

/**
 * Tests NotificationHandler use of user.settings.notify.*.
 */
#[CoversClass(NotificationHandler::class)]
#[Group('user')]
#[RunTestsInSeparateProcesses]
class NotificationHandlerTest extends EntityKernelTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'language',
  ];

  /**
   * Data provider for user mail testing.
   *
   * @return array
   *   An array of operations and the mail keys they should send.
   */
  public static function userMailsProvider(): array {
    return [
      'cancel confirm notification' => [
        'sendCancelConfirm',
        'notify.cancel_confirm',
        ['cancel_confirm'],
      ],
      'password reset notification' => [
        'sendPasswordReset',
        'notify.password_reset',
        ['password_reset'],
      ],
      'status activated notification' => [
        'sendStatusActivated',
        'notify.status_activated',
        ['status_activated'],
      ],
      'status blocked notification' => [
        'sendStatusBlocked',
        'notify.status_blocked',
        ['status_blocked'],
      ],
      'status canceled notification' => [
        'sendStatusCanceled',
        'notify.status_canceled',
        ['status_canceled'],
      ],
      'register admin created notification' => [
        'sendRegisterAdminCreated',
        'notify.register_admin_created',
        ['register_admin_created'],
      ],
      'register no approval required notification' => [
        'sendRegisterNoApprovalRequired',
        'notify.register_no_approval_required',
        ['register_no_approval_required'],
      ],
      'register pending approval notification' => [
        'sendRegisterPendingApproval',
        'notify.register_pending_approval',
        ['register_pending_approval', 'register_pending_approval_admin'],
      ],
    ];
  }

  /**
   * Tests that mails are sent when the appropriate notify configuration is set.
   *
   * @param string $method
   *   The method to call on the mail handler.
   * @param string $configKey
   *   The config key to test.
   * @param array $emailKeys
   *   The mail keys to test for.
   */
  #[DataProvider('userMailsProvider')]
  public function testUserMailsSent(string $method, string $configKey, array $emailKeys): void {
    $this->installConfig('user');
    $this->config('system.site')->set('mail', 'test@example.com')->save();
    $this->config('user.settings')->set($configKey, TRUE)->save();
    $notificationHandler = \Drupal::service(NotificationHandler::class);
    $return = $notificationHandler->{$method}($this->createUser());
    $this->assertTrue($return);
    foreach ($emailKeys as $key) {
      $filter = ['key' => $key];
      $this->assertNotEmpty($this->getMails($filter), "The mail for $key should be sent");
    }
    $this->assertSameSize($emailKeys, $this->getMails());
  }

  /**
   * Tests that mails are not sent when disabled.
   *
   * @param string $method
   *   The method to call on the mail handler.
   * @param string $configKey
   *   The config key to test.
   * @param array $emailKeys
   *   The mail keys to test for.
   */
  #[DataProvider('userMailsProvider')]
  public function testUserMailsNotSent(string $method, string $configKey, array $emailKeys): void {
    $this->installConfig('user');
    $this->config('system.site')->set('mail', 'test@example.com')->save();
    $this->config('user.settings')->set($configKey, FALSE)->save();
    $notificationHandler = \Drupal::service(NotificationHandler::class);
    $return = $notificationHandler->{$method}($this->createUser());
    $this->assertFalse($return);
    $this->assertEmpty($this->getMails());
  }

  /**
   * Tests mails are not sent when the account has no email address.
   *
   * @param string $method
   *   The method to call on the mail handler.
   * @param string $configKey
   *   The config key to test.
   * @param array $emailKeys
   *   The mail keys to test for.
   */
  #[DataProvider('userMailsProvider')]
  public function testUserMailsWithoutAccountEmail(string $method, string $configKey, array $emailKeys): void {
    $this->installConfig('user');
    $this->config('system.site')->set('mail', 'test@example.com')->save();
    $this->config('user.settings')->set($configKey, TRUE)->save();

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('log');
    /** @var \Drupal\Core\Logger\LoggerChannelFactory $logger_factory */
    $logger_factory = $this->container->get('logger.factory');
    $logger_factory->get('user')
      ->addLogger($logger);

    $notificationHandler = \Drupal::service(NotificationHandler::class);
    $return = $notificationHandler->{$method}($this->createUser([], NULL, FALSE, [
      'mail' => NULL,
    ]));

    $this->assertFalse($return);
    if ($configKey == 'notify.register_pending_approval') {
      // The register_pending_approval op will cause an email to be sent to the
      // site address.
      $this->assertCount(1, $this->getMails());
    }
    else {
      $this->assertEmpty($this->getMails());
    }
  }

  /**
   * Tests recovery email content and token langcode is aligned.
   */
  public function testUserRecoveryMailLanguage(): void {

    // Install locale schema.
    $this->installSchema('locale', [
      'locales_source',
      'locales_target',
      'locales_location',
    ]);

    // Add new language for translation purpose.
    ConfigurableLanguage::createFromLangcode('zh-hant')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Install configs.
    $this->installConfig(['system', 'language', 'locale', 'user']);

    $locale_config_manager = \Drupal::service('locale.config_manager');
    $locale_config_manager->updateDefaultConfigLangcodes();
    $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
    $names = $locale_config_manager->getComponentNames();
    $locale_config_manager->updateConfigTranslations($names, $langcodes);

    $this->config('system.site')->set('mail', 'test@example.com')->save();
    $this->config('user.settings')->set('notify.password_reset', TRUE)->save();

    // Set language prefix.
    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['en' => 'en', 'zh-hant' => 'zh', 'fr' => 'fr'])->save();

    // Reset services to apply change.
    \Drupal::service('kernel')->rebuildContainer();

    // Update zh-hant password_reset config with custom translation.
    $configLanguageOverride = $this->container->get('language_manager')->getLanguageConfigOverride('zh-hant', 'user.mail');
    $configLanguageOverride->set('password_reset.subject', 'hant subject [user:display-name]')->save();
    $configLanguageOverride->set('password_reset.body', 'hant body [user:display-name] and token link [user:one-time-login-url]')->save();

    // Update fr password_reset config with custom translation.
    $configLanguageOverride = $this->container->get('language_manager')->getLanguageConfigOverride('fr', 'user.mail');
    $configLanguageOverride->set('password_reset.subject', 'fr subject [user:display-name]')->save();
    $configLanguageOverride->set('password_reset.body', 'fr body [user:display-name] and token link [user:one-time-login-url]')->save();

    // Current language is 'en'.
    $currentLanguage = $this->container->get('language_manager')->getCurrentLanguage()->getId();
    $this->assertSame('en', $currentLanguage);

    // Set preferred_langcode to 'zh-hant'.
    $user = $this->createUser();
    $user->set('preferred_langcode', 'zh-hant')->save();
    $preferredLangcode = $user->getPreferredLangcode();
    $this->assertSame('zh-hant', $preferredLangcode);

    // Recovery email should respect user preferred langcode by default if
    // langcode not set.
    $this->config('system.site')->set('mail', 'test@example.com')->save();
    $params['account'] = $user;
    $default_email = \Drupal::service('plugin.manager.mail')->mail('user', 'password_reset', $user->getEmail(), $preferredLangcode, $params);
    $this->assertTrue($default_email['result']);

    // Assert for zh.
    $this->assertMailString('subject', 'hant subject', 1);
    $this->assertMailString('body', 'hant body', 1);
    $this->assertMailString('body', 'zh/user/reset', 1);

    // Recovery email should be fr when langcode specified.
    $french_email = \Drupal::service('plugin.manager.mail')->mail('user', 'password_reset', $user->getEmail(), 'fr', $params);
    $this->assertTrue($french_email['result']);

    // Assert for fr.
    $this->assertMailString('subject', 'fr subject', 1);
    $this->assertMailString('body', 'fr body', 1);
    $this->assertMailString('body', 'fr/user/reset', 1);

  }

  /**
   * Tests the mail hook implementation from the user module.
   */
  public function testUserMailHook(): void {
    $this->installConfig('user');
    $config = $this->config('system.site');
    $config->set('langcode', 'en');
    // Use a name that could trigger HTML entity replacements.
    // cspell:ignore L'Equipe de l'Agriculture
    $config->set('name', "L'Equipe de l'Agriculture")->save();

    $hooks = \Drupal::service(UserHooks::class);
    $user = $this->createUser();
    $message = ['langcode' => 'en', 'subject' => 'Test subject: '];
    $hooks->mail('password_reset', $message, ['account' => $user]);
    $this->assertSame('Test subject: Replacement login information for ' . $user->label() . " at L'Equipe de l'Agriculture", $message['subject']);
    $this->assertStringContainsString(
      "A request to reset the password for your account has been made at L'Equipe de l'Agriculture",
      $message['body'][0]
    );
    $this->assertStringContainsString(
      "--  L'Equipe de l'Agriculture team",
      $message['body'][0]
    );
  }

}
