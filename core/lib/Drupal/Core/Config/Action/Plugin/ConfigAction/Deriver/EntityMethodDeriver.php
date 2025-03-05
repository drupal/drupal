<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver;

// cspell:ignore inflector
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Action\EntityMethodException;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Derives config action methods from attributed config entity methods.
 *
 * @internal
 *   This API is experimental.
 */
final class EntityMethodDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Inflector to pluralize words.
   */
  protected readonly InflectorInterface $inflector;

  /**
   * Constructs new EntityMethodDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected readonly EntityTypeManagerInterface $entityTypeManager) {
    $this->inflector = new EnglishInflector();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Scan all the config entity classes for attributes.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ConfigEntityTypeInterface) {
        $reflectionClass = new \ReflectionClass($entity_type->getClass());
        while ($reflectionClass) {
          foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Only process a method if it is declared on the current class.
            // Methods on the parent class will be processed later. This allows
            // for a parent to have an attribute and an overriding class does
            // not need one. For example,
            // \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay::setComponent()
            // and \Drupal\Core\Entity\EntityDisplayBase::setComponent().
            if ($method->getDeclaringClass()->getName() === $reflectionClass->getName()) {
              foreach ($method->getAttributes(ActionMethod::class) as $attribute) {
                $this->processMethod($method, $attribute->newInstance(), $entity_type, $base_plugin_definition);
              }
            }
          }
          $reflectionClass = $reflectionClass->getParentClass();
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Processes a method to create derivatives.
   *
   * @param \ReflectionMethod $method
   *   The entity method.
   * @param \Drupal\Core\Config\Action\Attribute\ActionMethod $action_attribute
   *   The entity method attribute.
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $derivative
   *   The base plugin definition that will used to create the derivative.
   */
  private function processMethod(\ReflectionMethod $method, ActionMethod $action_attribute, ConfigEntityTypeInterface $entity_type, array $derivative): void {
    $derivative['admin_label'] = $action_attribute->adminLabel ?: $this->t('@entity_type @method', [
      '@entity_type' => $entity_type->getLabel(),
      '@method' => $method->name,
    ]);
    $derivative['constructor_args'] = [
      'method' => $method->name,
      'exists' => $action_attribute->exists,
      'numberOfParams' => $method->getNumberOfParameters(),
      'numberOfRequiredParams' => $method->getNumberOfRequiredParameters(),
      'pluralized' => FALSE,
    ];
    $derivative['entity_types'] = [$entity_type->id()];
    $action_name = $action_attribute->name ?: $method->name;
    // Build a config action identifier from the entity type's config
    // prefix  and the method name. For example, the Role entity adds a
    // 'user.role:grantPermission' action.
    $this->addDerivative($action_name, $entity_type, $derivative, $method->name);

    $pluralized_name = match(TRUE) {
      is_string($action_attribute->pluralize) => $action_attribute->pluralize,
      $action_attribute->pluralize === FALSE => '',
      default => $this->inflector->pluralize($action_name)[0]
    };
    // Add a pluralized version of the plugin.
    if (strlen($pluralized_name) > 0) {
      $derivative['constructor_args']['pluralized'] = TRUE;
      $derivative['admin_label'] = $this->t('@admin_label (multiple calls)', ['@admin_label' => $derivative['admin_label']]);
      $this->addDerivative($pluralized_name, $entity_type, $derivative, $method->name);
    }
  }

  /**
   * Adds a derivative.
   *
   * @param string $action_id
   *   The action ID.
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $derivative
   *   The derivative definition.
   * @param string $methodName
   *   The method name.
   */
  private function addDerivative(string $action_id, ConfigEntityTypeInterface $entity_type, array $derivative, string $methodName): void {
    $id = $entity_type->getConfigPrefix() . PluginBase::DERIVATIVE_SEPARATOR . $action_id;
    if (isset($this->derivatives[$id])) {
      throw new EntityMethodException(sprintf('Duplicate action can not be created for ID \'%s\' for %s::%s(). The existing action is for the ::%s() method', $id, $entity_type->getClass(), $methodName, $this->derivatives[$id]['constructor_args']['method']));
    }
    $this->derivatives[$id] = $derivative;
  }

}
