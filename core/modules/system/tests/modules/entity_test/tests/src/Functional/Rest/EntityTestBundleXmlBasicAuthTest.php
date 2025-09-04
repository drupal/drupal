<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests EntityTest Bundle Xml Basic Auth.
 */
#[Group('rest')]
class EntityTestBundleXmlBasicAuthTest extends EntityTestBundleResourceTestBase {

  use BasicAuthResourceTestTrait;
  use XmlEntityNormalizationQuirksTrait;

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
