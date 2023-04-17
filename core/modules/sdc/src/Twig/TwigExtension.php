<?php

namespace Drupal\sdc\Twig;

use Drupal\Core\Template\Attribute;
use Drupal\sdc\Component\ComponentValidator;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\sdc\Exception\InvalidComponentException;
use Drupal\sdc\Plugin\Component;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The twig extension so Drupal can recognize the new code.
 *
 * @internal
 */
final class TwigExtension extends AbstractExtension {

  /**
   * Creates TwigExtension.
   *
   * @param \Drupal\sdc\ComponentPluginManager $pluginManager
   *   The component plugin manager.
   * @param \Drupal\sdc\Component\ComponentValidator $componentValidator
   *   The component validator.
   */
  public function __construct(
    protected ComponentPluginManager $pluginManager,
    protected ComponentValidator $componentValidator
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors(): array {
    return [new ComponentNodeVisitor($this->pluginManager)];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction(
        'sdc_additional_context',
        [$this, 'addAdditionalContext'],
        ['needs_context' => TRUE]
      ),
      new TwigFunction(
        'sdc_validate_props',
        [$this, 'validateProps'],
        ['needs_context' => TRUE]
      ),
    ];
  }

  /**
   * Appends additional context to the template based on the template id.
   *
   * @param array &$context
   *   The context.
   * @param string $component_id
   *   The component ID.
   *
   * @throws \Drupal\sdc\Exception\ComponentNotFoundException
   */
  public function addAdditionalContext(array &$context, string $component_id): void {
    $context = $this->mergeAdditionalRenderContext(
      $this->pluginManager->find($component_id),
      $context
    );
  }

  /**
   * Calculates additional context for this template.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param array $context
   *   The context to update.
   *
   * @return array
   *   The additional context to inject to component templates.
   */
  protected function mergeAdditionalRenderContext(Component $component, array $context): array {
    $context['componentMetadata'] = $component->metadata->normalize();
    $component_attributes = ['data-component-id' => $component->getPluginId()];
    if (!isset($context['attributes'])) {
      $context['attributes'] = new Attribute($component_attributes);
    }
    // If there is an "attributes" property, merge the additional attributes
    // into it if possible.
    elseif ($context['attributes'] instanceof Attribute) {
      $context['attributes']->merge(new Attribute($component_attributes));
    }
    return $context;
  }

  /**
   * Validates the props in development environments.
   *
   * @param array $context
   *   The context provided to the component.
   * @param string $component_id
   *   The component ID.
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   */
  public function validateProps(array &$context, string $component_id): void {
    assert($this->doValidateProps($context, $component_id));
  }

  /**
   * Performs the actual validation of the schema for the props.
   *
   * @param array $context
   *   The context provided to the component.
   * @param string $component_id
   *   The component ID.
   *
   * @return bool
   *   TRUE if it's valid.
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   */
  protected function doValidateProps(array $context, string $component_id): bool {
    try {
      return $this->componentValidator->validateProps(
        $context,
        $this->pluginManager->find($component_id)
      );
    }
    catch (ComponentNotFoundException $e) {
      throw new InvalidComponentException($e->getMessage(), $e->getCode(), $e);
    }
  }

}
