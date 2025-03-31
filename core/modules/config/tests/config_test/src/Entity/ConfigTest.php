<?php

declare(strict_types=1);

namespace Drupal\config_test\Entity;

use Drupal\config_test\ConfigTestAccessControlHandler;
use Drupal\config_test\ConfigTestForm;
use Drupal\config_test\ConfigTestInterface;
use Drupal\config_test\ConfigTestListBuilder;
use Drupal\config_test\ConfigTestStorage;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the ConfigTest configuration entity.
 */
#[ConfigEntityType(
  id: 'config_test',
  label: new TranslatableMarkup('Test configuration'),
  config_prefix: 'dynamic',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'status' => 'status',
  ],
  handlers: [
    'storage' => ConfigTestStorage::class,
    'list_builder' => ConfigTestListBuilder::class,
    'form' => [
      'default' => ConfigTestForm::class,
      'delete' => EntityDeleteForm::class,
    ],
    'access' => ConfigTestAccessControlHandler::class,
  ],
  links: [
    'edit-form' => '/admin/structure/config_test/manage/{config_test}',
    'delete-form' => '/admin/structure/config_test/manage/{config_test}/delete',
    'enable' => '/admin/structure/config_test/manage/{config_test}/enable',
    'disable' => '/admin/structure/config_test/manage/{config_test}/disable',
    'collection' => '/admin/structure/config_test',
  ],
  config_export: [
    'id',
    'label',
    'weight',
    'style',
    'size',
    'size_value',
    'protected_property',
    'array_property',
  ],
  )]
class ConfigTest extends ConfigEntityBase implements ConfigTestInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of the configuration entity.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The image style to use.
   *
   * @var string
   */
  public $style;

  /**
   * A protected property of the configuration entity.
   *
   * @var string
   */
  protected $protected_property;

  /**
   * An array property of the configuration entity.
   *
   * @var array
   */
  protected array $array_property = [];

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    \Drupal::state()->set('config_entity_sort', TRUE);
    return parent::sort($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Used to test secondary writes during config sync.
    if ($this->id() == 'primary') {
      $secondary = $storage->create([
        'id' => 'secondary',
        'label' => 'Secondary Default',
      ]);
      $secondary->save();
    }
    if ($this->id() == 'dependency') {
      $dependent = $storage->load('dependent');
      $dependent->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $entity) {
      if ($entity->id() == 'dependency') {
        $dependent = $storage->load('dependent');
        $dependent->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if ($module = \Drupal::state()->get('config_test_new_dependency', FALSE)) {
      $this->addDependency('module', $module);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // Record which entities have this method called on and what dependencies
    // are passed.
    $called = \Drupal::state()->get('config_test.on_dependency_removal_called', []);
    $called[$this->id()] = $dependencies;
    $called[$this->id()]['config'] = array_keys($called[$this->id()]['config']);
    $called[$this->id()]['content'] = array_keys($called[$this->id()]['content']);
    \Drupal::state()->set('config_test.on_dependency_removal_called', $called);

    $changed = parent::onDependencyRemoval($dependencies);
    if (!isset($this->dependencies['enforced']['config'])) {
      return $changed;
    }
    $fix_deps = \Drupal::state()->get('config_test.fix_dependencies', []);
    foreach ($dependencies['config'] as $entity) {
      if (in_array($entity->getConfigDependencyName(), $fix_deps)) {
        $key = array_search($entity->getConfigDependencyName(), $this->dependencies['enforced']['config']);
        if ($key !== FALSE) {
          $changed = TRUE;
          unset($this->dependencies['enforced']['config'][$key]);
        }
      }
    }
    // If any of the dependencies removed still exists, return FALSE.
    if (array_intersect_key(array_flip($this->dependencies['enforced']['config']), $dependencies['config'])) {
      return FALSE;
    }
    return $changed;
  }

  /**
   * Sets the enforced dependencies.
   *
   * @param array $dependencies
   *   A config dependency array.
   *
   * @return $this
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  public function setEnforcedDependencies(array $dependencies) {
    $this->dependencies['enforced'] = $dependencies;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isInstallable() {
    return $this->id != 'is_installable' || \Drupal::state()->get('config_test.is_installable');
  }

  /**
   * Sets the protected property value.
   *
   * @param string $value
   *   The value to set.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod(pluralize: FALSE)]
  public function setProtectedProperty(string $value): static {
    $this->protected_property = $value;
    return $this;
  }

  /**
   * Gets the protected property value.
   *
   * @return string
   *   The protected property value.
   */
  public function getProtectedProperty(): string {
    return $this->protected_property;
  }

  /**
   * Concatenates the two params and sets the protected property value.
   *
   * @param string $value1
   *   The first value to concatenate.
   * @param string $value2
   *   The second value to concatenate.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod()]
  public function concatProtectedProperty(string $value1, string $value2): static {
    $this->protected_property = $value1 . $value2;
    return $this;
  }

  /**
   * Concatenates up to two params and sets the protected property value.
   *
   * @param string $value1
   *   The first value to concatenate.
   * @param string $value2
   *   (optional) The second value to concatenate. Defaults to ''.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod(pluralize: FALSE)]
  public function concatProtectedPropertyOptional(string $value1, string $value2 = ''): static {
    $this->protected_property = $value1 . $value2;
    return $this;
  }

  /**
   * Appends to protected property.
   *
   * @param mixed $value
   *   The value to append.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod()]
  public function append(string $value): static {
    $this->protected_property .= $value;
    return $this;
  }

  /**
   * Sets the protected property to a default value.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod(pluralize: FALSE, adminLabel: new TranslatableMarkup('Set default name'))]
  public function defaultProtectedProperty(): static {
    $this->protected_property = 'Set by method';
    return $this;
  }

  /**
   * Adds a value to the array property.
   *
   * @param mixed $value
   *   The value to add.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod(pluralize: 'addToArrayMultipleTimes')]
  public function addToArray(mixed $value): static {
    $this->array_property[] = $value;
    return $this;
  }

  /**
   * Gets the array property value.
   *
   * @return array
   *   The array property value.
   */
  public function getArrayProperty(): array {
    return $this->array_property;
  }

  /**
   * Sets the array property.
   *
   * @param array $value
   *   The value to set.
   *
   * @return $this
   *   The config entity.
   */
  #[ActionMethod(pluralize: FALSE)]
  public function setArray(array $value): static {
    $this->array_property = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    // Only export the 'array_property' is there is data.
    if (empty($properties['array_property'])) {
      unset($properties['array_property']);
    }
    return $properties;
  }

}
