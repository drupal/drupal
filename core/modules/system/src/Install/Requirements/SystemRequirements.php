<?php

declare(strict_types=1);

namespace Drupal\system\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Component\FileSystem\FileSystem as FileSystemComponent;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\OpCodeCache;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\Core\Utility\PhpRequirements;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Install time requirements for the system module.
 */
class SystemRequirements implements InstallRequirementsInterface {

  // cspell:ignore quickedit

  /**
   * An array of machine names of modules that were removed from Drupal core.
   */
  public const DRUPAL_CORE_REMOVED_MODULE_LIST = [
    'action' => 'Action UI',
    'ban' => 'Ban',
    'book' => 'Book',
    'aggregator' => 'Aggregator',
    'ckeditor' => 'CKEditor',
    'color' => 'Color',
    'contact' => 'Contact',
    'field_layout' => 'Field Layout',
    'forum' => 'Forum',
    'hal' => 'HAL',
    'history' => 'History',
    'quickedit' => 'Quick Edit',
    'rdf' => 'RDF',
    'statistics' => 'Statistics',
    'tour' => 'Tour',
    'tracker' => 'Tracker',
  ];

  /**
   * An array of machine names of themes that were removed from Drupal core.
   */
  public const DRUPAL_CORE_REMOVED_THEME_LIST = [
    'bartik' => 'Bartik',
    'classy' => 'Classy',
    'seven' => 'Seven',
    'stable' => 'Stable',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    return self::checkRequirements();
  }

