<?php

namespace Drupal\Core\TypedData;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\Exception\ContextNotFoundException;


class TypedDataResolver {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $manager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $manager
   *   The typed data manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(TypedDataManagerInterface $manager, TranslationInterface $translation) {
    $this->manager = $manager;
    $this->translation = $translation;
  }

  /**
   * Convert a property to a context.
   *
   * This method will respect the value of contexts as well, so if a context
   * object is pass that contains a value, the appropriate value will be
   * extracted and injected into the resulting context object if available.
   *
   * @param string $property_path
   *   The name of the property.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context from which we will extract values if available.
   *
   * @return \Drupal\Core\Plugin\Context\Context
   *   A context object that represents the definition & value of the property.
   *
   * @throws \Exception
   */
  public function getContextFromProperty($property_path, ContextInterface $context) {
    $value = NULL;
    $data_definition = NULL;
    if ($context->hasContextValue()) {
      /** @var \Drupal\Core\TypedData\ComplexDataInterface $data */
      $data = $context->getContextData();
      foreach (explode(':', $property_path) as $name) {

        if ($data instanceof ListInterface) {
          if (!is_numeric($name)) {
            // Implicitly default to delta 0 for lists when not specified.
            $data = $data->first();
          }
          else {
            // If we have a delta, fetch it and continue with the next part.
            $data = $data->get($name);
            continue;
          }
        }

        // Forward to the target value if this is a data reference.
        if ($data instanceof DataReferenceInterface) {
          $data = $data->getTarget();
        }

        if (!$data->getDataDefinition()->getPropertyDefinition($name)) {
          throw new \Exception("Unknown property $name in property path $property_path");
        }
        $data = $data->get($name);
      }

      $value = $data->getValue();
      $data_definition = $data instanceof DataReferenceInterface ? $data->getDataDefinition()->getTargetDefinition() : $data->getDataDefinition();
    }
    else {
      /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $data_definition */
      $data_definition = $context->getContextDefinition()->getDataDefinition();
      foreach (explode(':', $property_path) as $name) {

        if ($data_definition instanceof ListDataDefinitionInterface) {
          $data_definition = $data_definition->getItemDefinition();

          // If the delta was specified explicitly, continue with the next part.
          if (is_numeric($name)) {
            continue;
          }
        }

        // Forward to the target definition if this is a data reference
        // definition.
        if ($data_definition instanceof DataReferenceDefinitionInterface) {
          $data_definition = $data_definition->getTargetDefinition();
        }

        if (!$data_definition->getPropertyDefinition($name)) {
          throw new \Exception("Unknown property $name in property path $property_path");
        }
        $data_definition = $data_definition->getPropertyDefinition($name);
      }

      // Forward to the target definition if this is a data reference
      // definition.
      if ($data_definition instanceof DataReferenceDefinitionInterface) {
        $data_definition = $data_definition->getTargetDefinition();
      }
    }
    if (strpos($data_definition->getDataType(), 'entity:') === 0) {
      $context_definition = new EntityContextDefinition($data_definition->getDataType(), $data_definition->getLabel(), $data_definition->isRequired(), FALSE, $data_definition->getDescription());
    }
    else {
      $context_definition = new ContextDefinition($data_definition->getDataType(), $data_definition->getLabel(), $data_definition->isRequired(), FALSE, $data_definition->getDescription());
    }
    return new Context($context_definition, $value);
  }

  /**
   * Extracts a context from an array of contexts by a tokenized pattern.
   *
   * This is more than simple isset/empty checks on the contexts array. The
   * pattern could be node:uid:name which will iterate over all provided
   * contexts in the array for one named 'node', it will then load the data
   * definition of 'node' and check for a property named 'uid'. This will then
   * set a new (temporary) context on the array and recursively call itself to
   * navigate through related properties all the way down until the request
   * property is located. At that point the property is passed to a
   * TypedDataResolver which will convert it to an appropriate ContextInterface
   * object.
   *
   * @param $token
   *   A ":" delimited set of tokens representing
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of available contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The requested token as a full Context object.
   *
   * @throws \Drupal\Core\TypedData\Exception\ContextNotFoundException
   */
  public function convertTokenToContext($token, $contexts) {
    // If the requested token is already a context, just return it.
    if (isset($contexts[$token])) {
      return $contexts[$token];
    }
    else {
      [$base, $property_path] = explode(':', $token, 2);
      // A base must always be set. This method recursively calls itself
      // setting bases for this reason.
      if (!empty($contexts[$base])) {
        return $this->getContextFromProperty($property_path, $contexts[$base]);
      }
      // @todo improve this exception message.
      throw new ContextNotFoundException("The requested context was not found in the supplied array of contexts.");
    }
  }

  /**
   * Provides an administrative label for a tokenized relationship.
   *
   * @param string $token
   *   The token related to a context in the contexts array.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts from which to extract our token's label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The administrative label of $token.
   */
  public function getLabelByToken($token, $contexts) {
    // @todo Optimize this by allowing to limit the desired token?
    $tokens = $this->getTokensForContexts($contexts);
    if (isset($tokens[$token])) {
      return $tokens[$token];
    }
  }

  /**
   * Extracts an array of tokens and labels.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of contexts with which we are currently dealing.
   *
   * @return array
   *   An array of token keys and corresponding labels.
   */
  public function getTokensForContexts($contexts) {
    $tokens = [];
    foreach ($contexts as $context_id => $context) {
      $data_definition = $context->getContextDefinition()->getDataDefinition();
      if ($data_definition instanceof ComplexDataDefinitionInterface) {
        foreach ($this->getTokensFromComplexData($data_definition) as $token => $label) {
          $tokens["$context_id:$token"] = $data_definition->getLabel() . ': ' . $label;
        }
      }
    }
    return $tokens;
  }

  /**
   * Returns tokens for a complex data definition.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface $complex_data_definition
   *
   * @return array
   *   An array of token keys and corresponding labels.
   */
  protected function getTokensFromComplexData(ComplexDataDefinitionInterface $complex_data_definition) {
    $tokens = [];
    // Loop over all properties.
    foreach ($complex_data_definition->getPropertyDefinitions() as $property_name => $property_definition) {

      // Item definitions do not always have a label. Use the list definition
      // label if the item does not have one.
      $property_label = $property_definition->getLabel();
      if ($property_definition instanceof ListDataDefinitionInterface) {
        $property_definition = $property_definition->getItemDefinition();
        $property_label = $property_definition->getLabel() ?: $property_label;
      }

      // If the property is complex too, recurse to find child properties.
      if ($property_definition instanceof ComplexDataDefinitionInterface) {
        $property_tokens = $this->getTokensFromComplexData($property_definition);
        foreach ($property_tokens as $token => $label) {
          $tokens[$property_name . ':' . $token] = count($property_tokens) > 1 ? ($property_label . ': ' . $label) : $property_label;
        }
      }

      // Only expose references as tokens.
      // @todo Consider to expose primitive and non-reference typed data
      //   definitions too, like strings, integers and dates. The current UI
      //   will not scale to that.
      if ($property_definition instanceof DataReferenceDefinitionInterface) {
        $tokens[$property_name] = $property_definition->getLabel();
      }
    }
    return $tokens;
  }

}
