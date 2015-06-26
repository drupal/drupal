<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ResponseGeneratorTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests to see if generator header is added.
 *
 * @group system
 */
class ResponseGeneratorTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $permissions = $this->entityPermissions('node', 'view');
    $permissions[] = 'restful get entity:node';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
  }

  /**
   * Test to see if generator header is added.
   */
  function testGeneratorHeaderAdded() {

    $node = $this->drupalCreateNode();

    list($version) = explode('.', \Drupal::VERSION, 2);
    $expectedGeneratorHeader = 'Drupal ' . $version . ' (https://www.drupal.org)';

    // Check to see if the header is added when viewing a normal content page
    $this->drupalGet($node->urlInfo());
    $this->assertResponse(200);
    $this->assertEqual('text/html; charset=UTF-8', $this->drupalGetHeader('Content-Type'));
    $this->assertEqual($expectedGeneratorHeader, $this->drupalGetHeader('X-Generator'));

    // Check to see if the header is also added for a non-successful response
    $this->drupalGet('llama');
    $this->assertResponse(404);
    $this->assertEqual('text/html; charset=UTF-8', $this->drupalGetHeader('Content-Type'));
    $this->assertEqual($expectedGeneratorHeader, $this->drupalGetHeader('X-Generator'));

    // Enable rest API for nodes
    $this->enableService('entity:node', 'GET', 'json');

    // Tests to see if this also works for a non-html request
    $this->httpRequest($node->urlInfo()->setOption('query', ['_format' => 'json']), 'GET');
    $this->assertResponse(200);
    $this->assertEqual('application/json', $this->drupalGetHeader('Content-Type'));
    $this->assertEqual($expectedGeneratorHeader, $this->drupalGetHeader('X-Generator'));

  }

}