  /**
   * Check requirements for a given phase.
   *
   * @return array
   *   An associative array of requirements, as documented in
   *   hook_runtime_requirements() and hook_update_requirements().
   */
  protected static function checkRequirements(): array {
    global $install_state;

    // Get the current default PHP requirements for this version of Drupal.
    $minimum_supported_php = PhpRequirements::getMinimumSupportedPhp();

    // Reset the extension lists.
    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_extension_list */
    $module_extension_list = \Drupal::service('extension.list.module');
    $module_extension_list->reset();
    /** @var \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list */
    $theme_extension_list = \Drupal::service('extension.list.theme');
    $theme_extension_list->reset();
    $requirements = [];

    // Web server information.
    $request_object = \Drupal::request();
    $software = $request_object->server->get('SERVER_SOFTWARE');
    $requirements['webserver'] = [
      'title' => t('Web server'),
      'value' => $software,
    ];

    // Tests clean URL support.
    if ($install_state['interactive'] && !$request_object->query->has('rewrite') && str_contains($software, 'Apache')) {
      // If the Apache rewrite module is not enabled, Apache version must be >=
      // 2.2.16 because of the FallbackResource directive in the root .htaccess
      // file. Since the Apache version reported by the server is dependent on
      // the ServerTokens setting in httpd.conf, we may not be able to
      // determine if a given config is valid. Thus we are unable to use
      // version_compare() as we need have three possible outcomes: the version
      // of Apache is greater than 2.2.16, is less than 2.2.16, or cannot be
      // determined accurately. In the first case, we encourage the use of
      // mod_rewrite; in the second case, we raise an error regarding the
      // minimum Apache version; in the third case, we raise a warning that the
      // current version of Apache may not be supported.
      $rewrite_warning = FALSE;
      $rewrite_error = FALSE;
      $apache_version_string = 'Apache';

      // Determine the Apache version number: major, minor and revision.
      if (preg_match('/Apache\/(\d+)\.?(\d+)?\.?(\d+)?/', $software, $matches)) {
        $apache_version_string = $matches[0];

        // Major version number
        if ($matches[1] < 2) {
          $rewrite_error = TRUE;
        }
        elseif ($matches[1] == 2) {
          if (!isset($matches[2])) {
            $rewrite_warning = TRUE;
          }
          elseif ($matches[2] < 2) {
            $rewrite_error = TRUE;
          }
          elseif ($matches[2] == 2) {
            if (!isset($matches[3])) {
              $rewrite_warning = TRUE;
            }
            elseif ($matches[3] < 16) {
              $rewrite_error = TRUE;
            }
          }
        }
      }
      else {
        $rewrite_warning = TRUE;
      }

      if ($rewrite_warning) {
        $requirements['apache_version'] = [
          'title' => t('Apache version'),
          'value' => $apache_version_string,
          'severity' => RequirementSeverity::Warning,
          'description' => t('Due to the settings for ServerTokens in httpd.conf, it is impossible to accurately determine the version of Apache running on this server. The reported value is @reported, to run Drupal without mod_rewrite, a minimum version of 2.2.16 is needed.', ['@reported' => $apache_version_string]),
        ];
      }

      if ($rewrite_error) {
        $requirements['Apache version'] = [
          'title' => t('Apache version'),
          'value' => $apache_version_string,
          'severity' => RequirementSeverity::Error,
          'description' => t('The minimum version of Apache needed to run Drupal without mod_rewrite enabled is 2.2.16. See the <a href=":link">enabling clean URLs</a> page for more information on mod_rewrite.', [':link' => 'https://www.drupal.org/docs/8/clean-urls-in-drupal-8']),
        ];
      }

      if (!$rewrite_error && !$rewrite_warning) {
        $requirements['rewrite_module'] = [
          'title' => t('Clean URLs'),
          'value' => t('Disabled'),
          'severity' => RequirementSeverity::Warning,
          'description' => t('Your server is capable of using clean URLs, but it is not enabled. Using clean URLs gives an improved user experience and is recommended. <a href=":link">Enable clean URLs</a>', [':link' => 'https://www.drupal.org/docs/8/clean-urls-in-drupal-8']),
        ];
      }
    }

    // Verify the user is running a supported PHP version.
    // If the site is running a recommended version of PHP, just display it
    // as an informational message on the status report. This will be overridden
    // with an error or warning if the site is running older PHP versions for
    // which Drupal has already or will soon drop support.
    $phpversion = $phpversion_label = phpversion();
    $requirements['php'] = [
      'title' => t('PHP'),
      'value' => $phpversion_label,
    ];

    // Check if the PHP version is below what Drupal supports.
    if (version_compare($phpversion, $minimum_supported_php) < 0) {
      $requirements['php']['description'] = t('Your PHP installation is too old. Drupal requires at least PHP %version. It is recommended to upgrade to PHP version %recommended or higher for the best ongoing support. See <a href="http://php.net/supported-versions.php">PHP\'s version support documentation</a> and the <a href=":php_requirements">Drupal PHP requirements</a> page for more information.',
        [
          '%version' => $minimum_supported_php,
          '%recommended' => \Drupal::RECOMMENDED_PHP,
          ':php_requirements' => 'https://www.drupal.org/docs/system-requirements/php-requirements',
        ]
      );

      // If the PHP version is also below the absolute minimum allowed, it's not
      // safe to continue with the requirements check, and should always be an
      // error.
      if (version_compare($phpversion, \Drupal::MINIMUM_PHP) < 0) {
        $requirements['php']['severity'] = RequirementSeverity::Error;
        return $requirements;
      }
      // Otherwise, the message should be a warning during installation.
      $requirements['php']['severity'] = RequirementSeverity::Warning;
    }

    // Test for PHP extensions.
    $requirements['php_extensions'] = [
      'title' => t('PHP extensions'),
    ];

    $missing_extensions = [];
    $required_extensions = [
      'date',
      'dom',
      'filter',
      'gd',
      'hash',
      'json',
      'pcre',
      'pdo',
      'session',
      'SimpleXML',
      'SPL',
      'tokenizer',
      'xml',
      'zlib',
    ];
    foreach ($required_extensions as $extension) {
      if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
      }
    }

    if (!empty($missing_extensions)) {
      $description = t('Drupal requires you to enable the PHP extensions in the following list (see the <a href=":system_requirements">system requirements page</a> for more information):', [
        ':system_requirements' => 'https://www.drupal.org/docs/system-requirements',
      ]);

      // We use twig inline_template to avoid twig's autoescape.
      $description = [
        '#type' => 'inline_template',
        '#template' => '{{ description }}{{ missing_extensions }}',
        '#context' => [
          'description' => $description,
          'missing_extensions' => [
            '#theme' => 'item_list',
            '#items' => $missing_extensions,
          ],
        ],
      ];

      $requirements['php_extensions']['value'] = t('Disabled');
      $requirements['php_extensions']['severity'] = RequirementSeverity::Error;
      $requirements['php_extensions']['description'] = $description;
    }
    else {
      $requirements['php_extensions']['value'] = t('Enabled');
    }

