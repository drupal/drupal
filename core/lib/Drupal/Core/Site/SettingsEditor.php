<?php

namespace Drupal\Core\Site;

use Drupal\Component\Utility\OpCodeCache;

/**
 * Generates settings.php files for Drupal installations.
 */
final class SettingsEditor {

  /**
   * This class should not be instantiated.
   *
   * Use a static ::rewrite() method call instead.
   *
   * @see \Drupal\Core\Site\SettingsEditor::rewrite()
   */
  private function __construct() {}

  /**
   * Replaces values in settings.php with values in the submitted array.
   *
   * This method rewrites values in place if possible, even for
   * multidimensional arrays. This helps ensure that the documentation remains
   * attached to the correct settings and that old, overridden values do not
   * clutter up the file.
   *
   * @code
   *   $settings['settings']['config_sync_directory'] = (object) array(
   *     'value' => 'config_hash/sync',
   *     'required' => TRUE,
   *   );
   * @endcode
   *   gets dumped as:
   * @code
   *   $settings['config_sync_directory'] = 'config_hash/sync'
   * @endcode
   *
   * @param string $settings_file
   *   Path to the settings file relative to the DRUPAL_ROOT directory.
   * @param array $settings
   *   An array of settings that need to be updated. Multidimensional arrays
   *   are dumped up to a stdClass object. The object can have value, required
   *   and comment properties.
   *
   * @throws \Exception
   */
  public static function rewrite(string $settings_file, array $settings = []): void {
    // Build list of setting names and insert the values into the global
    // namespace.
    $variable_names = [];
    $settings_settings = [];
    foreach ($settings as $setting => $data) {
      if ($setting !== 'settings') {
        self::setGlobal($GLOBALS[$setting], $data);
      }
      else {
        self::setGlobal($settings_settings, $data);
      }
      $variable_names['$' . $setting] = $setting;
    }
    $contents = file_get_contents($settings_file);
    if ($contents !== FALSE) {
      // Initialize the contents for the settings.php file if it is empty.
      if (trim($contents) === '') {
        $contents = "<?php\n";
      }
      // Step through each token in settings.php and replace any variables that
      // are in the passed-in array.
      $buffer = '';
      $state = 'default';
      foreach (token_get_all($contents) as $token) {
        if (is_array($token)) {
          [$type, $value] = $token;
        }
        else {
          $type = -1;
          $value = $token;
        }
        // Do not operate on whitespace.
        if (!in_array($type, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], TRUE)) {
          switch ($state) {
            case 'default':
              if ($type === T_VARIABLE && isset($variable_names[$value])) {
                // This will be necessary to unset the dumped variable.
                $parent = &$settings;
                // This is the current index in parent.
                $index = $variable_names[$value];
                // This will be necessary for descending into the array.
                $current = &$parent[$index];
                $state = 'candidate_left';
              }
              break;

            case 'candidate_left':
              if ($value === '[') {
                $state = 'array_index';
              }
              if ($value === '=') {
                $state = 'candidate_right';
              }
              break;

            case 'array_index':
              if (self::isArrayIndex($type)) {
                $index = trim($value, '\'"');
                $state = 'right_bracket';
              }
              else {
                // $a[foo()] or $a[$bar] or something like that.
                throw new \Exception('invalid array index');
              }
              break;

            case 'right_bracket':
              if ($value === ']') {
                if (isset($current[$index])) {
                  // If the new settings has this index, descend into it.
                  $parent = &$current;
                  $current = &$parent[$index];
                  $state = 'candidate_left';
                }
                else {
                  // Otherwise, jump back to the default state.
                  $state = 'wait_for_semicolon';
                }
              }
              else {
                // $a[1 + 2].
                throw new \Exception('] expected');
              }
              break;

            case 'candidate_right':
              if (self::isSimple($type, $value)) {
                $value = self::exportSingleSettingToPhp($current);
                // Unsetting $current would not affect $settings at all.
                unset($parent[$index]);
                // Skip the semicolon because self::dumpOne() added one.
                $state = 'semicolon_skip';
              }
              else {
                $state = 'wait_for_semicolon';
              }
              break;

            case 'wait_for_semicolon':
              if ($value === ';') {
                $state = 'default';
              }
              break;

            case 'semicolon_skip':
              if ($value === ';') {
                $value = '';
                $state = 'default';
              }
              else {
                // If the expression was $a = 1 + 2; then we replaced 1 and
                // the + is unexpected.
                throw new \Exception('Unexpected token after replacing value.');
              }
              break;
          }
        }
        $buffer .= $value;
      }
      foreach ($settings as $name => $setting) {
        $buffer .= self::exportSettingsToPhp($setting, '$' . $name);
      }

      // Write the new settings file.
      if (file_put_contents($settings_file, $buffer) === FALSE) {
        throw new \Exception("Failed to modify '$settings_file'. Verify the file permissions.");
      }
      // In case any $settings variables were written, import them into the
      // Settings singleton.
      if (!empty($settings_settings)) {
        $old_settings = Settings::getAll();
        new Settings($settings_settings + $old_settings);
      }
      // The existing settings.php file might have been included already. In
      // case an opcode cache is enabled, the rewritten contents of the file
      // will not be reflected in this process. Ensure to invalidate the file
      // in case an opcode cache is enabled.
      OpCodeCache::invalidate(DRUPAL_ROOT . '/' . $settings_file);
    }
    else {
      throw new \Exception("Failed to open '$settings_file'. Verify the file permissions.");
    }
  }

  /**
   * Checks whether the given token represents a scalar or NULL.
   *
   * @param int $type
   *   The token type.
   * @param string $value
   *   The value of the token.
   *
   * @return bool
   *   TRUE if this token represents a scalar or NULL.
   *
   * @see token_name()
   */
  private static function isSimple(int $type, string $value): bool {
    $is_integer = $type === T_LNUMBER;
    $is_float = $type === T_DNUMBER;
    $is_string = $type === T_CONSTANT_ENCAPSED_STRING;
    $is_boolean_or_null = $type === T_STRING && in_array(
      strtoupper($value),
      ['TRUE', 'FALSE', 'NULL']
    );
    return $is_integer || $is_float || $is_string || $is_boolean_or_null;
  }

  /**
   * Checks whether the token is a valid array index (a number or string).
   *
   * @param int $type
   *   The token type.
   *
   * @return bool
   *   TRUE if this token represents a number or a string.
   *
   * @see token_name()
   */
  private static function isArrayIndex(int $type): bool {
    $is_integer = $type === T_LNUMBER;
    $is_float = $type === T_DNUMBER;
    $is_string = $type === T_CONSTANT_ENCAPSED_STRING;
    return $is_integer || $is_float || $is_string;
  }

  /**
   * Makes the given setting global.
   *
   * @param mixed $ref
   *   A reference to a nested index in $GLOBALS.
   * @param array|object $variable
   *   The nested value of the setting being copied.
   */
  private static function setGlobal(mixed &$ref, array|object $variable): void {
    if (is_object($variable)) {
      $ref = $variable->value;
    }
    else {
      foreach ($variable as $k => $v) {
        self::setGlobal($ref[$k], $v);
      }
    }
  }

  /**
   * Recursively exports one or more settings to a valid PHP string.
   *
   * @param array|object $variable
   *   The container for variable values.
   * @param string $variable_name
   *   Name of variable.
   *
   * @return string
   *   A string containing valid PHP code of the variable suitable for placing
   *   into settings.php.
   */
  private static function exportSettingsToPhp(array|object $variable, string $variable_name): string {
    $return = '';
    if (is_object($variable)) {
      if (!empty($variable->required)) {
        $return .= self::exportSingleSettingToPhp($variable, "$variable_name = ", "\n");
      }
    }
    else {
      foreach ($variable as $k => $v) {
        $return .= self::exportSettingsToPhp($v, $variable_name . "['" . $k . "']");
      }
    }
    return $return;
  }

  /**
   * Exports the value of a value property and adds the comment if it exists.
   *
   * @param object $variable
   *   A stdClass object with at least a value property.
   * @param string $prefix
   *   A string to prepend to the variable's value.
   * @param string $suffix
   *   A string to append to the variable's value.
   *
   * @return string
   *   A string containing valid PHP code of the variable suitable for placing
   *   into settings.php.
   */
  private static function exportSingleSettingToPhp(object $variable, string $prefix = '', string $suffix = ''): string {
    $return = $prefix . var_export($variable->value, TRUE) . ';';
    if (!empty($variable->comment)) {
      $return .= ' // ' . $variable->comment;
    }
    $return .= $suffix;
    return $return;
  }

}
