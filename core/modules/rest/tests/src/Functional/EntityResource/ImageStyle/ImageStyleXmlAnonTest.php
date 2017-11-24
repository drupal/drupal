<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ImageStyle;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 */
class ImageStyleXmlAnonTest extends ImageStyleResourceTestBase {

  use AnonResourceTestTrait;
  use XmlEntityNormalizationQuirksTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'xml';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'text/xml; charset=UTF-8';

  /**
   * {@inheritdoc}
   */
  public function testGet() {
    // @todo Remove this method override in https://www.drupal.org/node/2905655
    $this->markTestSkipped();
  }

}
