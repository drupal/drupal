<?php

namespace Drupal\Tests\workspaces\Functional\EntityResource;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test workspace entities for unauthenticated JSON requests.
 *
 * @group workspaces
 */
class WorkspaceJsonAnonTest extends WorkspaceResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function getModifiedEntityForPostTesting(): array {
    $values = parent::getModifiedEntityForPostTesting();
    // The ID field for a workspace uses a string field, with a validation
    // constraint that applies a regex pattern that prevents whitespace.
    // \Drupal\Core\Field\Plugin\Field\FieldType\StringItem::generateSampleValue
    // used in the parent implementation of ::getModifiedEntityForPostTesting
    // can generate whitespace, so we use the same regex pattern here to ensure
    // the generated value is valid for the sake of the test.
    //
    // @see \Drupal\Core\Field\Plugin\Field\FieldType\StringItem::generateSampleValue
    // @see \Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase::getModifiedEntityForPostTesting
    // @see \Drupal\workspaces\Entity\Workspace::baseFieldDefinitions
    $values['id'] = preg_replace('/[^a-z0-9_]/', '', $values['id']);
    return $values;
  }

}
