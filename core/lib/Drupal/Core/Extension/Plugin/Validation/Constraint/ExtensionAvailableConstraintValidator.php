<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Component\FileSystem\FileSystem as DrupalFilesystem;
use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a given extension exists.
 */
class ExtensionAvailableConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Extension discovery objects for when the profile has changed.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery[]
   */
  protected array $extensionDiscovery;

  /**
   * Indicates if the application is running in a test environment.
   */
  protected static ?bool $inTestEnvironment;

  /**
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   *   The profile extension list.
   * @param string $appRoot
   *   The app root.
   * @param string|false|null $installProfile
   *   The install profile used by the environment.
   * @param string $sitePath
   *   The site path.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
    protected ThemeExtensionList $themeExtensionList,
    protected ProfileExtensionList $profileExtensionList,
    protected string $appRoot,
    protected $installProfile,
    protected string $sitePath,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
      $container->get('extension.list.profile'),
      $container->getParameter('app.root'),
      $container->getParameter('install_profile'),
      $container->getParameter('site.path'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $extension_name, Constraint $constraint): void {
    assert($constraint instanceof ExtensionAvailableConstraint);

    // This constraint may be used to validate nullable (optional) values.
    if ($extension_name === NULL) {
      return;
    }

    $variables = ['@name' => $extension_name];

    $data = $this->context->getObject();
    assert($data instanceof TypedDataInterface);
    $validation_profile = $this->resolveProfile($data);
    $extension_discovery = NULL;

    // If the profile is changing, the list of available extensions can change.
    // The ExtensionDiscovery object is used directly instead of ExtensionList
    // objects to avoid polluting caches and state.
    // @see \Drupal\Core\Extension\ExtensionDiscovery
    // @see \Drupal\Core\Extension\InstallProfileUninstallValidator
    $installProfile = $this->installProfile;
    if ($validation_profile !== $installProfile) {
      try {
        $extension_discovery = $this->getExtensionDiscovery($validation_profile);
      }
      catch (UnknownExtensionException) {
        // The new profile passed is not available. If we are validating a
        // profile, show the profile missing message.
        if ($constraint->type === 'profile') {
          $this->context->addViolation($constraint->profileNotExistsMessage, $variables);
          return;
        }

        // If we are validating a module or theme, show we cannot load the
        // profile to check if the module or theme is available.
        $params = [
          '@profile' => $validation_profile,
          '@extension' => $extension_name,
        ];
        $this->context->addViolation($constraint->couldNotLoadProfileToCheckExtension, $params);
        return;
      }
    }

    switch ($constraint->type) {
      case 'module':
        // Some plugins are shipped in `core/lib`, which corresponds to the
        // special `core` extension name.
        // For example: \Drupal\Core\Menu\Plugin\Block\LocalActionsBlock.
        if ($extension_name === 'core') {
          return;
        }

        // If a profile is set, that profile is also available as a module.
        if ($extension_name === $validation_profile) {
          return;
        }

        // Intentionally fall through to the next cases.

      case 'theme':
      case 'profile':
        if (!$this->extensionExists($constraint->type, $extension_name, $extension_discovery)) {
          $message = $constraint->type . 'NotExistsMessage';
          assert(property_exists($constraint, $message));
          $this->context->addViolation($constraint->$message, $variables);
        }

        break;

      default:
        throw new \InvalidArgumentException("Unknown extension type: '$constraint->type'");
    }
  }

  /**
   * Determines if an extension exists.
   *
   * @param string $type
   *   The extension type.
   * @param string $name
   *   The extension name.
   * @param \Drupal\Core\Extension\ExtensionDiscovery|null $discovery
   *   The discovery service to use if set.
   *
   * @return bool
   *   TRUE if the extension exists, FALSE if not.
   */
  protected function extensionExists(string $type, string $name, ?ExtensionDiscovery $discovery): bool {
    if ($discovery) {
      return array_key_exists($name, $discovery->scan($type, static::insideTest()));
    }
    $list = $type . 'ExtensionList';
    assert($this->$list instanceof ExtensionList);
    return $this->$list->exists($name);
  }

  /**
   * Gets an extension discovery object for the given profile.
   *
   * @return \Drupal\Core\Extension\ExtensionDiscovery
   *   An extension discovery object to look for extensions.
   */
  protected function getExtensionDiscovery(?string $profile = NULL): ExtensionDiscovery {
    // cspell:ignore CNKDSIUSYFUISEFCB
    $profile = $profile ?? '_does_not_exist_profile_CNKDSIUSYFUISEFCB';
    if (!isset($this->extensionDiscovery) || !isset($this->extensionDiscovery[$profile])) {
      // When inside a testing environment, we allow all extensions to be
      // available to simplify testing distributions.
      $profileDirectories = static::insideTest() ? [] : [$this->profileExtensionList->getPath($profile)];
      $this->extensionDiscovery[$profile] = new ExtensionDiscovery($this->appRoot, TRUE, $profileDirectories);
    }
    return $this->extensionDiscovery[$profile];
  }

  /**
   * Resolve the profile based on the given typed data.
   *
   * Since the profile might have changed while validating, we need to resolve
   * the profile based on the current typed data. This allows us to scan for
   * extensions in the correct profile directory.
   *
   * @return string|false|null
   *   The name of the active install profile or distribution, FALSE if there is
   *    no install profile or NULL if Drupal is being installed.
   */
  public function resolveProfile(TypedDataInterface $data): mixed {
    if ($data->getParent()?->getName() === 'core.extension') {
      $current_profile = $this->installProfile;
      $parent_expression = '%parent.profile';
      $profile = TypeResolver::resolveExpression($parent_expression, $data);
      if ($profile !== $parent_expression && $profile !== $current_profile) {
        return $profile;
      }
    }
    return $this->installProfile;
  }

  /**
   * Whether this validator is running inside a test.
   *
   * @return bool
   *   TRUE if the validator is running in a test. FALSE otherwise.
   */
  protected static function insideTest(): bool {
    if (isset(static::$inTestEnvironment)) {
      return static::$inTestEnvironment;
    }

    // @see \Drupal\Core\CoreServiceProvider::registerTest()
    $in_functional_test = drupal_valid_test_ua();
    // @see \Drupal\Core\DependencyInjection\DependencySerializationTrait::__wakeup()
    $in_kernel_test = isset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
    // @see \Drupal\BuildTests\Framework\BuildTestBase::setUp()
    $in_build_test = str_contains(__FILE__, DrupalFilesystem::getOsTemporaryDirectory() . '/build_workspace_');
    static::$inTestEnvironment = $in_functional_test || $in_kernel_test || $in_build_test;
    return static::$inTestEnvironment;
  }

}
