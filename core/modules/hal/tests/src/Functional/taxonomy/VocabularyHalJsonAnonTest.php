<?php

namespace Drupal\Tests\hal\Functional\taxonomy;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\taxonomy\Functional\Rest\VocabularyResourceTestBase;

/**
 * @group hal
 */
class VocabularyHalJsonAnonTest extends VocabularyResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * @todo Remove this override in https://www.drupal.org/node/2805281.
   */
  public function testGet() {
    $this->markTestSkipped();
  }

}
