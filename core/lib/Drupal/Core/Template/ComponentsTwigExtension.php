<?php

namespace Drupal\Core\Template;

use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Render\Component\Exception\InvalidComponentException;
use Drupal\Core\Theme\Component\ComponentValidator;
use Drupal\Core\Theme\ComponentPluginManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The twig extension so Drupal can recognize the new code.
 *
 * @internal
 */
final class ComponentsTwigExtension extends AbstractExtension {

  /**
   * Creates TwigExtension.
   *
   * @param \Drupal\Core\Theme\ComponentPluginManager $pluginManager
   *   The component plugin manager.
   * @param \Drupal\Core\Theme\Component\ComponentValidator $componentValidator
   *   The component validator.
   */
  public function __construct(
    protected ComponentPluginManager $pluginManager,
    protected ComponentValidator $componentValidator,
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
      new TwigFunction('add_component_context', [$this, 'addAdditionalContext'], ['needs_context' => TRUE]),
      new TwigFunction('validate_component_props', [$this, 'validateProps'], ['needs_context' => TRUE]),
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
   * @throws \Drupal\Core\Render\Component\Exception\ComponentNotFoundException
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
   * @param \Drupal\Core\Plugin\Component $component
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
    if (!empty($context['variant'])) {
      $component_attributes['data-component-variant'] = $context['variant'];
    }
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
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
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
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
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
