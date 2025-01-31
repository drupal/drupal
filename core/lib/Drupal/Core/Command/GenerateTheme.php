<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Theme\StarterKitInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Process\Process;
use function Symfony\Component\String\u;

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
   * GenerateTheme constructor.
   *
   * @param string|null $name
   *   The name of the command; passing null means it must be set in
   *   configure().
   * @param string|null $root
   *   The path for the Drupal root.
   */
  public function __construct(?string $name = NULL, ?string $root = NULL) {
    parent::__construct($name);

    $this->root = $root ?? dirname(__DIR__, 5);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('generate-theme')
      ->setDescription('Generates a new theme based on latest default markup.')
      ->addArgument('machine-name', InputArgument::REQUIRED, 'The machine name of the generated theme')
      ->addOption('name', NULL, InputOption::VALUE_OPTIONAL, 'A name for the theme.')
      ->addOption('description', NULL, InputOption::VALUE_OPTIONAL, 'A description of your theme.', '')
      ->addOption('path', NULL, InputOption::VALUE_OPTIONAL, 'The path where your theme will be created. Defaults to: themes', 'themes')
      ->addOption('starterkit', NULL, InputOption::VALUE_OPTIONAL, 'The theme to use as the starterkit', 'starterkit_theme')
      ->addUsage('custom_theme --name "Custom Theme" --description "Custom theme generated from a starterkit theme" --path themes')
      ->addUsage('custom_theme --name "Custom Theme" --starterkit mystarterkit');
  }

  protected function initialize(InputInterface $input, OutputInterface $output): void {
    if ($input->getOption('name') === NULL) {
      $input->setOption('name', $input->getArgument('machine-name'));
    }

    // Change the directory to the Drupal root.
    chdir($this->root);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $filesystem = new Filesystem();
    $tmpDir = $this->getUniqueTmpDirPath();

    $destination_theme = $input->getArgument('machine-name');
    $starterkit_id = $input->getOption('starterkit');
    $theme_label = $input->getOption('name');

    $io->writeln("<info>Generating theme $theme_label ($destination_theme) from $starterkit_id starterkit.</info>");

    $destination = trim($input->getOption('path'), '/') . '/' . $destination_theme;
    if (is_dir($destination)) {
      $io->getErrorStyle()->error("Theme could not be generated because the destination directory $destination exists already.");
      return 1;
    }

    $starterkit = $this->getThemeInfo($starterkit_id);
    if ($starterkit === NULL) {
      $io->getErrorStyle()->error("Theme source theme $starterkit_id cannot be found.");
      return 1;
    }

    $io->writeln("Trying to parse version for $starterkit_id starterkit.", OutputInterface::VERBOSITY_DEBUG);
    try {
      $starterkit_version = self::getStarterKitVersion(
        $starterkit,
        $io
      );
    }
    catch (\Exception $e) {
      $io->getErrorStyle()->error($e->getMessage());
      return 1;
    }
    $io->writeln("Using version $starterkit_version for $starterkit_id starterkit.", OutputInterface::VERBOSITY_DEBUG);

    $io->writeln("Loading starterkit config from $starterkit_id.starterkit.yml.", OutputInterface::VERBOSITY_DEBUG);
    try {
      $starterkit_config = self::loadStarterKitConfig(
        $starterkit,
        $starterkit_version,
        $theme_label,
        $input->getOption('description')
      );
    }
    catch (\Exception $e) {
      $io->getErrorStyle()->error($e->getMessage());
      return 1;
    }

    $filesystem->mkdir($tmpDir);

    $io->writeln("Copying starterkit to temporary directory for processing.", OutputInterface::VERBOSITY_DEBUG);
    $mirror_iterator = (new Finder)
      ->in($starterkit->getPath())
      ->files()
      ->ignoreDotFiles(FALSE)
      ->notName($starterkit_config['ignore'])
      ->notPath($starterkit_config['ignore']);

    $filesystem->mirror($starterkit->getPath(), $tmpDir, $mirror_iterator);

    $io->writeln("Modifying and renaming files from starterkit.", OutputInterface::VERBOSITY_DEBUG);
    $patterns = [
      'old' => self::namePatterns($starterkit->getName(), $starterkit->info['name']),
      'new' => self::namePatterns($destination_theme, $theme_label),
    ];
    $filesToEdit = self::createFilesFinder($tmpDir)
      ->contains(array_values($patterns['old']))
      ->notPath($starterkit_config['no_edit']);
    foreach ($filesToEdit as $file) {
      $contents = file_get_contents($file->getRealPath());
      $contents = str_replace($patterns['old'], $patterns['new'], $contents);
      file_put_contents($file->getRealPath(), $contents);
    }

    $filesToRename = self::createFilesFinder($tmpDir)
      ->name(array_map(static fn (string $pattern) => "*$pattern*", array_values($patterns['old'])))
      ->notPath($starterkit_config['no_rename']);
    foreach ($filesToRename as $file) {
      $filepath_segments = explode('/', $file->getRealPath());
      $filename = array_pop($filepath_segments);
      $filename = str_replace($patterns['old'], $patterns['new'], $filename);
      $filepath_segments[] = $filename;
      $filesystem->rename($file->getRealPath(), implode('/', $filepath_segments));
    }

    $io->writeln("Updating $destination_theme.info.yml.", OutputInterface::VERBOSITY_DEBUG);
    $info_file = "$tmpDir/$destination_theme.info.yml";
    $info = Yaml::decode(file_get_contents($info_file));
    $info = array_filter(
      array_merge($info, $starterkit_config['info']),
      static fn (mixed $value) => $value !== NULL,
    );
    // Ensure the generated theme is not hidden.
    unset($info['hidden']);
    file_put_contents($info_file, Yaml::encode($info));

    $loader = new ClassLoader();
    $loader->addPsr4("Drupal\\{$starterkit->getName()}\\", "{$starterkit->getPath()}/src");
    $loader->register();

    $generator_classname = "Drupal\\{$starterkit->getName()}\\StarterKit";
    if (class_exists($generator_classname)) {
      if (is_a($generator_classname, StarterKitInterface::class, TRUE)) {
        $io->writeln("Running post processing.", OutputInterface::VERBOSITY_DEBUG);
        $generator_classname::postProcess($tmpDir, $destination_theme, $theme_label);
      }
      else {
        $io->getErrorStyle()->error("The $generator_classname does not implement \Drupal\Core\Theme\StarterKitInterface and cannot perform post-processing.");
        return 1;
      }
    }
    else {
      $io->writeln("Skipping post processing, $generator_classname not defined.", OutputInterface::VERBOSITY_DEBUG);
    }

    // Move altered theme to final destination.
    $io->writeln("Copying $destination_theme to $destination.", OutputInterface::VERBOSITY_DEBUG);
    $filesystem->mirror($tmpDir, $destination);

    $io->writeln(sprintf('Theme generated successfully to %s', $destination));

    return 0;
  }

  /**
   * Generates a path to a temporary location.
   *
   * @return string
   *   A temporary path.
   */
  private function getUniqueTmpDirPath(): string {
    return sys_get_temp_dir() . '/drupal-starterkit-theme-' . uniqid(md5(microtime()), TRUE);
  }

  /**
   * Gets theme info using the theme name.
   *
   * @param string $theme_name
   *   The machine name of the theme.
   *
   * @return \Drupal\Core\Extension\Extension|null
   *   The extension info array. NULL if the theme_name is not discovered.
   */
  private function getThemeInfo(string $theme_name): ? Extension {
    $extension_discovery = new ExtensionDiscovery($this->root, FALSE, []);
    $themes = $extension_discovery->scan('theme');

    $theme = $themes[$theme_name] ?? NULL;
    if ($theme !== NULL) {
      $theme->info = (new InfoParser($this->root))->parse($theme->getPathname());
    }

    return $theme;
  }

  private static function createFilesFinder(string $dir): Finder {
    return (new Finder)->in($dir)->files();
  }

  private static function loadStarterKitConfig(
    Extension $theme,
    string $version,
    string $name,
    string $description,
  ): array {
    $starterkit_config_file = $theme->getPath() . '/' . $theme->getName() . '.starterkit.yml';
    if (!file_exists($starterkit_config_file)) {
      throw new \RuntimeException("Theme source theme {$theme->getName()} is not a valid starter kit.");
    }
    $starterkit_config_defaults = [
      'info' => [
        'name' => $name,
        'description' => $description,
        'core_version_requirement' => '^' . explode('.', \Drupal::VERSION)[0],
        'version' => '1.0.0',
        'generator' => "{$theme->getName()}:$version",
      ],
      'ignore' => [
        '/src/StarterKit.php',
        '/*.starterkit.yml',
      ],
      'no_edit' => [],
      'no_rename' => [],
    ];
    $starterkit_config = Yaml::decode(file_get_contents($starterkit_config_file));
    if (!is_array($starterkit_config)) {
      throw new \RuntimeException('Starterkit config is was not able to be parsed.');
    }
    if (!isset($starterkit_config['info'])) {
      $starterkit_config['info'] = [];
    }
    $starterkit_config['info'] = array_merge($starterkit_config_defaults['info'], $starterkit_config['info']);

    foreach (['ignore', 'no_edit', 'no_rename'] as $key) {
      if (!isset($starterkit_config[$key])) {
        $starterkit_config[$key] = $starterkit_config_defaults[$key];
      }
      if (!is_array($starterkit_config[$key])) {
        throw new \RuntimeException("$key in starterkit.yml must be an array");
      }
      $starterkit_config[$key] = array_map(
        static fn (string $path) => Glob::toRegex(trim($path, '/')),
        $starterkit_config[$key]
      );

      if (count($starterkit_config[$key]) > 0) {
        $files = self::createFilesFinder($theme->getPath())->path($starterkit_config[$key]);
        $starterkit_config[$key] = array_map(static fn ($file) => $file->getRelativePathname(), iterator_to_array($files));
        if (count($starterkit_config[$key]) === 0) {
          throw new \RuntimeException("Paths were defined `$key` but no files found.");
        }
      }
    }

    return $starterkit_config;
  }

  private static function getStarterKitVersion(
    Extension $theme,
    SymfonyStyle $io,
  ): string {
    $source_version = $theme->info['version'] ?? '';
    if ($source_version === '') {
      $confirm = new ConfirmationQuestion(sprintf(
        'The source theme %s does not have a version specified. This makes tracking changes in the source theme difficult. Are you sure you want to continue?',
        $theme->getName()
      ));
      if (!$io->askQuestion($confirm)) {
        throw new \RuntimeException('source version could not be determined');
      }
      $source_version = 'unknown-version';
    }
    if ($source_version === 'VERSION') {
      $source_version = \Drupal::VERSION;
    }

    // A version in the generator string like "9.4.0-dev" is not very helpful.
    // When this occurs, generate a version string that points to a commit.
    if (VersionParser::parseStability($source_version) === 'dev') {
      $git_check = Process::fromShellCommandline('git --help');
      $git_check->run();
      if ($git_check->getExitCode()) {
        throw new \RuntimeException(
          sprintf(
            'The source theme %s has a development version number (%s). Determining a specific commit is not possible because git is not installed. Either install git or use a tagged release to generate a theme.',
            $theme->getName(),
            $source_version
          )
        );
      }

      // Get the git commit for the source theme.
      $git_get_commit = Process::fromShellCommandline("git rev-list --max-count=1 --abbrev-commit HEAD -C {$theme->getPath()}");
      $git_get_commit->run();
      if (!$git_get_commit->isSuccessful() || $git_get_commit->getOutput() === '') {
        $confirm = new ConfirmationQuestion(sprintf(
          'The source theme %s has a development version number (%s). Because it is not a git checkout, a specific commit could not be identified. This makes tracking changes in the source theme difficult. Are you sure you want to continue?',
          $theme->getName(),
          $source_version
        ));
        if (!$io->askQuestion($confirm)) {
          throw new \RuntimeException('source version could not be determined');
        }
        $source_version .= '#unknown-commit';
      }
      else {
        $source_version .= '#' . trim($git_get_commit->getOutput());
      }
    }
    return $source_version;
  }

  private static function namePatterns(string $machine_name, string $label): array {
    return [
      'machine_name' => $machine_name,
      'machine_name_camel' => u($machine_name)->camel(),
      'machine_name_pascal' => u($machine_name)->camel()->title(),
      'machine_name_title' => u($machine_name)->title(),
      'label' => $label,
      'label_camel' => u($label)->camel(),
      'label_pascal' => u($label)->camel()->title(),
      'label_title' => u($label)->title(),
    ];
  }

}
