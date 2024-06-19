<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 * @group #slow
 */
class CommentXmlAnonTest extends CommentResourceTestBase {

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
   *
   * Anonymous users cannot edit their own comments.
   *
   * @see \Drupal\comment\CommentAccessControlHandler::checkAccess
   *
   * Therefore we grant them the 'administer comments' permission for the
   * purpose of this test.
   *
   * @see ::setUpAuthorization
   */
  protected static $patchProtectedFieldNames = [
    'pid',
    'entity_id',
    'changed',
    'thread',
    'entity_type',
    'field_name',
  ];

  /**
   * {@inheritdoc}
   */
  public function testPostDxWithoutCriticalBaseFields(): void {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testPostSkipCommentApproval(): void {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

}