    // Check to see if OPcache is installed.
    if (!OpCodeCache::isEnabled()) {
      $requirements['php_opcache'] = [
        'value' => t('Not enabled'),
        'severity' => RequirementSeverity::Warning,
        'description' => t('PHP OPcode caching can improve your site\'s performance considerably. It is <strong>highly recommended</strong> to have <a href="http://php.net/manual/opcache.installation.php" target="_blank">OPcache</a> installed on your server.'),
      ];
    }
    else {
      $requirements['php_opcache']['value'] = t('Enabled');
    }
    $requirements['php_opcache']['title'] = t('PHP OPcode caching');

    // Test whether we have a good source of random bytes.
    $requirements['php_random_bytes'] = [
      'title' => t('Random number generation'),
    ];
    try {
      $bytes = random_bytes(10);
      if (strlen($bytes) != 10) {
        throw new \Exception("Tried to generate 10 random bytes, generated '" . strlen($bytes) . "'");
      }
      $requirements['php_random_bytes']['value'] = t('Successful');
    }
    catch (\Exception $e) {
      // If /dev/urandom is not available on a UNIX-like system, check whether
      // open_basedir restrictions are the cause.
      $open_basedir_blocks_urandom = FALSE;
      if (DIRECTORY_SEPARATOR === '/' && !@is_readable('/dev/urandom')) {
        $open_basedir = ini_get('open_basedir');
        if ($open_basedir) {
          $open_basedir_paths = explode(PATH_SEPARATOR, $open_basedir);
          $open_basedir_blocks_urandom = !array_intersect(['/dev', '/dev/', '/dev/urandom'], $open_basedir_paths);
        }
      }
      $args = [
        ':drupal-php' => 'https://www.drupal.org/docs/system-requirements/php-requirements',
        '%exception_message' => $e->getMessage(),
      ];
      if ($open_basedir_blocks_urandom) {
        $requirements['php_random_bytes']['description'] = t('Drupal is unable to generate highly randomized numbers, which means certain security features like password reset URLs are not as secure as they should be. Instead, only a slow, less-secure fallback generator is available. The most likely cause is that open_basedir restrictions are in effect and /dev/urandom is not on the allowed list. See the <a href=":drupal-php">system requirements</a> page for more information. %exception_message', $args);
      }
      else {
        $requirements['php_random_bytes']['description'] = t('Drupal is unable to generate highly randomized numbers, which means certain security features like password reset URLs are not as secure as they should be. Instead, only a slow, less-secure fallback generator is available. See the <a href=":drupal-php">system requirements</a> page for more information. %exception_message', $args);
      }
      $requirements['php_random_bytes']['value'] = t('Less secure');
      $requirements['php_random_bytes']['severity'] = RequirementSeverity::Error;
    }

    // Test for PDO (database).
    $requirements['database_extensions'] = [
      'title' => t('Database support'),
    ];

    // Make sure PDO is available.
    $database_ok = extension_loaded('pdo');
    if (!$database_ok) {
      $pdo_message = t('Your web server does not appear to support PDO (PHP Data Objects). Ask your hosting provider if they support the native PDO extension. See the <a href=":link">system requirements</a> page for more information.', [
        ':link' => 'https://www.drupal.org/docs/system-requirements/php-requirements#database',
      ]);
    }
    else {
      // Make sure at least one supported database driver exists.
      if (empty(Database::getDriverList()->getInstallableList())) {
        $database_ok = FALSE;
        $pdo_message = t('Your web server does not appear to support any common PDO database extensions. Check with your hosting provider to see if they support PDO (PHP Data Objects) and offer any databases that <a href=":drupal-databases">Drupal supports</a>.', [
          ':drupal-databases' => 'https://www.drupal.org/docs/system-requirements/database-server-requirements',
        ]);
      }
      // Make sure the native PDO extension is available, not the older PEAR
      // version. (See install_verify_pdo() for details.)
      if (!defined('PDO::ATTR_DEFAULT_FETCH_MODE')) {
        $database_ok = FALSE;
        $pdo_message = t('Your web server seems to have the wrong version of PDO installed. Drupal requires the PDO extension from PHP core. This system has the older PECL version. See the <a href=":link">system requirements</a> page for more information.', [
          ':link' => 'https://www.drupal.org/docs/system-requirements/php-requirements#database',
        ]);
      }
    }

