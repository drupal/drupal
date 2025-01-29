<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests performance with the navigation toolbar enabled.
 *
 * Stark is used as the default theme so that this test is not Olivero specific.
 *
 * @todo move this coverage to StandardPerformanceTest when Navigation is
 * enabled by default.
 *
 * @group Common
 * @group #slow
 * @requires extension apcu
 */
class PerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Uninstall the toolbar.
    \Drupal::service('module_installer')->uninstall(['toolbar']);
    \Drupal::service('module_installer')->install(['navigation']);
  }

  /**
   * Tests performance of the navigation toolbar.
   */
  public function testLogin(): void {
    $user = $this->drupalCreateUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    // Request the front page twice to ensure all cache collectors are fully
    // warmed. The exact contents of cache collectors depends on the order in
    // which requests complete so this ensures that the second request completes
    // after asset aggregates are served.
    $this->drupalGet('');
    sleep(1);
    $this->drupalGet('');
    // Flush the dynamic page cache to simulate visiting a page that is not
    // already fully cached.
    \Drupal::cache('dynamic_page_cache')->deleteAll();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'navigation');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
    ];

    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $this->assertSame(4, $performance_data->getQueryCount());
    $this->assertSame(61, $performance_data->getCacheGetCount());
    $this->assertSame(2, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
    $this->assertSame(2, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(29, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());
    $this->assertSame(1, $performance_data->getStyleSheetCount());
    $this->assertSame(2, $performance_data->getScriptCount());
    $this->assertLessThan(90200, $performance_data->getStylesheetBytes());
    $this->assertLessThan(220000, $performance_data->getScriptBytes());

    // Check that the navigation toolbar is cached without any high-cardinality
    // cache contexts (user, route, query parameters etc.).
    $this->assertIsObject(\Drupal::cache('render')->get('navigation:navigation:[languages:language_interface]=en:[theme]=stark:[user.permissions]=is-admin'));
  }

}
