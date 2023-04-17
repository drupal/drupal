<?php

declare(strict_types=1);

namespace Drupal\sdc\Twig;

use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\sdc\Exception\InvalidComponentException;
use Drupal\sdc\Plugin\Component;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Provides a ComponentNodeVisitor to change the generated parse-tree.
 *
 * @internal
 */
final class ComponentNodeVisitor implements NodeVisitorInterface {

  /**
   * Creates a new ComponentNodeVisitor object.
   *
   * @param \Drupal\sdc\ComponentPluginManager $pluginManager
   *   The plugin manager for components.
   */
  public function __construct(protected ComponentPluginManager $pluginManager) {}

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node, Environment $env): Node {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function leaveNode(Node $node, Environment $env): ?Node {
    if (!$node instanceof ModuleNode) {
      return $node;
    }
    $component = $this->getComponent($node);
    if (!$component) {
      return $node;
    }
    $line = $node->getTemplateLine();
    $print_nodes = [];
    $component_id = $component->getPluginId();
    $emoji = static::emojiForString($component_id);
    if ($env->isDebug()) {
      $print_nodes[] = new PrintNode(new ConstantExpression(sprintf('<!-- %s Component start: %s -->', $emoji, $component_id), $line), $line);
    }
    $print_nodes[] = new PrintNode(new FunctionExpression(
      'attach_library',
      new Node([new ConstantExpression($component->getLibraryName(), $line)]),
      $line
    ), $line);
    $print_nodes[] = new PrintNode(new FunctionExpression(
      'sdc_additional_context',
      new Node([new ConstantExpression($component_id, $line)]),
      $line
    ), $line);
    $print_nodes[] = new PrintNode(new FunctionExpression(
      'sdc_validate_props',
      new Node([new ConstantExpression($component_id, $line)]),
      $line
    ), $line);
    foreach ($print_nodes as $index => $print_node) {
      $node->getNode('display_start')->setNode((string) $index, $print_node);
    }
    if ($env->isDebug()) {
      $node->getNode('display_end')
        ->setNode(
          '0',
          new PrintNode(new ConstantExpression(sprintf('<!-- %s Component end: %s -->', $emoji, $component_id), $line), $line)
        );
    }
    // Slots can be validated at compile time, we don't need to add nodes to
    // execute functions during display with the actual data.
    $this->validateSlots($component, $node->getNode('blocks'));
    return $node;
  }

  /**
   * Finds the SDC for the current module node.
   *
   * @param \Twig\Node\Node $node
   *   The node.
   *
   * @return \Drupal\sdc\Plugin\Component|null
   *   The component, if any.
   */
  protected function getComponent(Node $node): ?Component {
    $component_id = $node->getTemplateName();
    if (!preg_match('/^[a-z]([a-zA-Z0-9_-]*[a-zA-Z0-9])*:[a-z]([a-zA-Z0-9_-]*[a-zA-Z0-9])*$/', $component_id)) {
      return NULL;
    }
    try {
      return $this->pluginManager->find($component_id);
    }
    catch (ComponentNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 250;
  }

  /**
   * Performs a cheap validation of the slots in the template.
   *
   * It validates them against the JSON Schema provided in the component
   * definition file and massaged in the ComponentMetadata class. We don't use
   * the JSON Schema validator because we just want to validate required and
   * undeclared slots. This cheap validation lets us validate during runtime
   * even in production.
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   *   When the slots don't pass validation.
   */
  protected function validateSlots(Component $component, Node $node): void {
    $metadata = $component->metadata;
    if (!$metadata->mandatorySchemas) {
      return;
    }
    $slot_definitions = $metadata->slots;
    $ids_available = array_keys($slot_definitions);
    $undocumented_ids = [];
    try {
      $it = $node->getIterator();
    }
    catch (\Exception $e) {
      return;
    }
    if ($it instanceof \SeekableIterator) {
      while ($it->valid()) {
        $provided_id = $it->key();
        if (!in_array($provided_id, $ids_available, TRUE)) {
          $undocumented_ids[] = $provided_id;
        }
        $it->next();
      }
    }
    // Now build the error message.
    $error_messages = [];
    if (!empty($undocumented_ids)) {
      $error_messages[] = sprintf(
        'We found an unexpected slot that is not declared: [%s]. Please declare them in "%s.component.yml".',
        implode(', ', $undocumented_ids),
        $component->machineName
      );
    }
    if (!empty($error_messages)) {
      $message = implode("\n", $error_messages);
      throw new InvalidComponentException($message);
    }
  }

  /**
   * Chooses an emoji representative for the input string.
   *
   * @param string $input
   *   The input string.
   *
   * @return string
   *   The emoji code.
   */
  protected static function emojiForString(string $input): string {
    // Compute a cheap and reproducible float between 0 and 1 for based on the
    // component ID.
    $max_length = 40;
    $input = strtolower($input);
    $input = strtr($input, '-_:', '000');
    $input = substr($input, 0, $max_length);
    $chars = str_split($input);
    $chars = array_pad($chars, 20, '0');
    $sum = array_reduce($chars, static fn(int $total, string $char) => $total + ord($char), 0);
    $num = $sum / 4880;

    // Compute an int between 129338 and 129431, which is the sequential emoji
    // range we are interested in. We chose this range because all integers in
    // between correspond to an emoji. These emojis depict sports, food, and
    // animals.
    $html_entity = floor(129338 + $num * (129431 - 129338));
    $emoji = mb_convert_encoding("&#$html_entity;", 'UTF-8', 'HTML-ENTITIES');
    return is_string($emoji) ? $emoji : '';
  }

}