    if (!$database_ok) {
      $requirements['database_extensions']['value'] = t('Disabled');
      $requirements['database_extensions']['severity'] = RequirementSeverity::Error;
      $requirements['database_extensions']['description'] = $pdo_message;
    }
    else {
      $requirements['database_extensions']['value'] = t('Enabled');
    }

    // Test PHP memory_limit
    $memory_limit = ini_get('memory_limit');
    $requirements['php_memory_limit'] = [
      'title' => t('PHP memory limit'),
      'value' => $memory_limit == -1 ? t('-1 (Unlimited)') : $memory_limit,
    ];

    if (!Environment::checkMemoryLimit(\Drupal::MINIMUM_PHP_MEMORY_LIMIT, $memory_limit)) {
      $description = [];
      $description['phase'] = t('Consider increasing your PHP memory limit to %memory_minimum_limit to help prevent errors in the installation process.', ['%memory_minimum_limit' => \Drupal::MINIMUM_PHP_MEMORY_LIMIT]);

      if (!empty($description['phase'])) {
        if ($php_ini_path = get_cfg_var('cfg_file_path')) {
          $description['memory'] = t('Increase the memory limit by editing the memory_limit parameter in the file %configuration-file and then restart your web server (or contact your system administrator or hosting provider for assistance).', ['%configuration-file' => $php_ini_path]);
        }
        else {
          $description['memory'] = t('Contact your system administrator or hosting provider for assistance with increasing your PHP memory limit.');
        }

        $handbook_link = t('For more information, see the online handbook entry for <a href=":memory-limit">increasing the PHP memory limit</a>.', [':memory-limit' => 'https://www.drupal.org/node/207036']);

        $description = [
          '#type' => 'inline_template',
          '#template' => '{{ description_phase }} {{ description_memory }} {{ handbook }}',
          '#context' => [
            'description_phase' => $description['phase'],
            'description_memory' => $description['memory'],
            'handbook' => $handbook_link,
          ],
        ];

        $requirements['php_memory_limit']['description'] = $description;
        $requirements['php_memory_limit']['severity'] = RequirementSeverity::Warning;
      }
    }

    // During an install we need to make assumptions about the file system
    // unless overrides are provided in settings.php.
    $directories = [];
    if ($file_public_path = Settings::get('file_public_path')) {
      $directories[] = $file_public_path;
    }
    else {
      // If we are installing Drupal, the settings.php file might not exist
      // yet in the intended site directory, so don't require it.
      $request = Request::createFromGlobals();
      $site_path = DrupalKernel::findSitePath($request);
      $directories[] = $site_path . '/files';
    }
    if ($file_private_path = Settings::get('file_private_path')) {
      $directories[] = $file_private_path;
    }
    if (Settings::get('file_temp_path')) {
      $directories[] = Settings::get('file_temp_path');
    }
    else {
      // If the temporary directory is not overridden use an appropriate
      // temporary path for the system.
      $directories[] = FileSystemComponent::getOsTemporaryDirectory();
    }

