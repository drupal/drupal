<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Vocabulary;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Vocabulary\VocabularyResourceTestBase;

/**
 * @group hal
 */
class VocabularyHalJsonAnonTest extends VocabularyResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $expectedErrorMimeType = 'application/json';

  /**
   * @todo Remove this override in https://www.drupal.org/node/2805281.
   */
  public function testGet() {
    $this->markTestSkipped();
  }

}
