<?php

namespace Drupal\Tests\aggregator\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 */
class ItemXmlBasicAuthTest extends ItemResourceTestBase {

  use BasicAuthResourceTestTrait;
  use XmlEntityNormalizationQuirksTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $auth = 'basic_auth';

}
