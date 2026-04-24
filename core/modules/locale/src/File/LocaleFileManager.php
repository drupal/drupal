<?php

namespace Drupal\locale\File;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\locale\LocaleProjectStorageInterface;
use Drupal\locale\LocaleSource;
use Drupal\locale\StreamWrapper\TranslationsStream;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Provide Locale File helper methods.
 */
class LocaleFileManager {
  use StringTranslationTrait;

  public function __construct(
    protected readonly LocaleProjectStorageInterface $localeProjectStorage,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly ClientFactory $clientFactory,
    protected readonly ClientInterface $httpClient,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Get interface translation files present in the translations directory.
   *
   * @param array $projects
   *   (optional) Project names from which to get the translation files and
   *   history. Defaults to all projects.
   * @param array $langcodes
   *   (optional) Language codes from which to get the translation files and
   *   history. Defaults to all languages.
   *
   * @return array
   *   An array of interface translation files keyed by their URI.
   */
  public function getInterfaceTranslationFiles(array $projects = [], array $langcodes = []): array {
    \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
    $files = [];
    $projects = $projects ?: array_keys($this->localeProjectStorage->getProjects());
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    // Scan the translations directory for files matching a name pattern
    // containing a project name and language code: {project}.{langcode}.po or
    // {project}-{version}.{langcode}.po.
    // Only files of known projects and languages will be returned.
    $directory = TranslationsStream::basePath();
    $result = [];
    if (is_dir($directory)) {
      $result = $this->fileSystem->scanDirectory($directory, '![a-z_]+(\-[0-9a-z\.\-\+]+|)\.[^\./]+\.po$!', ['recurse' => FALSE]);
    }

    foreach ($result as $file) {
      // Recreate file object, detect the project name and version from the file
      // name.
      $file = LocaleFile::createFromPath($file->filename, $file->uri);
      if (in_array($file->project, $projects) && in_array($file->langcode, $langcodes)) {
        $files[$file->uri] = $file;
      }
    }

    return $files;
  }

  /**
   * Deletes interface translation files and translation history records.
   *
   * @param array $projects
   *   (optional) Project names from which to delete the translation files and
   *   history. Defaults to all projects.
   * @param array $langcodes
   *   (optional) Language codes from which to delete the translation files and
   *   history. Defaults to all languages.
   *
   * @return bool
   *   TRUE if files are removed successfully. FALSE if one or more files could
   *   not be deleted.
   */
  public function deleteTranslationFiles(array $projects = [], array $langcodes = []): bool {
    $fail = FALSE;
    locale_translation_file_history_delete($projects, $langcodes);

    // Delete all translation files from the translations directory.
    if ($files = $this->getInterfaceTranslationFiles($projects, $langcodes)) {
      foreach ($files as $file) {
        try {
          $this->fileSystem->delete($file->uri);
        }
        catch (FileException) {
          $fail = TRUE;
        }
      }
    }
    return !$fail;
  }

  /**
   * Check if remote file exists and when it was last updated.
   *
   * @param string $uri
   *   URI of remote file.
   *
   * @return \Drupal\locale\File\RemoteFileInfo
   *   RemoteFileInfo value object.
   */
  public function checkRemoteFileStatus(string $uri): RemoteFileInfo {
    $logger = $this->loggerFactory->get('locale');
    $remoteFileInfo = new RemoteFileInfo();
    try {
      $actual_uri = NULL;
      $response = $this->clientFactory->fromOptions([
        'allow_redirects' => [
          'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $request_uri) use (&$actual_uri) {
            $actual_uri = (string) $request_uri;
          },
        ],
      ])->head($uri);

      // Return the effective URL if it differs from the requested.
      if ($actual_uri && $actual_uri !== $uri) {
        $remoteFileInfo->location = $actual_uri;
      }

      $remoteFileInfo->lastModified = $response->hasHeader('Last-Modified') ? strtotime($response->getHeaderLine('Last-Modified')) : 0;
      $remoteFileInfo->status = RemoteFileStatus::Success;
      return $remoteFileInfo;
    }
    catch (RequestException $e) {
      // Handle 4xx and 5xx http responses.
      $response = $e->getResponse();
      if ($response) {
        if ($response->getStatusCode() == 404) {
          // File not found occurs when a translation file is not yet available
          // at the translation server. But also if a custom module or custom
          // theme does not define the location of a translation file. By
          // default the file is checked at the translation server, but it will
          // not be found there.
          $logger->notice('Translation file not found: @uri.', ['@uri' => $uri]);
          $remoteFileInfo->status = RemoteFileStatus::Missing;
          return $remoteFileInfo;
        }
        $logger->notice(
          'HTTP request to @url failed with error: @error.',
          [
            '@url' => $uri,
            '@error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
          ]
        );
      }
    }
    // We need to handle ConnectException separately because in Guzzle 7 it
    // doesn't have a getResponse() method, so the above will fatal.
    catch (ConnectException $e) {
      $logger->notice('HTTP request to @url failed with error: @error.', ['@url' => $uri, '@error' => $e->getMessage()]);
    }

    $remoteFileInfo->status = RemoteFileStatus::Error;
    return $remoteFileInfo;
  }

  /**
   * Downloads a translation file from a remote server.
   *
   * @param LocaleFile $source_file
   *   Source file object with at least:
   *   - "uri": uri to download the file from.
   *   - "project": Project name.
   *   - "langcode": Translation language.
   *   - "version": Project version.
   *   - "filename": File name.
   * @param string $directory
   *   Directory where the downloaded file will be saved. Defaults to the
   *   temporary file path.
   *
   * @return LocaleFile|false
   *   File object if download was successful. FALSE on failure.
   */
  public function downloadTranslationSource(LocaleFile $source_file, string $directory = 'translations://'): LocaleFile|false {
    try {
      $data = (string) $this->httpClient->request('get', $source_file->uri)->getBody();
      $filename = basename($source_file->uri);
      if ($uri = $this->fileSystem->saveData($data, $directory . $filename, FileExists::Replace)) {
        $hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $uri);
        $langcode = $source_file->langcode ?? LanguageInterface::LANGCODE_NOT_SPECIFIED;
        $project = $source_file->project ?? NULL;
        $version = $source_file->version ?? NULL;
        $file = new LocaleFile($filename, $uri, $hash, filemtime($uri), $langcode, $project, $version);
        $file->type = LOCALE_TRANSLATION_LOCAL;
        $file->directory = $directory;
        return $file;
      }
    }
    catch (ClientExceptionInterface $exception) {
      $this->messenger->addError($this->t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
    }
    catch (FileException | InvalidStreamWrapperException $e) {
      $this->messenger->addError($this->t('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
    }
    $this->loggerFactory->get('locale')->error('Unable to download translation file @uri.', ['@uri' => $source_file->uri]);
    return FALSE;
  }

}