    // Check the config directory if it is defined in settings.php. If it isn't
    // defined, the installer will create a valid config directory later.
    $config_sync_directory = Settings::get('config_sync_directory');
    if (!empty($config_sync_directory)) {
      // If we're installing Drupal try and create the config sync directory.
      if (!is_dir($config_sync_directory)) {
        \Drupal::service('file_system')->prepareDirectory($config_sync_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      }
      if (!is_dir($config_sync_directory)) {
        $description = t('An automated attempt to create the directory %directory failed, possibly due to a permissions problem. To proceed with the installation, either create the directory and modify its permissions manually or ensure that the installer has the permissions to create it automatically. For more information, see INSTALL.txt or the <a href=":handbook_url">online handbook</a>.', ['%directory' => $config_sync_directory, ':handbook_url' => 'https://www.drupal.org/server-permissions']);
        $requirements['config sync directory'] = [
          'title' => t('Configuration sync directory'),
          'description' => $description,
          'severity' => RequirementSeverity::Error,
        ];
      }
    }

    $requirements['file system'] = [
      'title' => t('File system'),
    ];

    $error = '';
    // For installer, create the directories if possible.
    foreach ($directories as $directory) {
      if (!$directory) {
        continue;
      }
      \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $is_writable = is_writable($directory);
      $is_directory = is_dir($directory);
      if (!$is_writable || !$is_directory) {
        $description = '';
        $requirements['file system']['value'] = t('Not writable');
        if (!$is_directory) {
          $error = t('The directory %directory does not exist.', ['%directory' => $directory]);
        }
        else {
          $error = t('The directory %directory is not writable.', ['%directory' => $directory]);
        }
        // The files directory requirement check is done only during install.
        // For the installer UI, we need different wording. 'value' will
        // be treated as version, so provide none there.
        $description = t('An automated attempt to create this directory failed, possibly due to a permissions problem. To proceed with the installation, either create the directory and modify its permissions manually or ensure that the installer has the permissions to create it automatically. For more information, see INSTALL.txt or the <a href=":handbook_url">online handbook</a>.', [':handbook_url' => 'https://www.drupal.org/server-permissions']);
        $requirements['file system']['value'] = '';
        $description = [
          '#type' => 'inline_template',
          '#template' => '{{ error }} {{ description }}',
          '#context' => [
            'error' => $error,
            'description' => $description,
          ],
        ];
        $requirements['file system']['description'] = $description;
        $requirements['file system']['severity'] = RequirementSeverity::Error;
      }
      else {
        // This function can be called before the config_cache table has been
        // created.
        $requirements['file system']['value'] = t('Writable (<em>public</em> download method)');
      }
    }

    // Returns Unicode library status and errors.
    $libraries = [
      Unicode::STATUS_SINGLEBYTE => t('Standard PHP'),
      Unicode::STATUS_MULTIBYTE => t('PHP Mbstring Extension'),
      Unicode::STATUS_ERROR => t('Error'),
    ];
    $severities = [
      Unicode::STATUS_SINGLEBYTE => RequirementSeverity::Warning,
      Unicode::STATUS_MULTIBYTE => NULL,
      Unicode::STATUS_ERROR => RequirementSeverity::Error,
    ];
    $failed_check = Unicode::check();
    $library = Unicode::getStatus();

    $requirements['unicode'] = [
      'title' => t('Unicode library'),
      'value' => $libraries[$library],
      'severity' => $severities[$library],
    ];
    switch ($failed_check) {
      case 'mb_strlen':
        $requirements['unicode']['description'] = t('Operations on Unicode strings are emulated on a best-effort basis. Install the <a href="http://php.net/mbstring">PHP mbstring extension</a> for improved Unicode support.');
        break;

      case 'mbstring.encoding_translation':
        $requirements['unicode']['description'] = t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.encoding_translation</em> setting. Refer to the <a href="http://php.net/mbstring">PHP mbstring documentation</a> for more information.');
        break;
    }

    // Check xdebug.max_nesting_level, as some pages will not work if it is too
    // low.
    if (extension_loaded('xdebug')) {
      // Setting this value to 256 was considered adequate on Xdebug 2.3
      // (see http://bugs.xdebug.org/bug_view_page.php?bug_id=00001100)
      $minimum_nesting_level = 256;
      $current_nesting_level = ini_get('xdebug.max_nesting_level');

      if ($current_nesting_level < $minimum_nesting_level) {
        $requirements['xdebug_max_nesting_level'] = [
          'title' => t('Xdebug settings'),
          'value' => t('xdebug.max_nesting_level is set to %value.', ['%value' => $current_nesting_level]),
          'description' => t('Set <code>xdebug.max_nesting_level=@level</code> in your PHP configuration as some pages in your Drupal site will not work when this setting is too low.', ['@level' => $minimum_nesting_level]),
          'severity' => RequirementSeverity::Error,
        ];
      }
    }

    // Installations on Windows can run into limitations with MAX_PATH if the
    // Drupal root directory is too deep in the filesystem. Generally this
    // shows up in cached Twig templates and other public files with long
    // directory or file names. There is no definite root directory depth below
    // which Drupal is guaranteed to function correctly on Windows. Since
    // problems are likely with more than 100 characters in the Drupal root
    // path, show an error.
    if (str_starts_with(PHP_OS, 'WIN')) {
      $depth = strlen(realpath(DRUPAL_ROOT . '/' . PublicStream::basePath()));
      if ($depth > 120) {
        $requirements['max_path_on_windows'] = [
          'title' => t('Windows installation depth'),
          'description' => t('The public files directory path is %depth characters. Paths longer than 120 characters will cause problems on Windows.', ['%depth' => $depth]),
          'severity' => RequirementSeverity::Error,
        ];
      }
    }
    // Check to see if dates will be limited to 1901-2038.
    if (PHP_INT_SIZE <= 4) {
      $requirements['limited_date_range'] = [
        'title' => t('Limited date range'),
        'value' => t('Your PHP installation has a limited date range.'),
        'description' => t('You are running on a system where PHP is compiled or limited to using 32-bit integers. This will limit the range of dates and timestamps to the years 1901-2038. Read about the <a href=":url">limitations of 32-bit PHP</a>.', [':url' => 'https://www.drupal.org/docs/system-requirements/limitations-of-32-bit-php']),
        'severity' => RequirementSeverity::Warning,
      ];
    }

    // During installs from configuration don't support install profiles that
    // implement hook_install.
    if (!empty($install_state['config_install_path'])) {
      $install_hook = $install_state['parameters']['profile'] . '_install';
      if (function_exists($install_hook)) {
        $requirements['config_install'] = [
          'title' => t('Configuration install'),
          'value' => $install_state['parameters']['profile'],
          'description' => t('The selected profile has a hook_install() implementation and therefore can not be installed from configuration.'),
          'severity' => RequirementSeverity::Error,
        ];
      }
    }

    return $requirements;
  }

