<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a Drupal stream wrapper class for optimized assets (assets://).
 *
 * Provides support for storing publicly accessible optimized assets files
 * with the Drupal file interface.
 */
class AssetsStream extends PublicStream {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getType(): int {
    return StreamWrapperInterface::LOCAL_HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->t('Optimized assets files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Public local optimized assets files served by the webserver.');
  }

  /**
   * {@inheritdoc}
   */
  public static function basePath($site_path = NULL): string {
    return Settings::get(
      'file_assets_path',
      parent::basePath($site_path)
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseUrl(): string {
    $public_path = Settings::get('file_public_path', 'sites/default/files');
    $path = Settings::get('file_assets_path', $public_path);
    if ($path === $public_path) {
      $base_url = PublicStream::baseUrl();
    }
    else {
      $base_url = $GLOBALS['base_url'] . '/' . $path;
    }

    return $base_url;
  }

}
