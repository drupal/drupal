<?php

declare(strict_types=1);

namespace Drupal\locale\Model;

use Drupal\Core\Utility\OptionsEnumTrait;

/**
 * Where to check for updates to translations.
 */
enum TranslationUpdateMode: string {

  use OptionsEnumTrait;

  // Translation update mode: Use local files only.
  //
  // When checking for available translation updates, only local files will be
  // used. Any remote translation file will be ignored. Also, custom modules and
  // themes which have set a "server pattern" to use a remote translation server
  // will be ignored.
  case Local = 'local';

  // Translation update mode: Use both remote and local files.
  //
  // When checking for available translation updates, both local and remote
  // files will be checked.
  case RemoteAndLocal = 'remote_and_local';

  /**
   * {@inheritdoc}
   */
  public function label(): \Stringable {
    return match ($this) {
      self::RemoteAndLocal => t('Drupal translation server and local files'),
      self::Local => t('Local files only'),
    };
  }

}
