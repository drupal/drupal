<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditorFileReferenceFilterTest.
 */

namespace Drupal\editor\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\simpletest\KernelTestBase;
use Drupal\filter\FilterPluginCollection;

/**
 * Tests Editor module's file reference filter.
 *
 * @group editor
 */
class EditorFileReferenceFilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'filter', 'editor', 'field', 'file', 'user');

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
    $this->installEntitySchema('file');
    $this->installSchema('file', array('file_usage'));

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, array());
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the editor file reference filter.
   */
  function testEditorFileReferenceFilter() {
    $filter = $this->filters['editor_file_reference'];

    $test = function($input) use ($filter) {
      return $filter->process($input, 'und');
    };

    file_put_contents('public://llama.jpg', $this->randomMachineName());
    $image = entity_create('file', array('uri' => 'public://llama.jpg'));
    $image->save();
    $id = $image->id();
    $uuid = $image->uuid();
    $cache_tag = ['file:' . $id];

    file_put_contents('public://alpaca.jpg', $this->randomMachineName());
    $image_2 = entity_create('file', array('uri' => 'public://alpaca.jpg'));
    $image_2->save();
    $id_2 = $image_2->id();
    $uuid_2 = $image_2->uuid();
    $cache_tag_2 = ['file:' . $id_2];

    $this->pass('No data-entity-type and no data-entity-uuid attribute.');
    $input = '<img src="llama.jpg" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());

    $this->pass('A non-file data-entity-type attribute value.');
    $input = '<img src="llama.jpg" data-entity-type="invalid-entity-type-value" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());

    $this->pass('One data-entity-uuid attribute.');
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    $this->pass('One data-entity-uuid attribute with odd capitalization.');
    $input = '<img src="llama.jpg" data-entity-type="file" DATA-entity-UUID =   "' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    $this->pass('One data-entity-uuid attribute on a non-image tag.');
    $input = '<video src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    $this->pass('One data-entity-uuid attribute with an invalid value.');
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="invalid-' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array(), $output->getCacheTags());

    $this->pass('Two different data-entity-uuid attributes.');
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $input .= '<img src="alpaca.jpg" data-entity-type="file" data-entity-uuid="' . $uuid_2 . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(Cache::mergeTags($cache_tag, $cache_tag_2), $output->getCacheTags());

    $this->pass('Two identical  data-entity-uuid attributes.');
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $input .= '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());
  }

}
