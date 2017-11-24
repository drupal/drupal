<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Comment;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
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
   *
   * Anononymous users cannot edit their own comments.
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
