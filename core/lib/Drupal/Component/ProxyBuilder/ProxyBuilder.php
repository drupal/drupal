<?php

namespace Drupal\Component\ProxyBuilder;

/**
 * Generates the string representation of the proxy service.
 */
class ProxyBuilder {

  /**
   * Generates the used proxy class name from a given class name.
   *
   * @param string $class_name
   *   The class name of the actual service.
   *
   * @return string
   *   The class name of the proxy.
   */
  public static function buildProxyClassName($class_name) {
    $match = [];
    preg_match('/([a-zA-Z0-9_]+\\\\[a-zA-Z0-9_]+)\\\\(.+)/', $class_name, $match);
    $root_namespace = $match[1];
    $rest_fqcn = $match[2];
    $proxy_class_name = $root_namespace . '\\ProxyClass\\' . $rest_fqcn;

    return $proxy_class_name;
  }

  /**
   * Generates the used proxy namespace from a given class name.
   *
   * @param string $class_name
   *   The class name of the actual service.
   *
   * @return string
   *   The namespace name of the proxy.
   */
  public static function buildProxyNamespace($class_name) {
    $proxy_classname = static::buildProxyClassName($class_name);

    preg_match('/(.+)\\\\[a-zA-Z0-9]+/', $proxy_classname, $match);
    $proxy_namespace = $match[1];
    return $proxy_namespace;
  }

