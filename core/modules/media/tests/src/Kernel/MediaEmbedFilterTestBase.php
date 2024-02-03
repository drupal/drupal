<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\file\Entity\File;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\FilterProcessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Base class for Media Embed filter tests.
 */
abstract class MediaEmbedFilterTestBase extends KernelTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
  }

  /**
   * The UUID to use for the embedded entity.
   *
   * @var string
   */
  const EMBEDDED_ENTITY_UUID = 'e7a3e1fe-b69b-417e-8ee4-c80cb7640e63';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'media',
    'system',
    'text',
    'user',
  ];

  /**
   * The image file to use in tests.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $image;

  /**
   * The sample Media entity to embed.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $embeddedEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installConfig('filter');
    $this->installConfig('image');
    $this->installConfig('media');
    $this->installConfig('system');

    // Create a user with required permissions. Ensure that we don't use user 1
    // because that user is treated in special ways by access control handlers.
    $this->drupalCreateUser([]);
    $user = $this->drupalCreateUser([
      'access content',
      'view media',
    ]);
    $this->container->get('current_user')->setAccount($user);

    $this->image = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
      'uid' => 2,
    ]);
    $this->image->setPermanent();
    $this->image->save();

    // Create a sample media entity to be embedded.
    $media_type = $this->createMediaType('image', ['id' => 'image']);
    EntityViewMode::create([
      'id' => 'media.foobar',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => $this->randomMachineName(),
    ])->save();
    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => $media_type->id(),
      'mode' => 'foobar',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
      ->removeComponent('created')
      ->removeComponent('uid')
      ->setComponent('field_media_image', [
        'label' => 'visually_hidden',
        'type' => 'image',
        'settings' => [
          'image_style' => 'medium',
          'image_link' => 'file',
        ],
        'third_party_settings' => [],
        'weight' => 1,
        'region' => 'content',
      ])
      ->save();
    $media = Media::create([
      'uuid' => static::EMBEDDED_ENTITY_UUID,
      'bundle' => 'image',
      'name' => 'Screaming hairy armadillo',
      'field_media_image' => [
        [
          'target_id' => $this->image->id(),
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ])->setOwner($user);
    $media->save();
    $this->embeddedEntity = $media;
  }

  /**
   * Gets an embed code with given attributes.
   *
   * @param array $attributes
   *   The attributes to add.
   *
   * @return string
   *   A string containing a drupal-media DOM element.
   *
   * @see assertEntityEmbedFilterHasRun()
   */
  protected function createEmbedCode(array $attributes) {
    $dom = Html::load('<drupal-media>This placeholder should not be rendered.</drupal-media>');
    $xpath = new \DOMXPath($dom);
    $drupal_entity = $xpath->query('//drupal-media')[0];
    foreach ($attributes as $attribute => $value) {
      $drupal_entity->setAttribute($attribute, $value);
    }
    return Html::serialize($dom);
  }

  /**
   * Applies the `@Filter=media_embed` filter to text, pipes to raw content.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\Tests\media\Kernel\MediaEmbedFilterTestBase::createEmbedCode()
   * @see \Drupal\KernelTests\AssertContentTrait::setRawContent()
   */
  protected function applyFilter($text, $langcode = 'en') {
    $this->assertStringContainsString('<drupal-media', $text);
    $this->assertStringContainsString('This placeholder should not be rendered.', $text);
    $filter_result = $this->processText($text, $langcode);
    $output = $filter_result->getProcessedText();
    $this->assertStringNotContainsString('<drupal-media', $output);
    $this->assertStringNotContainsString('This placeholder should not be rendered.', $output);
    $this->setRawContent($output);
    return $filter_result;
  }

  /**
   * Assert that the SimpleXMLElement object has the given attributes.
   *
   * @param \SimpleXMLElement $element
   *   The SimpleXMLElement object to check.
   * @param array $expected_attributes
   *   An array of expected attributes.
   */
  protected function assertHasAttributes(\SimpleXMLElement $element, array $expected_attributes) {
    foreach ($expected_attributes as $attribute => $value) {
      if ($value === NULL) {
        $this->assertNull($element[$attribute]);
      }
      else {
        $this->assertSame((string) $value, (string) $element[$attribute]);
      }
    }
  }

  /**
   * Processes text through the provided filters.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param string[] $filter_ids
   *   (optional) The filter plugin IDs to apply to the given text, in the order
   *   they are being requested to be executed.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\filter\Element\ProcessedText::preRenderText()
   */
  protected function processText($text, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, array $filter_ids = ['media_embed']) {
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $filters = [];
    foreach ($filter_ids as $filter_id) {
      $filters[] = $bag->get($filter_id);
    }

    $render_context = new RenderContext();
    /** @var \Drupal\filter\FilterProcessResult $filter_result */
    $filter_result = $this->container->get('renderer')->executeInRenderContext($render_context, function () use ($text, $filters, $langcode) {
      $metadata = new BubbleableMetadata();
      foreach ($filters as $filter) {
        /** @var \Drupal\filter\FilterProcessResult $result */
        $result = $filter->process($text, $langcode);
        $metadata = $metadata->merge($result);
        $text = $result->getProcessedText();
      }
      return (new FilterProcessResult($text))->merge($metadata);
    });
    if (!$render_context->isEmpty()) {
      $filter_result = $filter_result->merge($render_context->pop());
    }
    return $filter_result;
  }

}
