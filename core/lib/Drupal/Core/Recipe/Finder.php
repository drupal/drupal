<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Composer\InstalledVersions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;

/**
 * Discovers recipes at specific locations in the file system.
 *
 * By default, this will scan core's recipes, along with the directory where
 * Composer is configured to install recipes. Alternatively, it can scan
 * directories of your choosing. It won't recurse into the file system, which is
 * an intentional difference from ExtensionDiscovery, because all recipes
 * (except core's) must be installed in a single location.
 *
 * Once instantiated, this object should be used as a simple iterator over
 * \Drupal\Core\Recipe\Recipe objects. For example:
 *
 * @code
 * $discovery = new Finder('/path/to/recipes');
 * foreach ($discovery as $recipe) {
 *   echo $recipe->name;
 * }
 * @endcode
 *
 * @see \Drupal\Core\Recipe\RecipeConfigurator
 *
 * @implements \IteratorAggregate<\Drupal\Core\Recipe\Recipe>
 *
 * @internal
 *   This API is experimental.
 */
final class Finder extends SymfonyFinder {

  /**
   * The location where Composer is configured to install recipes.
   */
  private static ?string $recipesDirectory = NULL;

  /**
   * Constructs a recipe finder.
   *
   * @param string|array|null $directories
   *   (optional) The directories that should be searched for recipes, or NULL
   *   to use the default locations.
   * @param bool $skipInvalid
   *   (optional) Whether to silently skip invalid recipes, or allow them to
   *   throw exceptions.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   (optional) A logger where exceptions raised by invalid recipes should be
   *   logged.
   */
  public function __construct(
    string|array|null $directories = NULL,
    private readonly bool $skipInvalid = FALSE,
    public readonly ?LoggerInterface $logger = NULL,
  ) {
    parent::__construct();

    $directories ??= [
      // Placeholders used by the Composer installer don't make sense here.
      // For example, if Composer is configured to install recipes in
      // `./recipes/{$name}`, we really just want to scan `./recipes`.
      str_replace(['{$vendor}', '{$name}'], '', self::getComposerRecipesDirectory()),
      // Drupal runs in the web root, so this directory is guaranteed to exist
      // (and Finder will throw if it doesn't).
      'core/recipes',
    ];
    $this->in($directories)->followLinks();
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Generator {
    // Set options that should never be configurable by calling code, so that
    // we can guarantee they are what we expect them to be before iterating.
    // Calling name() and depth() on $this is forbidden, so we need to set those
    // options on the parent class.
    $this->files();
    parent::name('recipe.yml');
    parent::depth(1);

    foreach (parent::getIterator() as $file) {
      try {
        yield Recipe::createFromDirectory($file->getPath());
      }
      catch (\Throwable $e) {
        $this->logger?->error($e->getMessage());
        if ($this->skipInvalid) {
          continue;
        }
        throw $e;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function directories(): never {
    throw new \LogicException('Recipe discovery can only scan for files.');
  }

  /**
   * {@inheritdoc}
   */
  public function depth(mixed $levels): never {
    throw new \LogicException('Recipe discovery cannot recurse into the file system.');
  }

  /**
   * {@inheritdoc}
   */
  public function name(mixed $patterns): never {
    throw new \LogicException('Recipe discovery can only scan for files named `recipe.yml`.');
  }

  /**
   * Returns the directory where recipes are installed.
   *
   * Placeholder tokens, like {$name} and {$vendor}, are left as-is.
   *
   * @param string|null $project_root
   *   (optional) The root of the project, where the top-level `composer.json`
   *   is. Auto-detected by default.
   *
   * @return string
   *   The path where Composer has installed recipes.
   *
   * @throws \RuntimeException
   *   Thrown if the install directory for recipes cannot be determined.
   */
  public static function getComposerRecipesDirectory(?string $project_root = NULL): string {
    // This is expensive to compute, and very unlikely to change in a request.
    if (isset(self::$recipesDirectory)) {
      return self::$recipesDirectory;
    }

    if (empty($project_root)) {
      ['install_path' => $project_root] = InstalledVersions::getRootPackage();
      $project_root = realpath($project_root);
    }
    assert(is_string($project_root));

    $file = $project_root . DIRECTORY_SEPARATOR . 'composer.json';
    $data = (string) file_get_contents($file);
    $data = json_decode($data, TRUE, flags: JSON_THROW_ON_ERROR);

    $directory = array_find_key(
      $data['extra']['installer-paths'] ?? [],
      fn (array $criteria): bool => in_array('type:' . Recipe::COMPOSER_PROJECT_TYPE, $criteria, TRUE),
    );
    if ($directory) {
      return self::$recipesDirectory = $project_root . DIRECTORY_SEPARATOR . $directory;
    }
    throw new \RuntimeException("No recipe install location is configured in $file.");
  }

}
