<?php

namespace Drupal\Tests\update\Kernel;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests update report functionality.
 *
 * @covers template_preprocess_update_report()
 * @group update
 */
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
   * @dataProvider providerTemplatePreprocessUpdateReport
   */
  public function testTemplatePreprocessUpdateReport($variables) {
    \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.report');

    // The function should run without an exception being thrown when the value
    // of $variables['data'] is not set or is not an array.
    template_preprocess_update_report($variables);

    // Test that the key "no_updates_message" has been set.
    $this->assertArrayHasKey('no_updates_message', $variables);
  }

  /**
   * Provides data for testTemplatePreprocessUpdateReport().
   *
   * @return array
   *   Array of $variables for template_preprocess_update_report().
   */
  public function providerTemplatePreprocessUpdateReport() {
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
   * Tests the error message when failing to fetch data without dblog enabled.
   *
   * @see template_preprocess_update_fetch_error_message()
   */
  public function testTemplatePreprocessUpdateFetchErrorMessageNoDblog() {
    $build = [
      '#theme' => 'update_fetch_error_message',
    ];
    $this->render($build);
    $this->assertRaw('Failed to fetch available update data:<ul><li>See <a href="https://www.drupal.org/node/3170647">PHP OpenSSL requirements</a> in the Drupal.org handbook for possible reasons this could happen and what you can do to resolve them.</li><li>Check your local system logs for additional error messages.</li></ul>');

    \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.report');
    $variables = [];
    template_preprocess_update_fetch_error_message($variables);
    $this->assertArrayHasKey('error_message', $variables);
    $this->assertEquals('Failed to fetch available update data:', $variables['error_message']['message']['#markup']);
    $this->assertArrayHasKey('documentation_link', $variables['error_message']['items']['#items']);
    $this->assertArrayHasKey('logs', $variables['error_message']['items']['#items']);
    $this->assertArrayNotHasKey('dblog', $variables['error_message']['items']['#items']);
  }

  /**
   * Tests the error message when failing to fetch data with dblog enabled.
   *
   * @see template_preprocess_update_fetch_error_message()
   */
  public function testTemplatePreprocessUpdateFetchErrorMessageWithDblog() {
    \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.report');

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
    template_preprocess_update_fetch_error_message($variables);
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
    $this->assertRaw(Link::fromTextAndUrl('your local system logs', $dblog_url)->toString());
    $this->assertRaw(' for additional error messages.</li></ul>');

    $variables = [];
    template_preprocess_update_fetch_error_message($variables);
    $this->assertArrayHasKey('error_message', $variables);
    $this->assertEquals('Failed to fetch available update data:', $variables['error_message']['message']['#markup']);
    $this->assertArrayHasKey('documentation_link', $variables['error_message']['items']['#items']);
    $this->assertArrayNotHasKey('logs', $variables['error_message']['items']['#items']);
    $this->assertArrayHasKey('dblog', $variables['error_message']['items']['#items']);
  }

}
