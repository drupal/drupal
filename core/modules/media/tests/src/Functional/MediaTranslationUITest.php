<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the Media Translation UI.
 *
 * @group media
 */
class MediaTranslationUITest extends ContentTranslationUITestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'session',
    'theme',
    'url.path',
    'url.query_args',
    'user.permissions',
    'user.roles:authenticated',
  ];

  /**
   * {inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'media',
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeId = 'media';
    $this->bundle = 'test';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function setupBundle() {
    $this->createMediaType('test', [
      'id' => $this->bundle,
      'queue_thumbnail_downloads' => FALSE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), [
      'administer media',
      'edit any test media',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return ['administer media', 'create test media'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), [
      'access administration pages',
      'administer media types',
      'access media overview',
      'administer languages',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return [
      'name' => [['value' => $this->randomMachineName()]],
      'field_media_test' => [['value' => $this->randomMachineName()]],
    ] + parent::getNewEntityValues($langcode);
  }

}
