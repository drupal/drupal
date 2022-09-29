<?php

namespace Drupal\Core\Installer\Exception;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Exception thrown if Drupal is installed already.
 */
class AlreadyInstalledException extends InstallerException {

  /**
   * Constructs a new "already installed" exception.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;

    $title = $this->t('Drupal already installed');
    $replacements = [
      ':base-url' => $GLOBALS['base_url'],
      // We cannot use the route system.db_update here because we are too early
      // in the execution stack.
      ':update-url' => $GLOBALS['base_path'] . 'update.php',
    ];
    $message = $this->t('<ul>
<li>To start over, you must empty your existing database and copy <em>default.settings.php</em> over <em>settings.php</em>.</li>
<li>To upgrade an existing installation, proceed to the <a href=":update-url">update script</a>.</li>
<li>View your <a href=":base-url">existing site</a>.</li>
</ul>', $replacements);
    parent::__construct($message, $title);
  }

}
