<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 */
class NodeXmlAnonTest extends NodeResourceTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function testPatchPath(): void {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

}
