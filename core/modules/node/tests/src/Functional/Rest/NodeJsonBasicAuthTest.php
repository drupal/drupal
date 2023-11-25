<?php

namespace Drupal\Tests\node\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group rest
 */
class NodeJsonBasicAuthTest extends NodeResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    parent::setUpAuthorization($method);
    $this->grantPermissionsToTestedRole(['view camelids revisions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $entity = parent::getExpectedNormalizedEntity();
    $entity['revision_log'] = [];
    return $entity;
  }

}
