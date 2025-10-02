<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\update\Hook\UpdateHooks;
use Drupal\update\UpdateManagerInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests text of update email.
 */
#[Group('update')]
#[CoversMethod(UpdateHooks::class, 'mail')]
class UpdateMailTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The mocked current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Mocked language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mocked URL generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    include_once __DIR__ . '/../../../update.module';

    // Initialize the container.
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);

    // Get needed mocks.
    $this->currentUser = $this->createMock('\Drupal\Core\Session\AccountProxy');
    $this->languageManager = $this->createMock('Drupal\language\ConfigurableLanguageManagerInterface');
    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactory');
    $this->urlGenerator = $this->createMock('\Drupal\Core\Render\MetadataBubblingUrlGenerator');
  }

  /**
   * Test the subject and body of update text.
   */
  #[DataProvider('providerTestUpdateEmail')]
  public function testUpdateEmail($notification_threshold, $params, array $expected_body): void {
    $langcode = 'en';
    $available_updates_url = 'https://example.com/admin/reports/updates';
    $update_settings_url = 'https://example.com/admin/reports/updates/settings';
    $site_name = 'Test site';

    // Initialize update_mail input parameters.
    $key = NULL;
    $message = [
      'langcode' => $langcode,
      'subject' => '',
      'message' => '',
      'body' => [],
    ];

    // Language manager just returns the language.
    $this->languageManager
      ->expects($this->once())
      ->method('getLanguage')
      ->willReturn($langcode);

    // Create three config entities.
    $config_site_name = $this->createMock('Drupal\Core\Config\Config');
    $config_site_name
      ->expects($this->once())
      ->method('get')
      ->with('name')
      ->willReturn($site_name);
    $config_notification = $this->createMock('Drupal\Core\Config\Config');
    $config_notification
      ->expects($this->once())
      ->method('get')
      ->with('notification.threshold')
      ->willReturn($notification_threshold);

    $this->configFactory
      ->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['system.site', $config_site_name],
        ['update.settings', $config_notification],
      ]);

    $this->urlGenerator
      ->expects($this->exactly(2))
      ->method('generateFromRoute')
      ->willReturnMap([
        ['update.status', [], ['absolute' => TRUE, 'language' => $langcode], FALSE, $available_updates_url],
        ['update.settings', [], ['absolute' => TRUE], FALSE, $update_settings_url],
      ]);

    // Set the container.
    $this->container->set('language_manager', $this->languageManager);
    $this->container->set('url_generator', $this->urlGenerator);
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('current_user', $this->currentUser);
    \Drupal::setContainer($this->container);

    // Generate the email message.
    $updateMail = new UpdateHooks();
    $updateMail->mail($key, $message, $params);

    // Confirm the subject.
    $this->assertSame("New release(s) available for $site_name", $message['subject']);

    // Confirm each part of the body.
    for ($i = 0; $i < count($expected_body); $i++) {
      $body_part = is_string($message['body'][$i]) ? $message['body'][$i] : $message['body'][$i]->render();
      $this->assertSame($expected_body[$i], $body_part);
    }
  }

  /**
   * Provides data for ::testUpdateEmail.
   *
   * @return array
   *   - The value of the update setting 'notification.threshold'.
   *   - An array of parameters for update_mail.
   *   - An array of expected message body strings.
   */
  public static function providerTestUpdateEmail(): array {
    return [
      // Configured to notify for all available releases. Drupal Core is missing
      // an available update, there are no contrib modules installed.
      'all: only core, not current' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
        ],
        [
          'There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Drupal Core is missing
      // a security release, there are no contrib modules installed.
      'all: only core, not secure' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Drupal Core is missing
      // an available update, contrib is up to date.
      'all: core not current, contrib current' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
          'contrib' => UpdateManagerInterface::CURRENT,
        ],
        [
          'There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.',
          '',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Drupal Core is up to
      // date, but contrib is missing an available update.
      'all: core current, contrib not current' => [
        'all',
        [
          'core' => UpdateManagerInterface::CURRENT,
          'contrib' => UpdateManagerInterface::NOT_CURRENT,
        ],
        [
          '',
          'There are updates available for one or more of your modules or themes. To ensure the proper functioning of your site, you should update as soon as possible.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Both Drupal Core and
      // contrib are missing available updates.
      'all: both not current' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
          'contrib' => UpdateManagerInterface::NOT_CURRENT,
        ],
        [
          'There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.',
          'There are updates available for one or more of your modules or themes. To ensure the proper functioning of your site, you should update as soon as possible.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Core is missing a
      // security release, contrib is up to date.
      'all: core not secure, contrib current' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => UpdateManagerInterface::CURRENT,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          '',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Core is missing a
      // security release, contrib is missing a regular update.
      'all: core not secure, contrib not current' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => UpdateManagerInterface::NOT_CURRENT,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          'There are updates available for one or more of your modules or themes. To ensure the proper functioning of your site, you should update as soon as possible.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Core is up to date,
      // but contrib is missing a security update.
      'all: core current, contrib not secure' => [
        'all',
        [
          'core' => UpdateManagerInterface::CURRENT,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          '',
          'There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Core is missing a
      // regular update, contrib is missing a security update.
      'all: core not current, contrib not secure' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          'There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.',
          'There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to notify for all available releases. Both core and contrib
      // are missing a security update.
      'all: both not secure' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          'There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to only show security notifications. Core is missing a
      // security release, no contrib modules installed.
      'security: only core, not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to only show security notifications. Core is missing a
      // security release, contrib is up to date.
      'security: core not secure, contrib current' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => UpdateManagerInterface::CURRENT,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          '',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to only show security notifications. Core is missing a
      // security release, contrib is missing a regular release.
      'security: core not secure, contrib not current' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => UpdateManagerInterface::NOT_CURRENT,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          'There are updates available for one or more of your modules or themes. To ensure the proper functioning of your site, you should update as soon as possible.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to only show security notifications. Core is up to date, but
      // contrib is missing a security update.
      'security: core current, contrib not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::CURRENT,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          '',
          'There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to only show security notifications. Core is missing a
      // regular update, contrib is missing a security update.
      'security: core not current, contrib not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          'There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.',
          'There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
      // Configured to only show security notifications. Both core and contrib
      // are missing a security update.
      'security: both not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        [
          'There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!',
          'There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates/settings.',
        ],
      ],
    ];
  }

}
