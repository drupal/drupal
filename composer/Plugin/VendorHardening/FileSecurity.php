<?php

namespace Drupal\Composer\Plugin\VendorHardening;

/**
 * Provides file security functions.
 *
 * IMPORTANT: This file is duplicated at /lib/Drupal/Component/FileSecurity.
 * If any change is made here, the same change should be made in the duplicate.
 * See https://www.drupal.org/project/drupal/issues/3079481.
 *
 * @internal
 */
class FileSecurity {

  /**
   * Writes an .htaccess file in the given directory, if it doesn't exist.
   *
   * @param string $directory
   *   The directory.
   * @param bool $deny_public_access
   *   (optional) Set to FALSE to ensure an .htaccess file for an open and
   *   public directory. Default is TRUE.
   * @param bool $force
   *   (optional) Set to TRUE to force overwrite an existing file.
   *
   * @return bool
   *   TRUE if the file already exists or was created. FALSE otherwise.
   */
  public static function writeHtaccess($directory, $deny_public_access = TRUE, $force = FALSE) {
    return self::writeFile($directory, '.htaccess', self::htaccessLines($deny_public_access), $force);
  }

  /**
   * Returns the standard .htaccess lines that Drupal writes.
   *
   * @param bool $deny_public_access
   *   (optional) Set to FALSE to return the .htaccess lines for an open and
   *   public directory that allows Apache to serve files, but not execute code.
   *   The default is TRUE, which returns the .htaccess lines for a private and
   *   protected directory that Apache will deny all access to.
   *
   * @return string
   *   The desired contents of the .htaccess file.
   *
   * @see file_save_htaccess()
   */
  public static function htaccessLines($deny_public_access = TRUE) {
    $lines = static::htaccessPreventExecution();

    if ($deny_public_access) {
      $lines = static::denyPublicAccess() . "\n\n$lines";
    }

    return $lines;
  }

  /**
   * Returns htaccess directives to deny execution in a given directory.
   *
   * @return string
   *   Apache htaccess directives to prevent execution of files in a location.
   */
  protected static function htaccessPreventExecution() {
    return <<<EOF
# Turn off all options we don't need.
Options -Indexes -ExecCGI -Includes -MultiViews

# Set the catch-all handler to prevent scripts from being executed.
SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006
<Files *>
  # Override the handler again if we're run later in the evaluation list.
  SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003
</Files>

# If we know how to do it safely, disable the PHP engine entirely.
<IfModule mod_php7.c>
  php_flag engine off
</IfModule>
EOF;
  }

  /**
   * Returns htaccess directives to block all access to a given directory.
   *
   * @return string
   *   Apache htaccess directives to block access to a location.
   */
  protected static function denyPublicAccess() {
    return <<<EOF
# Deny all requests from Apache 2.4+.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

# Deny all requests from Apache 2.0-2.2.
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>
EOF;
  }

  /**
   * Writes a web.config file in the given directory, if it doesn't exist.
   *
   * @param string $directory
   *   The directory.
   * @param bool $force
   *   (optional) Set to TRUE to force overwrite an existing file.
   *
   * @return bool
   *   TRUE if the file already exists or was created. FALSE otherwise.
   */
  public static function writeWebConfig($directory, $force = FALSE) {
    return self::writeFile($directory, 'web.config', self::webConfigLines(), $force);
  }

  /**
   * Returns the standard web.config lines for security.
   *
   * @return string
   *   The contents of the web.config file.
   */
  public static function webConfigLines() {
    return <<<EOT
<configuration>
  <system.webServer>
    <authorization>
      <deny users="*">
    </authorization>
  </system.webServer>
</configuration>
EOT;
  }

  /**
   * Writes the contents to the file in the given directory.
   *
   * @param string $directory
   *   The directory to write to.
   * @param string $filename
   *   The file name.
   * @param string $contents
   *   The file contents.
   * @param bool $force
   *   TRUE if we should force the write over an existing file.
   *
   * @return bool
   *   TRUE if writing the file was successful.
   */
  protected static function writeFile($directory, $filename, $contents, $force) {
    $file_path = $directory . DIRECTORY_SEPARATOR . $filename;
    // Don't overwrite if the file exists unless forced.
    if (file_exists($file_path) && !$force) {
      return TRUE;
    }
    // Try to write the file. This can fail if concurrent requests are both
    // trying to write a the same time.
    if (@file_put_contents($file_path, $contents)) {
      return @chmod($file_path, 0444);
    }
    return FALSE;
  }

}
