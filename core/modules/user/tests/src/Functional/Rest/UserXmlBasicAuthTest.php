<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 * @group #slow
 */
class UserXmlBasicAuthTest extends UserResourceTestBase {

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

  /**
   * {@inheritdoc}
   */
  public function testPatchDxForSecuritySensitiveBaseFields(): void {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testPatchSecurityOtherUser(): void {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

}
