<?php

namespace Drupal\package_manager\Hook;

use Drupal\package_manager\ComposerInspector;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for package_manager.
 */
class PackageManagerHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name) : ?string {
    switch ($route_name) {
      case 'help.page.package_manager':
        $output = '<h3 id="package-manager-about">' . t('About') . '</h3>';
        $output .= '<p>' . t('Package Manager is a framework for updating Drupal core and installing contributed modules and themes via Composer. It has no user interface, but it provides an API for creating a temporary copy of the current site, making changes to the copy, and then syncing those changes back into the live site.') . '</p>';
        $output .= '<p>' . t('Package Manager dispatches events before and after various operations, and external code can integrate with it by subscribing to those events. For more information, see <code>package_manager.api.php</code>.') . '</p>';
        $output .= '<h3 id="package-manager-requirements">' . t('Requirements') . '</h3>';
        $output .= '<ul>';
        $output .= '  <li>' . t("The Drupal application's codebase must be writable in order to use Automatic Updates. This includes Drupal core, modules, themes and the Composer dependencies in the <code>vendor</code> directory. This makes Automatic Updates incompatible with some hosting platforms.") . '</li>';
        $output .= '  <li>' . t('Package Manager requires a Composer executable whose version satisfies <code>@version</code>, and PHP must have permission to run it.', ['@version' => ComposerInspector::SUPPORTED_VERSION]) . '</li>';
        $output .= '  <li>' . t("Your Drupal site's <code>composer.json</code> file must be valid according to <code>composer validate</code>. See <a href=\":url\">Composer's documentation</a> for more information.", [':url' => 'https://getcomposer.org/doc/03-cli.md#validate']) . '</li>';
        $output .= '  <li>' . t('Composer must be configured for secure downloads. This means that <a href=":disable-tls">the <code>disable-tls</code> option</a> must be <code>false</code>, and <a href=":secure-http">the <code>secure-http</code> option</a> must be <code>true</code> in the <code>config</code> section of your <code>composer.json</code> file. If these options are not set in your <code>composer.json</code>, Composer will behave securely by default. To set these values at the command line, run the following commands:', [
          ':disable-tls' => 'https://getcomposer.org/doc/06-config.md#disable-tls',
          ':secure-http' => 'https://getcomposer.org/doc/06-config.md#secure-http',
        ]);
        $output .= '<pre><code>';
        $output .= "composer config --unset disable-tls\n";
        $output .= "composer config --unset secure-http\n";
        $output .= '</code></pre></li></ul>';
        $output .= '<h3 id="package-manager-limitations">' . t('Limitations') . '</h3>';
        $output .= '<p>' . t("Because Package Manager modifies the current site's code base, it is intentionally limited in certain ways to prevent unexpected changes to the live site:") . '</p>';
        $output .= '<ul>';
        $output .= '  <li>' . t('It does not support Drupal multi-site installations.') . '</li>';
        $output .= '  <li>' . t('It only allows supported Composer plugins. If you have any, see <a href="#package-manager-faq-unsupported-composer-plugin">What if it says I have unsupported Composer plugins in my codebase?</a>.') . '</li>';
        $output .= '  <li>' . t('It does not automatically perform version control operations, e.g., with Git. Site administrators are responsible for committing updates.') . '</li>';
        $output .= '  <li>' . t('It can only maintain one copy of the site at any given time. If a copy of the site already exists, another one cannot be created until the existing copy is destroyed.') . '</li>';
        $output .= '  <li>' . t('It associates the temporary copy of the site with the user or session that originally created it, and only that user or session can make changes to it.') . '</li>';
        $output .= '  <li>' . t('It does not allow modules to be uninstalled while syncing changes into live site.') . '</li>';
        $output .= '</ul>';
        $output .= '<p>' . t('For more information, see the <a href=":url">online documentation for the Package Manager module</a>.', [':url' => 'https://www.drupal.org/docs/8/core/modules/package-manager']) . '</p>';
        $output .= '<h3 id="package-manager-faq">' . t('FAQ') . '</h3>';
        $output .= '<h4 id="package-manager-composer-related-faq">' . t('FAQs related to Composer') . '</h4>';
        $output .= '<ul>';
        $output .= '  <li>' . t('What if it says the <code>proc_open()</code> function is disabled on your PHP installation?');
        $output .= '    <p>' . t('Ask your system administrator to remove <code>proc_open()</code> from the <a href=":url">disable_functions</a> setting in <code>php.ini</code>.', [':url' => 'https://www.php.net/manual/en/ini.core.php#ini.disable-functions']) . '</p>';
        $output .= '  </li>';
        $output .= '  <li>' . t('What if it says the <code>composer</code> executable cannot be found?');
        $output .= '    <p>' . t("If the <code>composer</code> executable's path cannot be automatically determined, it can be explicitly set by adding the following line to <code>settings.php</code>:") . '</p>';
        $output .= "    <pre><code>\$config['package_manager.settings']['executables']['composer'] = '/full/path/to/composer.phar';</code></pre>";
        $output .= '  </li>';
        $output .= '  <li>' . t('What if it says the detected version of Composer is not supported?');
        $output .= '    <p>' . t('The version of the <code>composer</code> executable must satisfy <code>@version</code>. See the <a href=":url">the Composer documentation</a> for more information, or use this command to update Composer:', [
          '@version' => ComposerInspector::SUPPORTED_VERSION,
          ':url' => 'https://getcomposer.org/doc/03-cli.md#self-update-selfupdate',
        ]) . '</p>';
        $output .= '    <pre><code>composer self-update</code></pre>';
        $output .= '  </li>';
        $output .= '  <li>' . t('What if it says the <code>composer validate</code> command failed?');
        $output .= '    <p>' . t('Composer detected problems with your <code>composer.json</code> and/or <code>composer.lock</code> files, and the project is not in a completely valid state. See <a href=":url">the Composer documentation</a> for more information.', [':url' => 'https://getcomposer.org/doc/04-schema.md']) . '</p>';
        $output .= '  </li>';
        $output .= '</ul>';
        $output .= '<h4 id="package-manager-faq-rsync">' . t('Using rsync') . '</h4>';
        $output .= '<p>' . t('Package Manager must be able to run <code>rsync</code> to copy files between the live site and the stage directory. Package Manager will try to detect the path to <code>rsync</code>, but if it cannot be detected, you can set it explicitly by adding the following line to <code>settings.php</code>:') . '</p>';
        $output .= "<pre><code>\$config['package_manager.settings']['executables']['rsync'] = '/full/path/to/rsync';</code></pre>";
        $output .= '<h4 id="package-manager-tuf-info">' . t('Enabling PHP-TUF protection') . '</h4>';
        $output .= '<p>' . t('Package Manager requires <a href=":php-tuf">PHP-TUF</a>, which implements <a href=":tuf">The Update Framework</a> as a way to help secure Composer package downloads via the <a href=":php-tuf-plugin">PHP-TUF Composer integration plugin</a>. This plugin must be installed and configured properly in order to use Package Manager.', [
          ':php-tuf' => 'https://github.com/php-tuf/php-tuf',
          ':tuf' => 'https://theupdateframework.io/',
          ':php-tuf-plugin' => 'https://github.com/php-tuf/composer-integration',
        ]) . '</p>';
        $output .= '<p>' . t('To install and configure the plugin as needed, you can run the following commands:') . '</p>';
        $output .= '<pre><code>';
        $output .= "composer config allow-plugins.php-tuf/composer-integration true\n";
        $output .= "composer require php-tuf/composer-integration";
        $output .= '</code></pre>';
        $output .= '<p>' . t('Package Manager currently requires the <code>https://packages.drupal.org/8</code> Composer repository to be protected by TUF. To set this up, run the following command:') . '</p>';
        $output .= '<pre><code>';
        $output .= "composer tuf:protect https://packages.drupal.org/8\n";
        $output .= '</code></pre>';
        $output .= '<h4 id="package-manager-faq-unsupported-composer-plugin">' . t('What if it says I have unsupported Composer plugins in my codebase?') . '</h4>';
        $output .= '<p>' . t('A fresh Drupal installation only uses supported Composer plugins, but some modules or themes may depend on additional Composer plugins. <a href=":new-issue">Create a new issue</a> when you encounter this.', [':new-issue' => 'https://www.drupal.org/node/add/project-issue/auto_updates']) . '</p>';
        $output .= '<p>' . t('It is possible to <em>trust</em> additional Composer plugins, but this requires significant expertise: understanding the code of that Composer plugin, what the effects on the file system are and how it affects the Package Manager module. Some Composer plugins could result in a broken site!') . '</p>';
        $output .= '<h4 id="package-manager-faq-composer-patches-installed-or-removed">' . t('What if it says <code>cweagans/composer-patches</code> cannot be installed/removed?') . '</h4>';
        $output .= '<p>' . t('Installation or removal of <code>cweagans/composer-patches</code> via Package Manager is not supported. You can install or remove it manually by running Composer commands in your site root.') . '</p>';
        $output .= '<p>' . t('To install it:') . '</p>';
        $output .= '<pre><code>composer require cweagans/composer-patches</code></pre>';
        $output .= '<p>' . t('To remove it:') . '</p>';
        $output .= '<pre><code>composer remove cweagans/composer-patches</code></pre>';
        $output .= '<h4 id="package-manager-faq-composer-patches-not-a-root-dependency">' . t('What if it says <code>cweagans/composer-patches</code> must be a root dependency?') . '</h4>';
        $output .= '<p>' . t('If <code>cweagans/composer-patches</code> is installed, it must be defined as a dependency of the main project (i.e., it must be listed in the <code>require</code> or <code>require-dev</code> section of <code>composer.json</code>). You can run the following command in your site root to add it as a dependency of the main project:') . '</p>';
        $output .= "<pre><code>composer require cweagans/composer-patches</code></pre>";
        return $output;
    }
    return NULL;
  }

}
