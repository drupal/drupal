<?php

namespace Drupal\Tests\editor\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests Editor module's file reference filter.
 *
 * @group editor
 */
class EditorFileReferenceFilterTest extends KernelTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'filter',
    'editor',
    'field',
    'file',
    'user',
  ];

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the editor file reference filter.
   */
  public function testEditorFileReferenceFilter() {
    $filter = $this->filters['editor_file_reference'];

    $test = function ($input) use ($filter) {
      return $filter->process($input, 'und');
    };

    file_put_contents('public://llama.jpg', $this->randomMachineName());
    $image = File::create(['uri' => 'public://llama.jpg']);
    $image->save();
    $id = $image->id();
    $uuid = $image->uuid();
    $cache_tag = ['file:' . $id];

    file_put_contents('public://alpaca.jpg', $this->randomMachineName());
    $image_2 = File::create(['uri' => 'public://alpaca.jpg']);
    $image_2->save();
    $id_2 = $image_2->id();
    $uuid_2 = $image_2->uuid();
    $cache_tag_2 = ['file:' . $id_2];

    // No data-entity-type and no data-entity-uuid attribute.
    $input = '<img src="llama.jpg" />';
    $output = $test($input);
    $this->assertSame($input, $output->getProcessedText());

    // A non-file data-entity-type attribute value.
    $input = '<img src="llama.jpg" data-entity-type="invalid-entity-type-value" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertSame($input, $output->getProcessedText());

    // One data-entity-uuid attribute.
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    // One data-entity-uuid attribute with odd capitalization.
    $input = '<img src="llama.jpg" data-entity-type="file" DATA-entity-UUID =   "' . $uuid . '" />';
    $expected_output = '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    // One data-entity-uuid attribute on a non-image tag.
    $input = '<video src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<video src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '"></video>';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    // One data-entity-uuid attribute with an invalid value.
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="invalid-' . $uuid . '" />';
    $output = $test($input);
    $this->assertSame($input, $output->getProcessedText());
    $this->assertEqual([], $output->getCacheTags());

    // Two different data-entity-uuid attributes.
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $input .= '<img src="alpaca.jpg" data-entity-type="file" data-entity-uuid="' . $uuid_2 . '" />';
    $expected_output = '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output .= '<img src="/' . $this->siteDirectory . '/files/alpaca.jpg" data-entity-type="file" data-entity-uuid="' . $uuid_2 . '" />';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEqual(Cache::mergeTags($cache_tag, $cache_tag_2), $output->getCacheTags());

    // Two identical  data-entity-uuid attributes.
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $input .= '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output .= '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEqual($cache_tag, $output->getCacheTags());

    // Add a valid image for test lazy loading feature.
    /** @var array stdClass */
    $files = $this->getTestFiles('image');
    $image = reset($files);
    \Drupal::service('file_system')->copy($image->uri, 'public://llama.jpg', FileSystemInterface::EXISTS_REPLACE);
    [$width, $height] = getimagesize('public://llama.jpg');
    $dimensions = 'width="' . $width . '" height="' . $height . '"';

    // Image dimensions and loading attributes are present.
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" ' . $dimensions . ' loading="lazy" />';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEquals($cache_tag, $output->getCacheTags());

    // Image dimensions and loading attributes are set manually.
    $input = '<img src="llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '"width="41" height="21" loading="eager" />';
    $expected_output = '<img src="/' . $this->siteDirectory . '/files/llama.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" width="41" height="21" loading="eager" />';
    $output = $test($input);
    $this->assertSame($expected_output, $output->getProcessedText());
    $this->assertEquals($cache_tag, $output->getCacheTags());
  }

}
