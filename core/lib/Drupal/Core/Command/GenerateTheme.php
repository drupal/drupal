<?php

namespace Drupal\Core\Command;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Theme\StarterKitInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Twig\Util\TemplateDirIterator;

/**
 * Generates a new theme based on latest default markup.
 */
class GenerateTheme extends Command {

  /**
   * The path for the Drupal root.
   *
   * @var string
   */
  private $root;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $name = NULL) {
    parent::__construct($name);

    $this->root = dirname(__DIR__, 5);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('generate-theme')
      ->setDescription('Generates a new theme based on latest default markup.')
      ->addArgument('machine-name', InputArgument::REQUIRED, 'The machine name of the generated theme')
      ->addOption('name', NULL, InputOption::VALUE_OPTIONAL, 'A name for the theme.')
      ->addOption('description', NULL, InputOption::VALUE_OPTIONAL, 'A description of your theme.')
      ->addOption('path', NULL, InputOption::VALUE_OPTIONAL, 'The path where your theme will be created. Defaults to: themes')
      ->addOption('starterkit', NULL, InputOption::VALUE_OPTIONAL, 'The theme to use as the starterkit', 'starterkit_theme')
      ->addUsage('custom_theme --name "Custom Theme" --description "Custom theme generated from a starterkit theme" --path themes')
      ->addUsage('custom_theme --name "Custom Theme" --starterkit mystarterkit');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    // Change the directory to the Drupal root.
    chdir($this->root);

    // Path where the generated theme should be placed.
    $destination_theme = $input->getArgument('machine-name');
    $default_destination = 'themes';
    $destination = trim($input->getOption('path') ?: $default_destination, '/') . '/' . $destination_theme;

    if (is_dir($destination)) {
      $io->getErrorStyle()->error("Theme could not be generated because the destination directory $destination exists already.");
      return 1;
    }

    // Source directory for the theme.
    $source_theme_name = $input->getOption('starterkit');
    if (!$source_theme = $this->getThemeInfo($source_theme_name)) {
      $io->getErrorStyle()->error("Theme source theme $source_theme_name cannot be found.");
      return 1;
    }

    if (!$this->isStarterkitTheme($source_theme)) {
      $io->getErrorStyle()->error("Theme source theme $source_theme_name is not a valid starter kit.");
      return 1;
    }

    $source = $source_theme->getPath();

    if (!is_dir($source)) {
      $io->getErrorStyle()->error("Theme could not be generated because the source directory $source does not exist.");
      return 1;
    }

    $tmp_dir = $this->getUniqueTmpDirPath();
    $this->copyRecursive($source, $tmp_dir);

    // Rename files based on the theme machine name.
    $file_pattern = "/$source_theme_name\.(theme|[^.]+\.yml)/";
    if ($files = @scandir($tmp_dir)) {
      foreach ($files as $file) {
        $location = $tmp_dir . '/' . $file;
        if (is_dir($location)) {
          continue;
        }

        if (preg_match($file_pattern, $file, $matches)) {
          if (!rename($location, $tmp_dir . '/' . $destination_theme . '.' . $matches[1])) {
            $io->getErrorStyle()->error("The file $location could not be moved.");
            return 1;
          }
        }
      }
    }
    else {
      $io->getErrorStyle()->error("Temporary directory $tmp_dir cannot be opened.");
      return 1;
    }

    // Info file.
    $info_file = "$tmp_dir/$destination_theme.info.yml";
    if (!file_exists($info_file)) {
      $io->getErrorStyle()->error("The theme info file $info_file could not be read.");
      return 1;
    }

    $info = Yaml::decode(file_get_contents($info_file));
    $info['name'] = $input->getOption('name') ?: $destination_theme;

    $info['core_version_requirement'] = '^' . $this->getVersion();

    if (!array_key_exists('version', $info)) {
      $confirm_versionless_source_theme = new ConfirmationQuestion(sprintf('The source theme %s does not have a version specified. This makes tracking changes in the source theme difficult. Are you sure you want to continue?', $source_theme->getName()));
      if (!$io->askQuestion($confirm_versionless_source_theme)) {
        return 0;
      }
    }

    $source_version = $info['version'] ?? 'unknown-version';
    if ($source_version === 'VERSION') {
      $source_version = \Drupal::VERSION;
    }
    // A version in the generator string like "9.4.0-dev" is not very helpful.
    // When this occurs, generate a version string that points to a commit.
    if (VersionParser::parseStability($source_version) === 'dev') {
      $git_check = Process::fromShellCommandline('git --help');
      $git_check->run();
      if ($git_check->getExitCode()) {
        $io->error(sprintf('The source theme %s has a development version number (%s). Determining a specific commit is not possible because git is not installed. Either install git or use a tagged release to generate a theme.', $source_theme->getName(), $source_version));
        return 1;
      }

      // Get the git commit for the source theme.
      $git_get_commit = Process::fromShellCommandline("git rev-list --max-count=1 --abbrev-commit HEAD -C $source");
      $git_get_commit->run();
      if ($git_get_commit->getOutput() === '') {
        $confirm_packaged_dev_release = new ConfirmationQuestion(sprintf('The source theme %s has a development version number (%s). Because it is not a git checkout, a specific commit could not be identified. This makes tracking changes in the source theme difficult. Are you sure you want to continue?', $source_theme->getName(), $source_version));
        if (!$io->askQuestion($confirm_packaged_dev_release)) {
          return 0;
        }
        $source_version .= '#unknown-commit';
      }
      else {
        $source_version .= '#' . trim($git_get_commit->getOutput());
      }
    }
    $info['generator'] = "$source_theme_name:$source_version";

    if ($description = $input->getOption('description')) {
      $info['description'] = $description;
    }
    else {
      unset($info['description']);
    }

    // Replace references to libraries.
    if (isset($info['libraries'])) {
      $info['libraries'] = preg_replace("/$source_theme_name(\/.*)/", "$destination_theme$1", $info['libraries']);
    }
    if (isset($info['libraries-extend'])) {
      foreach ($info['libraries-extend'] as $key => $value) {
        $info['libraries-extend'][$key] = preg_replace("/$source_theme_name(\/.*)/", "$destination_theme$1", $info['libraries-extend'][$key]);
      }
    }
    if (isset($info['libraries-override'])) {
      foreach ($info['libraries-override'] as $key => $value) {
        if (isset($info['libraries-override'][$key]['dependencies'])) {
          $info['libraries-override'][$key]['dependencies'] = preg_replace("/$source_theme_name(\/.*)/", "$destination_theme$1", $info['libraries-override'][$key]['dependencies']);
        }
      }
    }

    if (!file_put_contents($info_file, Yaml::encode($info))) {
      $io->getErrorStyle()->error("The theme info file $info_file could not be written.");
      return 1;
    }

    // Replace references to libraries in libraries.yml file.
    $libraries_file = "$tmp_dir/$destination_theme.libraries.yml";
    if (file_exists($libraries_file)) {
      $libraries = Yaml::decode(file_get_contents($libraries_file));
      foreach ($libraries as $key => $value) {
        if (isset($libraries[$key]['dependencies'])) {
          $libraries[$key]['dependencies'] = preg_replace("/$source_theme_name(\/.*)/", "$destination_theme$1", $libraries[$key]['dependencies']);
        }
      }

      if (!file_put_contents($libraries_file, Yaml::encode($libraries))) {
        $io->getErrorStyle()->error("The libraries file $libraries_file could not be written.");
        return 1;
      }
    }

    // Rename hooks.
    $theme_file = "$tmp_dir/$destination_theme.theme";
    if (file_exists($theme_file)) {
      if (!file_put_contents($theme_file, preg_replace("/(function )($source_theme_name)(_.*)/", "$1$destination_theme$3", file_get_contents($theme_file)))) {
        $io->getErrorStyle()->error("The theme file $theme_file could not be written.");
        return 1;
      }
    }

    // Rename references to libraries in templates.
    $iterator = new TemplateDirIterator(new \RegexIterator(
      new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($tmp_dir), \RecursiveIteratorIterator::LEAVES_ONLY
      ), '/' . preg_quote('.html.twig') . '$/'
    ));

    foreach ($iterator as $template_file => $contents) {
      $new_template_content = preg_replace("/(attach_library\(['\")])$source_theme_name(\/.*['\"]\))/", "$1$destination_theme$2", $contents);
      if (!file_put_contents($template_file, $new_template_content)) {
        $io->getErrorStyle()->error("The template file $template_file could not be written.");
        return 1;
      }
    }

    $loader = new ClassLoader();
    $loader->addPsr4("Drupal\\$source_theme_name\\", "$source/src");
    $loader->register();

    $generator_classname = "Drupal\\$source_theme_name\\StarterKit";
    if (class_exists($generator_classname)) {
      if (is_a($generator_classname, StarterKitInterface::class, TRUE)) {
        $generator_classname::postProcess($tmp_dir, $destination_theme, $info['name']);
      }
      else {
        $io->getErrorStyle()->error("The $generator_classname does not implement \Drupal\Core\Theme\StarterKitInterface and cannot perform post-processing.");
        return 1;
      }
    }

    if (!rename($tmp_dir, $destination)) {
      $io->getErrorStyle()->error("The theme could not be moved to the destination: $destination.");
      return 1;
    }

    $output->writeln(sprintf('Theme generated successfully to %s', $destination));

    return 0;
  }

  /**
   * Copies files recursively.
   *
   * @param string $src
   *   A file or directory to be copied.
   * @param string $dest
   *   Destination directory where the directory or file should be copied.
   *
   * @throws \RuntimeException
   *   Exception thrown if copying failed.
   */
  private function copyRecursive($src, $dest): void {
    // Copy all subdirectories and files.
    if (is_dir($src)) {
      if (!mkdir($dest, FileSystem::CHMOD_DIRECTORY, FALSE)) {
        throw new \RuntimeException("Directory $dest could not be created");
      }
      $handle = opendir($src);
      while ($file = readdir($handle)) {
        if ($file != "." && $file != "..") {
          $this->copyRecursive("$src/$file", "$dest/$file");
        }
      }
      closedir($handle);
    }
    elseif (is_link($src)) {
      symlink(readlink($src), $dest);
    }
    elseif (!copy($src, $dest)) {
      throw new \RuntimeException("File $src could not be copied to $dest");
    }

    // Set permissions for the directory or file.
    if (!is_link($dest)) {
      if (is_dir($dest)) {
        $mode = FileSystem::CHMOD_DIRECTORY;
      }
      else {
        $mode = FileSystem::CHMOD_FILE;
      }

      if (!chmod($dest, $mode)) {
        throw new \RuntimeException("The file permissions could not be set on $src");
      }
    }
  }

  /**
   * Generates a path to a temporary location.
   *
   * @return string
   */
  private function getUniqueTmpDirPath(): string {
    return sys_get_temp_dir() . '/drupal-starterkit-theme-' . uniqid(md5(microtime()), TRUE);
  }

  /**
   * Gets theme info using the theme name.
   *
   * @param string $theme
   *   The machine name of the theme.
   *
   * @return \Drupal\Core\Extension\Extension|null
   */
  private function getThemeInfo(string $theme): ? Extension {
    $extension_discovery = new ExtensionDiscovery($this->root, FALSE, []);
    $themes = $extension_discovery->scan('theme');

    if (!isset($themes[$theme])) {
      return NULL;
    }

    return $themes[$theme];
  }

  /**
   * Checks if the theme is a starterkit theme.
   *
   * @param \Drupal\Core\Extension\Extension $theme
   *   The theme extension.
   *
   * @return bool
   */
  private function isStarterkitTheme(Extension $theme): bool {
    $info_parser = new InfoParser($this->root);
    $info = $info_parser->parse($theme->getPathname());

    return $info['starterkit'] ?? FALSE === TRUE;
  }

  /**
   * Gets the current Drupal major version.
   *
   * @return string
   */
  private function getVersion(): string {
    return explode('.', \Drupal::VERSION)[0];
  }

}
