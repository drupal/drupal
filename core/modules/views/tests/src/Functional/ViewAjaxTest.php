<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

/**
 * Tests the ajax view functionality.
 *
 * @group views
 */
class ViewAjaxTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_ajax_view', 'test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests an ajax view.
   */
  public function testAjaxView(): void {
    $this->drupalGet('test_ajax_view');

    $drupal_settings = $this->getDrupalSettings();
    $this->assertTrue(isset($drupal_settings['views']['ajax_path']), 'The Ajax callback path is set in drupalSettings.');
    $this->assertCount(1, $drupal_settings['views']['ajaxViews']);
    $view_entry = array_keys($drupal_settings['views']['ajaxViews'])[0];
    $this->assertEquals('test_ajax_view', $drupal_settings['views']['ajaxViews'][$view_entry]['view_name'], 'The view\'s ajaxViews array entry has the correct \'view_name\' key.');
    $this->assertEquals('page_1', $drupal_settings['views']['ajaxViews'][$view_entry]['view_display_id'], 'The view\'s ajaxViews array entry has the correct \'view_display_id\' key.');
  }

  /**
   * Ensures that non-ajax view cannot be accessed via an ajax HTTP request.
   */
  public function testNonAjaxViewViaAjax(): void {
    $client = $this->getHttpClient();
    $response = $client->request('POST', $this->buildUrl('views/ajax'), [
      'form_params' => ['view_name' => 'test_ajax_view', 'view_display_id' => 'default'],
      'query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax'],
    ]);
    $this->assertEquals(200, $response->getStatusCode());
    $response = $client->request('POST', $this->buildUrl('views/ajax'), [
      'form_params' => ['view_name' => 'test_view', 'view_display_id' => 'default'],
      'query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax'],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Tests that an ajax view response is cacheable.
   */
  public function testAjaxViewCache(): void {
    $this->drupalGet('views/ajax', [
      'query' => [
        'view_name' => 'test_ajax_view',
        'view_display_id' => 'page_1',
      ],
    ]);

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->responseHeaderEquals('X-Drupal-Cache-Tags', 'config:user.role.anonymous config:views.view.test_ajax_view http_response');
    $this->assertSession()
      ->responseHeaderEquals('X-Drupal-Cache-Contexts', 'languages:language_interface route theme url.query_args user.permissions');
    $this->assertSession()
      ->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
  }

}