  /**
   * Display requirements from security advisories.
   *
   * @param array[] $requirements
   *   The requirements array as specified in hook_requirements().
   */
  public static function systemAdvisoriesRequirements(array &$requirements): void {
    if (!\Drupal::config('system.advisories')->get('enabled')) {
      return;
    }

    /** @var \Drupal\system\SecurityAdvisories\SecurityAdvisoriesFetcher $fetcher */
    $fetcher = \Drupal::service('system.sa_fetcher');
    try {
      $advisories = $fetcher->getSecurityAdvisories(TRUE, 5);
    }
    catch (ClientExceptionInterface $exception) {
      $requirements['system_advisories']['title'] = t('Critical security announcements');
      $requirements['system_advisories']['severity'] = RequirementSeverity::Warning;
      $requirements['system_advisories']['description'] = ['#theme' => 'system_security_advisories_fetch_error_message'];
      Error::logException(\Drupal::logger('system'), $exception, 'Failed to retrieve security advisory data.');
      return;
    }

    if (!empty($advisories)) {
      $advisory_links = [];
      $severity = RequirementSeverity::Warning;
      foreach ($advisories as $advisory) {
        if (!$advisory->isPsa()) {
          $severity = RequirementSeverity::Error;
        }
        $advisory_links[] = new Link($advisory->getTitle(), Url::fromUri($advisory->getUrl()));
      }
      $requirements['system_advisories']['title'] = t('Critical security announcements');
      $requirements['system_advisories']['severity'] = $severity;
      $requirements['system_advisories']['description'] = [
        'list' => [
          '#theme' => 'item_list',
          '#items' => $advisory_links,
        ],
      ];
      if (\Drupal::moduleHandler()->moduleExists('help')) {
        $requirements['system_advisories']['description']['help_link'] = Link::createFromRoute(
          'What are critical security announcements?',
          'help.page', ['name' => 'system'],
          ['fragment' => 'security-advisories']
        )->toRenderable();
      }
    }
  }

}
