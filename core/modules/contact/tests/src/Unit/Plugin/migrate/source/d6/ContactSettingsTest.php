<?php

/**
 * @file
 * Contains \Drupal\Tests\contact\Unit\Plugin\migrate\source\d6\ContactSettingsTest.
 */

namespace Drupal\Tests\contact\Unit\Plugin\migrate\source\d6;

use Drupal\contact\Plugin\migrate\source\ContactSettings;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 contact settings source plugin.
 *
 * @group contact
 */
class ContactSettingsTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = ContactSettings::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_contact_settings',
      'variables' => array('site_name'),
    ),
  );

  protected $expectedResults = array(
    array(
      'default_category' => '1',
      'site_name' => 'Blorf!',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['variable'] = array(
      array(
        'name' => 'site_name',
        'value' => serialize('Blorf!'),
      ),
    );
    $this->databaseContents['contact'] = array(
      array(
        'cid' => '1',
        'category' => 'Website feedback',
        'recipients' => 'admin@example.com',
        'reply' => '',
        'weight' => '0',
        'selected' => '1',
      )
    );
    parent::setUp();
  }

}