  /**
   * Builds a proxy class string.
   *
   * @param string $class_name
   *   The class name of the actual service.
   *
   * @return string
   *   The full string with namespace class and methods.
   */
  public function build($class_name) {
    $reflection = new \ReflectionClass($class_name);

    $proxy_class_name = $this->buildProxyClassName($class_name);
    $proxy_namespace = $this->buildProxyNamespace($class_name);
    $proxy_class_shortname = str_replace($proxy_namespace . '\\', '', $proxy_class_name);

    $output = '';
    $class_documentation = <<<'EOS'

namespace {{ namespace }}{

    /**
     * Provides a proxy class for \{{ class_name }}.
     *
     * @see \Drupal\Component\ProxyBuilder
     */

EOS;
    $class_start = '    class {{ proxy_class_shortname }}';

    // For cases in which the implemented interface is a child of another
    // interface, getInterfaceNames() also returns the parent. This causes a
    // PHP error.
    // In order to avoid that, check for each interface, whether one of its
    // parents is also in the list and exclude it.
    if ($interfaces = $reflection->getInterfaces()) {
      foreach ($interfaces as $interface) {
        // Exclude all parents from the list of implemented interfaces of the
        // class.
        if ($parent_interfaces = $interface->getInterfaceNames()) {
          foreach ($parent_interfaces as $parent_interface) {
            unset($interfaces[$parent_interface]);
          }
        }
      }

      $interface_names = [];
      foreach ($interfaces as $interface) {
        $interface_names[] = '\\' . $interface->getName();
      }
      $class_start .= ' implements ' . implode(', ', $interface_names);
    }

    $output .= $this->buildUseStatements();

    // The actual class;
    $properties = <<<'EOS'
/**
 * The id of the original proxied service.
 *
 * @var string
 */
protected $drupalProxyOriginalServiceId;

/**
 * The real proxied service, after it was lazy loaded.
 *
 * @var \{{ class_name }}
 */
protected $service;

/**
 * The service container.
 *
 * @var \Symfony\Component\DependencyInjection\ContainerInterface
 */
protected $container;


EOS;

    $output .= $properties;

    // Add all the methods.
    $methods = [];
    $methods[] = $this->buildConstructorMethod();
    $methods[] = $this->buildLazyLoadItselfMethod();

    // Add all the methods of the proxied service.
    $reflection_methods = $reflection->getMethods();

    foreach ($reflection_methods as $method) {
      if ($method->getName() === '__construct') {
        continue;
      }

      if ($method->isPublic()) {
        $methods[] = $this->buildMethod($method) . "\n";
      }
    }

    $output .= implode("\n", $methods);

    // Indent the output.
    $output = implode("\n", array_map(function ($value) {
      if ($value === '') {
        return $value;
      }
      return "        $value";
    }, explode("\n", $output)));

    $final_output = $class_documentation . $class_start . "\n    {\n\n" . $output . "\n    }\n\n}\n";

    $final_output = str_replace('{{ class_name }}', $class_name, $final_output);
    $final_output = str_replace('{{ namespace }}', $proxy_namespace ? $proxy_namespace . ' ' : '', $final_output);
    $final_output = str_replace('{{ proxy_class_shortname }}', $proxy_class_shortname, $final_output);

    return $final_output;
  }

  /**
   * Generates the string for the method which loads the actual service.
   *
   * @return string
   *   A string for the lazyLoadItself method.
   */
  protected function buildLazyLoadItselfMethod() {
    $output = <<<'EOS'
/**
 * Lazy loads the real service from the container.
 *
 * @return object
 *   Returns the constructed real service.
 */
protected function lazyLoadItself()
{
    if (!isset($this->service)) {
        $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
    }

    return $this->service;
}

EOS;

    return $output;
  }

  /**
   * Generates the string representation of a single method: signature, body.
   *
   * @param \ReflectionMethod $reflection_method
   *   A reflection method for the method.
   *
   * @return string
   *   The docblock, signature, and body for a method.
   */
  protected function buildMethod(\ReflectionMethod $reflection_method) {

    $parameters = [];
    foreach ($reflection_method->getParameters() as $parameter) {
      $parameters[] = $this->buildParameter($parameter);
    }

    $function_name = $reflection_method->getName();

    $reference = '';
    if ($reflection_method->returnsReference()) {
      $reference = '&';
    }

    $signature_line = <<<'EOS'
/**
 * {@inheritdoc}
 */

EOS;

    if ($reflection_method->isStatic()) {
      $signature_line .= 'public static function ' . $reference . $function_name . '(';
    }
    else {
      $signature_line .= 'public function ' . $reference . $function_name . '(';
    }

    $signature_line .= implode(', ', $parameters);
    $signature_line .= ')';
    if ($reflection_method->hasReturnType()) {
      $signature_line .= ': ';
      $return_type = $reflection_method->getReturnType();
      if ($return_type->allowsNull()) {
        $signature_line .= '?';
      }
      if (!$return_type->isBuiltin()) {
        // The parameter is a class or interface.
        $signature_line .= '\\';
      }
      $return_type_name = $return_type->getName();
      if ($return_type_name === 'self') {
        $return_type_name = $reflection_method->getDeclaringClass()->getName();
      }
      $signature_line .= $return_type_name;
    }

    $output = $signature_line . "\n{\n";

    $output .= $this->buildMethodBody($reflection_method);

    $output .= "\n" . '}';
    return $output;
  }

  /**
   * Builds a string for a single parameter of a method.
   *
   * @param \ReflectionParameter $parameter
   *   A reflection object of the parameter.
   *
   * @return string
   *   A parameter string.
   */
  protected function buildParameter(\ReflectionParameter $parameter) {
    $parameter_string = '';

    if ($parameter->hasType()) {
      $type = $parameter->getType();
      if ($type->allowsNull()) {
        $parameter_string .= '?';
      }
      if (!$type->isBuiltin()) {
        // The parameter is a class or interface.
        $parameter_string .= '\\';
      }
      $type_name = $type->getName();
      if ($type_name === 'self') {
        $type_name = $parameter->getDeclaringClass()->getName();
      }
      $parameter_string .= $type_name . ' ';
    }

    if ($parameter->isPassedByReference()) {
      $parameter_string .= '&';
    }

    $parameter_string .= '$' . $parameter->getName();

    if ($parameter->isDefaultValueAvailable()) {
      $parameter_string .= ' = ';
      $parameter_string .= var_export($parameter->getDefaultValue(), TRUE);
    }

    return $parameter_string;
  }

  /**
   * Builds the body of a wrapped method.
   *
   * @param \ReflectionMethod $reflection_method
   *   A reflection method for the method.
   *
   * @return string
   *   The body for a method.
   */
  protected function buildMethodBody(\ReflectionMethod $reflection_method) {
    $output = '';

    $function_name = $reflection_method->getName();

    if (!$reflection_method->isStatic()) {
      if ($reflection_method->getReturnType() && $reflection_method->getReturnType()->getName() === 'void') {
        $output .= '    $this->lazyLoadItself()->' . $function_name . '(';
      }
      else {
        $output .= '    return $this->lazyLoadItself()->' . $function_name . '(';
      }
    }
    else {
      $class_name = $reflection_method->getDeclaringClass()->getName();
      $output .= "    \\$class_name::$function_name(";
    }

    // Add parameters;
    $parameters = [];
    foreach ($reflection_method->getParameters() as $parameter) {
      $parameters[] = '$' . $parameter->getName();
    }

    $output .= implode(', ', $parameters) . ');';

    return $output;
  }

  /**
   * Builds the constructor used to inject the actual service ID.
   *
   * @return string
   *   The constructor for a class.
   */
  protected function buildConstructorMethod() {
    $output = <<<'EOS'
/**
 * Constructs a ProxyClass Drupal proxy object.
 *
 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
 *   The container.
 * @param string $drupal_proxy_original_service_id
 *   The service ID of the original service.
 */
public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
{
    $this->container = $container;
    $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
}

EOS;

    return $output;
  }

  /**
   * Build the required use statements of the proxy class.
   *
   * @return string
   *   The use statements.
   */
  protected function buildUseStatements() {
    $output = '';

    return $output;
  }

}
