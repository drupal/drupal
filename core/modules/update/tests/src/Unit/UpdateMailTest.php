<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\update\UpdateManagerInterface;

/**
 * Tests text of update email.
 *
 * @covers \update_mail
 *
 * @group update
 */
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
   *
   * @dataProvider providerTestUpdateEmail
   */
  public function testUpdateEmail($notification_threshold, $params, $authorized, array $expected_body): void {
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

    // The calls to generateFromRoute differ if authorized.
    $count = 2;
    if ($authorized) {
      $this->currentUser
        ->expects($this->once())
        ->method('hasPermission')
        ->with('administer software updates')
        ->willReturn(TRUE);
      $count = 3;
    }
    // When authorized also get the URL for the route 'update.report_update'.
    $this->urlGenerator
      ->expects($this->exactly($count))
      ->method('generateFromRoute')
      ->willReturnMap([
        ['update.status', [], ['absolute' => TRUE, 'language' => $langcode], FALSE, $update_settings_url],
        ['update.settings', [], ['absolute' => TRUE], FALSE, $available_updates_url],
        ['update.report_update', [], ['absolute' => TRUE, 'language' => $langcode], FALSE, $available_updates_url],
      ]);

    // Set the container.
    $this->container->set('language_manager', $this->languageManager);
    $this->container->set('url_generator', $this->urlGenerator);
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('current_user', $this->currentUser);
    \Drupal::setContainer($this->container);

    // Generate the email message.
    update_mail($key, $message, $params);

    // Confirm the subject.
    $this->assertSame("New release(s) available for $site_name", $message['subject']);

    // Confirm each part of the body.
    if ($authorized) {
      $this->assertSame($expected_body[0], $message['body'][0]);
      $this->assertSame($expected_body[1], $message['body'][1]);
      $this->assertSame($expected_body[2], $message['body'][2]->render());
    }
    else {
      if (empty($params)) {
        $this->assertSame($expected_body[0], $message['body'][0]);
        $this->assertSame($expected_body[1], $message['body'][1]->render());
      }
      else {
        $this->assertSame($expected_body[0], $message['body'][0]->render());
        $this->assertSame($expected_body[1], $message['body'][1]);
        $this->assertSame($expected_body[2], $message['body'][2]);
        $this->assertSame($expected_body[3], $message['body'][3]->render());
      }
    }
  }

  /**
   * Provides data for ::testUpdateEmail.
   *
   * @return array
   *   - The value of the update setting 'notification.threshold'.
   *   - An array of parameters for update_mail.
   *   - TRUE if the user is authorized.
   *   - An array of message body strings.
   */
  public static function providerTestUpdateEmail(): array {
    return [
      'all' => [
        'all',
        [],
        FALSE,
        [
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.',
        ],
      ],
      'security' => [
        'security',
        [],
        FALSE,
        [
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          'Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates.',
        ],
      ],
      // Choose parameters that do not require changes to the mocks.
      'not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => NULL,
        ],
        FALSE,
        [
          "There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!",
          '',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'authorize' => [
        'all',
        [],
        TRUE,
        [
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "You can automatically download your missing updates using the Update manager:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.',
        ],
      ],
    ];
  }

}
