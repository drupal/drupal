<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityUriTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests the basic Entity API.
 */
class EntityUriTest extends EntityUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Entity URI',
      'description' => 'Tests default URI functionality.',
      'group' => 'Entity API',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('system', array('variable', 'url_alias'));
  }

  /**
   * Tests that an entity without a URI callback uses the default URI.
   */
  function testDefaultUri() {
    // Create a test entity.
    $entity = entity_create('entity_test', array('name' => 'test', 'user_id' => 1));
    $entity->save();
    $uri = $entity->uri();
    $expected_path = 'entity/entity_test/' . $entity->id();
    $this->assertEqual(url($uri['path'], $uri['options']), url($expected_path), 'Entity without URI callback returns expected URI.');
  }

}
