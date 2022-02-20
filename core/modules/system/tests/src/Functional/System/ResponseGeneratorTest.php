<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests to see if generator header is added.
 *
 * @group system
 */
class ResponseGeneratorTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['serialization', 'rest', 'node', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $account = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($account);
  }

  /**
   * Tests to see if generator header is added.
   */
  public function testGeneratorHeaderAdded() {

    $node = $this->drupalCreateNode();

    [$version] = explode('.', \Drupal::VERSION, 2);
    $expectedGeneratorHeader = 'Drupal ' . $version . ' (https://www.drupal.org)';

    // Check to see if the header is added when viewing an HTML page.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/html; charset=UTF-8');
    $this->assertSession()->responseHeaderEquals('X-Generator', $expectedGeneratorHeader);

    // Check to see if the header is also added for a non-successful response.
    $this->drupalGet('llama');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/html; charset=UTF-8');
    $this->assertSession()->responseHeaderEquals('X-Generator', $expectedGeneratorHeader);

    // Create a cookie-based authentication for the entity:node REST resource.
    // @todo Turn this back in to an optional config YAML file in D10 to have an
    //   example config for REST endpoints and adjust
    //   core/modules/help_topics/help_topics/core.web_services.html.twig and
    //   core/core.api.php accordingly.
    //   See https://www.drupal.org/project/drupal/issues/3049857
    $resource_config_values = [
      'id' => 'entity.node',
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => [
          'GET',
        ],
        'formats' => [
          'json',
        ],
        'authentication' => [
          'cookie',
        ],
      ],
    ];

    RestResourceConfig::create($resource_config_values)->save();
    $this->rebuildAll();

    // Check to see if the header is also added for a non-HTML request.
    $this->drupalGet($node->toUrl()->setOption('query', ['_format' => 'json']));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/json');
    $this->assertSession()->responseHeaderEquals('X-Generator', $expectedGeneratorHeader);

  }

}
