<?php

namespace Drupal\user\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Plugin to replace !tokens with [tokens].
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess(
  id: "convert_tokens",
  handle_multiples: TRUE,
)]
class ConvertTokens extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $tokens = [
      '!site' => '[site:name]',
      '!username' => '[user:name]',
      '!mailto' => '[user:mail]',
      '!login_uri' => '[site:login-url]',
      '!uri_brief' => '[site:url-brief]',
      '!edit_uri' => '[user:edit-url]',
      '!login_url' => '[user:one-time-login-url]',
      '!uri' => '[site:url]',
      '!date' => '[date:medium]',
      '!password' => '',
    ];

    // Given that our source is a database column that could hold a NULL
    // value, sometimes that filters down to here. str_replace() cannot
    // handle NULLs as the subject, so we reset to an empty string.
    if (is_null($value)) {
      $value = '';
    }

    if (is_string($value)) {
      return str_replace(array_keys($tokens), $tokens, $value);
    }
    else {
      throw new MigrateException('Value must be a string.');
    }
  }

}
