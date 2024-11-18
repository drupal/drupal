<?php

declare(strict_types=1);

namespace Drupal\file_test\Hook;

use Drupal\file\Entity\File;
use Drupal\Core\Hook\Attribute\Hook;

// cspell:ignore tarz
// cspell:ignore garply

/**
 * Hook implementations for file_test.
 */
class FileTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_load() for file entities.
   */
  #[Hook('file_load')]
  public function fileLoad($files) {
    foreach ($files as $file) {
      _file_test_log_call('load', [$file->id()]);
      // Assign a value on the object so that we can test that the $file is passed
      // by reference.
      $file->file_test['loaded'] = TRUE;
    }
  }

  /**
   * Implements hook_file_download().
   */
  #[Hook('file_download')]
  public function fileDownload($uri) {
    if (\Drupal::state()->get('file_test.allow_all', FALSE)) {
      $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
      $file = reset($files);
      return file_get_content_headers($file);
    }
    _file_test_log_call('download', [$uri]);
    return _file_test_get_return('download');
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for file entities.
   */
  #[Hook('file_insert')]
  public function fileInsert(File $file) {
    _file_test_log_call('insert', [$file->id()]);
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for file entities.
   */
  #[Hook('file_update')]
  public function fileUpdate(File $file) {
    _file_test_log_call('update', [$file->id()]);
  }

  /**
   * Implements hook_file_copy().
   */
  #[Hook('file_copy')]
  public function fileCopy(File $file, $source) {
    _file_test_log_call('copy', [$file->id(), $source->id()]);
  }

  /**
   * Implements hook_file_move().
   */
  #[Hook('file_move')]
  public function fileMove(File $file, File $source) {
    _file_test_log_call('move', [$file->id(), $source->id()]);
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete(File $file) {
    _file_test_log_call('delete', [$file->id()]);
  }

  /**
   * Implements hook_file_url_alter().
   */
  #[Hook('file_url_alter')]
  public function fileUrlAlter(&$uri): void {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    // Only run this hook when this variable is set. Otherwise, we'd have to add
    // another hidden test module just for this hook.
    $alter_mode = \Drupal::state()->get('file_test.hook_file_url_alter');
    if (!$alter_mode) {
      return;
    }
    elseif ($alter_mode == 'cdn') {
      $cdn_extensions = ['css', 'js', 'gif', 'jpg', 'jpeg', 'png'];
      // Most CDNs don't support private file transfers without a lot of hassle,
      // so don't support this in the common case.
      $schemes = ['public'];
      $scheme = $stream_wrapper_manager::getScheme($uri);
      // Only serve shipped files and public created files from the CDN.
      if (!$scheme || in_array($scheme, $schemes)) {
        // Shipped files.
        if (!$scheme) {
          $path = $uri;
        }
        else {
          $wrapper = $stream_wrapper_manager->getViaScheme($scheme);
          $path = $wrapper->getDirectoryPath() . '/' . $stream_wrapper_manager::getTarget($uri);
        }
        // Clean up Windows paths.
        $path = str_replace('\\', '/', $path);
        // Serve files with one of the CDN extensions from CDN 1, all others from
        // CDN 2.
        $pathinfo = pathinfo($path);
        if (array_key_exists('extension', $pathinfo) && in_array($pathinfo['extension'], $cdn_extensions)) {
          $uri = FILE_URL_TEST_CDN_1 . '/' . $path;
        }
        else {
          $uri = FILE_URL_TEST_CDN_2 . '/' . $path;
        }
      }
    }
    elseif ($alter_mode == 'root-relative') {
      // Only serve shipped files and public created files with root-relative
      // URLs.
      $scheme = $stream_wrapper_manager::getScheme($uri);
      if (!$scheme || $scheme == 'public') {
        // Shipped files.
        if (!$scheme) {
          $path = $uri;
        }
        else {
          $wrapper = $stream_wrapper_manager->getViaScheme($scheme);
          $path = $wrapper->getDirectoryPath() . '/' . $stream_wrapper_manager::getTarget($uri);
        }
        // Clean up Windows paths.
        $path = str_replace('\\', '/', $path);
        // Generate a root-relative URL.
        $uri = base_path() . '/' . $path;
      }
    }
    elseif ($alter_mode == 'protocol-relative') {
      // Only serve shipped files and public created files with protocol-relative
      // URLs.
      $scheme = $stream_wrapper_manager::getScheme($uri);
      if (!$scheme || $scheme == 'public') {
        // Shipped files.
        if (!$scheme) {
          $path = $uri;
        }
        else {
          $wrapper = $stream_wrapper_manager->getViaScheme($scheme);
          $path = $wrapper->getDirectoryPath() . '/' . $stream_wrapper_manager::getTarget($uri);
        }
        // Clean up Windows paths.
        $path = str_replace('\\', '/', $path);
        // Generate a protocol-relative URL.
        $uri = '/' . base_path() . '/' . $path;
      }
    }
  }

  /**
   * Implements hook_file_mimetype_mapping_alter().
   */
  #[Hook('file_mimetype_mapping_alter')]
  public function fileMimetypeMappingAlter(&$mapping): void {
    // Add new mappings.
    $mapping['mimetypes']['file_test_mimetype_1'] = 'made_up/file_test_1';
    $mapping['mimetypes']['file_test_mimetype_2'] = 'made_up/file_test_2';
    $mapping['mimetypes']['file_test_mimetype_3'] = 'made_up/doc';
    $mapping['mimetypes']['application-x-compress'] = 'application/x-compress';
    $mapping['mimetypes']['application-x-tarz'] = 'application/x-tarz';
    $mapping['mimetypes']['application-x-garply-waldo'] = 'application/x-garply-waldo';
    $mapping['extensions']['file_test_1'] = 'file_test_mimetype_1';
    $mapping['extensions']['file_test_2'] = 'file_test_mimetype_2';
    $mapping['extensions']['file_test_3'] = 'file_test_mimetype_2';
    $mapping['extensions']['z'] = 'application-x-compress';
    $mapping['extensions']['tar.z'] = 'application-x-tarz';
    $mapping['extensions']['garply.waldo'] = 'application-x-garply-waldo';
    // Override existing mapping.
    $mapping['extensions']['doc'] = 'file_test_mimetype_3';
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(&$entity_types) : void {
    if (\Drupal::state()->get('file_test_alternate_access_handler', FALSE)) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
      $entity_types['file']->setAccessClass('Drupal\file_test\FileTestAccessControlHandler');
    }
  }

}
