<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests performance for JSON:API routes.
 */
#[Group('Common')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
class JsonApiPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'jsonapi',
    'node',
    'dynamic_page_cache',
    'page_cache',
  ];

  /**
   * Tests performance of the navigation toolbar.
   */
  public function testGetIndividual(): void {

    $this->drupalCreateContentType(['type' => 'article']);
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Example article',
      'uuid' => '677f9911-f002-4639-9891-5c39e8b00d9d',
    ]);

    $user = $this->drupalCreateUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    sleep(2);

    // Request the front page to ensure all cache collectors are fully
    // warmed, wait one second to ensure that the request finished processing.
    $this->drupalGet('');
    sleep(2);

    $url = Url::fromRoute('jsonapi.node--article.individual', ['entity' => $node->uuid()])->toString();
    $performance_data = $this->collectPerformanceData(function () use ($url): void {
      $this->drupalGet($url);
    }, 'jsonapi_individual_cool_cache');

    $this->assertQueriesByName('jsonapi_individual_cool_cache', $performance_data->getQueries());
    $this->assertMetricsByName('jsonapi_individual_cool_cache', $performance_data);

    sleep(2);

    $url = Url::fromRoute('jsonapi.node--article.individual', ['entity' => $node->uuid()])->toString();
    $performance_data = $this->collectPerformanceData(function () use ($url): void {
      $this->drupalGet($url);
    }, 'jsonapi_individual_hot_cache');

    $this->assertQueriesByName('jsonapi_individual_hot_cache', $performance_data->getQueries());
    $this->assertMetricsByName('jsonapi_individual_hot_cache', $performance_data);
    $this->assertSame(['jsonapi.resource_types'], $performance_data->getCacheOperations()['get']['default']);

    $node->save();

    $url = Url::fromRoute('jsonapi.node--article.individual', ['entity' => $node->uuid()])->toString();
    $performance_data = $this->collectPerformanceData(function () use ($url): void {
      $this->drupalGet($url);
    }, 'jsonapi_node_individual_invalidated');

    $this->assertQueriesByName('jsonapi_node_individual_invalidated', $performance_data->getQueries());
    $this->assertMetricsByName('jsonapi_node_individual_invalidated', $performance_data);
    $this->assertSame([
      'jsonapi.resource_types',
      'jsonapi.resource_type.node.article',
      'jsonapi.resource_type.node_type.node_type',
      'jsonapi.resource_type.user.user',
    ], $performance_data->getCacheOperations()['get']['default']);
  }

}
