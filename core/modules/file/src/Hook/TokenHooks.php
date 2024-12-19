<?php

declare(strict_types=1);

namespace Drupal\file\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\Utility\Token;

/**
 * Hook implementations for file tokens.
 */
class TokenHooks {

  public function __construct(
    private readonly Token $token,
    private readonly LanguageManagerInterface $languageManager,
    private readonly DateFormatterInterface $dateFormatter,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $url_options = ['absolute' => TRUE];
    if (isset($options['langcode'])) {
      $url_options['language'] = $this->languageManager->getLanguage($options['langcode']);
      $langcode = $options['langcode'];
    }
    else {
      $langcode = NULL;
    }
    $replacements = [];
    if ($type == 'file' && !empty($data['file'])) {
      $dateFormatStorage = $this->entityTypeManager->getStorage('date_format');
      /** @var \Drupal\file\FileInterface $file */
      $file = $data['file'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          // Basic keys and values.
          case 'fid':
            $replacements[$original] = $file->id();
            break;

          case 'uuid':
            $replacements[$original] = $file->uuid();
            break;

          // Essential file data
          case 'name':
            $replacements[$original] = $file->getFilename();
            break;

          case 'path':
            $replacements[$original] = $file->getFileUri();
            break;

          case 'mime':
            $replacements[$original] = $file->getMimeType();
            break;

          case 'size':
            $replacements[$original] = ByteSizeMarkup::create($file->getSize());
            break;

          case 'url':
            // Ideally, this would use return a relative URL, but because tokens
            // are also often used in emails, it's better to keep absolute file
            // URLs. The 'url.site' cache context is associated to ensure the
            // correct absolute URL is used in case of a multisite setup.
            $replacements[$original] = $file->createFileUrl(FALSE);
            $bubbleable_metadata->addCacheContexts(['url.site']);
            break;

          // These tokens are default variations on the chained tokens handled below.
          case 'created':
            $date_format = $dateFormatStorage->load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = $this->dateFormatter->format($file->getCreatedTime(), 'medium', '', NULL, $langcode);
            break;

          case 'changed':
            $date_format = $dateFormatStorage->load('medium');
            $bubbleable_metadata = $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = $this->dateFormatter->format($file->getChangedTime(), 'medium', '', NULL, $langcode);
            break;

          case 'owner':
            $owner = $file->getOwner();
            $bubbleable_metadata->addCacheableDependency($owner);
            $name = $owner->label();
            $replacements[$original] = $name;
            break;
        }
      }
      if ($date_tokens = $this->token->findWithPrefix($tokens, 'created')) {
        $replacements += $this->token->generate('date', $date_tokens, ['date' => $file->getCreatedTime()], $options, $bubbleable_metadata);
      }
      if ($date_tokens = $this->token->findWithPrefix($tokens, 'changed')) {
        $replacements += $this->token->generate('date', $date_tokens, ['date' => $file->getChangedTime()], $options, $bubbleable_metadata);
      }
      if (($owner_tokens = $this->token->findWithPrefix($tokens, 'owner')) && $file->getOwner()) {
        $replacements += $this->token->generate('user', $owner_tokens, ['user' => $file->getOwner()], $options, $bubbleable_metadata);
      }
    }
    return $replacements;
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $types['file'] = [
      'name' => t("Files"),
      'description' => t("Tokens related to uploaded files."),
      'needs-data' => 'file',
    ];
    // File related tokens.
    $file['fid'] = [
      'name' => t("File ID"),
      'description' => t("The unique ID of the uploaded file."),
    ];
    $file['uuid'] = ['name' => t('UUID'), 'description' => t('The UUID of the uploaded file.')];
    $file['name'] = ['name' => t("File name"), 'description' => t("The name of the file on disk.")];
    $file['path'] = [
      'name' => t("Path"),
      'description' => t("The location of the file relative to Drupal root."),
    ];
    $file['mime'] = ['name' => t("MIME type"), 'description' => t("The MIME type of the file.")];
    $file['size'] = ['name' => t("File size"), 'description' => t("The size of the file.")];
    $file['url'] = ['name' => t("URL"), 'description' => t("The web-accessible URL for the file.")];
    $file['created'] = [
      'name' => t("Created"),
      'description' => t("The date the file created."),
      'type' => 'date',
    ];
    $file['changed'] = [
      'name' => t("Changed"),
      'description' => t("The date the file was most recently changed."),
      'type' => 'date',
    ];
    $file['owner'] = [
      'name' => t("Owner"),
      'description' => t("The user who originally uploaded the file."),
      'type' => 'user',
    ];
    return ['types' => $types, 'tokens' => ['file' => $file]];
  }

}
