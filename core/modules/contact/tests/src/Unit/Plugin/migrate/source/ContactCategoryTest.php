<?php

/**
 * @file
 * Contains \Drupal\Tests\contact\Unit\Plugin\migrate\source\ContactCategoryTest.
 */

namespace Drupal\Tests\contact\Unit\Plugin\migrate\source;

use Drupal\contact\Plugin\migrate\source\ContactCategory;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests contact_category source plugin.
 *
 * @group contact
 */
class ContactCategoryTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = ContactCategory::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'contact_category',
    ),
  );

  protected $expectedResults = array(
    array(
      'cid' => 1,
      'category' => 'contact category value 1',
      'recipients' => array('admin@example.com', 'user@example.com'),
      'reply' => 'auto reply value 1',
      'weight' => 0,
      'selected' => 0,
    ),
    array(
      'cid' => 2,
      'category' => 'contact category value 2',
      'recipients' => array('admin@example.com', 'user@example.com'),
      'reply' => 'auto reply value 2',
      'weight' => 0,
      'selected' => 0,
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['contact'][$k] = $row;
      $this->databaseContents['contact'][$k]['recipients'] = implode(',', $row['recipients']);
    }
    parent::setUp();
  }

}
