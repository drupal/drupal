<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Kernel;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\update\Hook\UpdateThemeHooks;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests update report functionality.
 */
#[CoversClass(UpdateThemeHooks::class)]
#[Group('update')]
#[RunTestsInSeparateProcesses]
class UpdateReportTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'update',
  ];

  /**
   * Tests template preprocess update report.
   *
   * @legacy-covers ::preprocessUpdateReport
   */
  #[DataProvider('providerTemplatePreprocessUpdateReport')]
  public function testTemplatePreprocessUpdateReport($variables): void {
    // The function should run without an exception being thrown when the value
    // of $variables['data'] is not set or is not an array.
    \Drupal::service(UpdateThemeHooks::class)->preprocessUpdateReport($variables);

    // Test that the key "no_updates_message" has been set.
    $this->assertArrayHasKey('no_updates_message', $variables);
  }

  /**
   * Provides data for testTemplatePreprocessUpdateReport().
   *
   * @return array
   *   Array of $variables for
   *   \Drupal\update\Hook\UpdateThemeHooks::preprocessUpdateReport().
   */
  public static function providerTemplatePreprocessUpdateReport() {
    return [
      '$variables with data not set' => [
        [],
      ],
      '$variables with data as an integer' => [
        ['data' => 4],
      ],
      '$variables with data as a string' => [
        ['data' => 'I am a string'],
      ],
    ];
  }

  /**
   * Tests the error message when failing to fetch data without dblog installed.
   *
   * @legacy-covers ::preprocessUpdateFetchErrorMessage
   */
  public function testTemplatePreprocessUpdateFetchErrorMessageNoDblog(): void {
    $build = [
      '#theme' => 'update_fetch_error_message',
    ];
    $this->render($build);
    $this->assertRaw('Failed to fetch available update data:<ul><li>See <a href="https://www.drupal.org/node/3170647">PHP OpenSSL requirements</a> in the Drupal.org handbook for possible reasons this could happen and what you can do to resolve them.</li><li>Check your local system logs for additional error messages.</li></ul>');

    $variables = [];
    \Drupal::service(UpdateThemeHooks::class)->preprocessUpdateFetchErrorMessage($variables);
    $this->assertArrayHasKey('error_message', $variables);
    $this->assertEquals('Failed to fetch available update data:', $variables['error_message']['message']['#markup']);
    $this->assertArrayHasKey('documentation_link', $variables['error_message']['items']['#items']);
    $this->assertArrayHasKey('logs', $variables['error_message']['items']['#items']);
    $this->assertArrayNotHasKey('dblog', $variables['error_message']['items']['#items']);
  }

  /**
   * Tests the error message when failing to fetch data with dblog installed.
   *
   * @legacy-covers ::preprocessUpdateFetchErrorMessage
   */
  public function testTemplatePreprocessUpdateFetchErrorMessageWithDblog(): void {

    $this->enableModules(['dblog', 'user']);
    $this->installEntitySchema('user');

    // First, try as a normal user that can't access dblog.
    $this->setUpCurrentUser();

    $build = [
      '#theme' => 'update_fetch_error_message',
    ];
    $this->render($build);
    $this->assertRaw('Failed to fetch available update data:<ul><li>See <a href="https://www.drupal.org/node/3170647">PHP OpenSSL requirements</a> in the Drupal.org handbook for possible reasons this could happen and what you can do to resolve them.</li><li>Check your local system logs for additional error messages.</li></ul>');

    $variables = [];
    \Drupal::service(UpdateThemeHooks::class)->preprocessUpdateFetchErrorMessage($variables);
    $this->assertArrayHasKey('error_message', $variables);
    $this->assertEquals('Failed to fetch available update data:', $variables['error_message']['message']['#markup']);
    $this->assertArrayHasKey('documentation_link', $variables['error_message']['items']['#items']);
    $this->assertArrayHasKey('logs', $variables['error_message']['items']['#items']);
    $this->assertArrayNotHasKey('dblog', $variables['error_message']['items']['#items']);

    // Now, try as an admin that can access dblog.
    $this->setUpCurrentUser([], ['access content', 'access site reports']);

    $this->render($build);
    $this->assertRaw('Failed to fetch available update data:<ul><li>See <a href="https://www.drupal.org/node/3170647">PHP OpenSSL requirements</a> in the Drupal.org handbook for possible reasons this could happen and what you can do to resolve them.</li><li>Check');
    $dblog_url = Url::fromRoute('dblog.overview', [], ['query' => ['type' => ['update']]]);
    $this->assertRaw((string) Link::fromTextAndUrl('your local system logs', $dblog_url)->toString());
    $this->assertRaw(' for additional error messages.</li></ul>');

    $variables = [];
    \Drupal::service(UpdateThemeHooks::class)->preprocessUpdateFetchErrorMessage($variables);
    $this->assertArrayHasKey('error_message', $variables);
    $this->assertEquals('Failed to fetch available update data:', $variables['error_message']['message']['#markup']);
    $this->assertArrayHasKey('documentation_link', $variables['error_message']['items']['#items']);
    $this->assertArrayNotHasKey('logs', $variables['error_message']['items']['#items']);
    $this->assertArrayHasKey('dblog', $variables['error_message']['items']['#items']);
  }

}
