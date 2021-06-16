<?php

namespace Drupal\Tests\comment\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 */
class CommentXmlCookieTest extends CommentResourceTestBase {

  use CookieResourceTestTrait;
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
  protected static $auth = 'cookie';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function testPostDxWithoutCriticalBaseFields() {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testPostSkipCommentApproval() {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

}
