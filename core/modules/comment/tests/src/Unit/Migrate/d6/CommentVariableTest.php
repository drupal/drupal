<?php

namespace Drupal\Tests\comment\Unit\Migrate\d6;

use Drupal\comment\Plugin\migrate\source\d6\CommentVariable;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * @coversDefaultClass \Drupal\comment\Plugin\migrate\source\d6\CommentVariable
 * @group comment
 */
class CommentVariableTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = CommentVariable::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_comment_variable',
    ),
  );

  protected $expectedResults = array(
    array(
      'comment' => '1',
      'comment_default_mode' => '1',
      'comment_default_order' => '1',
      'comment_default_per_page' => '50',
      'comment_controls' => '1',
      'comment_anonymous' => '1',
      'comment_subject_field' => '1',
      'comment_preview' => '1',
      'comment_form_location' => '1',
      'node_type' => 'page',
      'comment_type' => 'comment',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = array(
      array(
        'type' => 'page',
      ),
    );
    $this->databaseContents['variable'] = array(
      array(
        'name' => 'comment_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_default_mode_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_default_order_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_default_per_page_page',
        'value' => serialize(50),
      ),
      array(
        'name' => 'comment_controls_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_anonymous_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_subject_field_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_preview_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_form_location_page',
        'value' => serialize(1),
      ),
    );
    parent::setUp();
  }

}
