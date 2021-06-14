<?php

namespace Drupal\Tests\taxonomy\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class VocabularyJsonAnonTest extends VocabularyResourceTestBase {

  use AnonResourceTestTrait;

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
  protected $defaultTheme = 'stark';

  /**
   * Disable the GET test coverage due to bug in taxonomy module.
   * @todo Fix in https://www.drupal.org/node/2805281: remove this override.
   */
  public function testGet() {
    $this->markTestSkipped();
  }

}
