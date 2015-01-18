<?php

/**
 * @file
 * Contains \Drupal\Component\ProxyBuilder\ProxyBuilder.
 */

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
    return str_replace('\\', '_', $class_name) . '_Proxy';
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

    $output = '';
    $class_documentation = <<<'EOS'
/**
 * Provides a proxy class for \{{ class_name }}.
 *
 * @see \Drupal\Component\ProxyBuilder
 */

EOS;
    $class_start = 'class {{ proxy_class_name }}';

    // For cases in which the implemented interface is a child of another
    // interface, getInterfaceNames() also returns the parent. This causes a
    // PHP error.
    // In order to avoid that, check for each interface, whether one of its
    // parents is also in the list and exclude it.
    if ($interfaces = $reflection->getInterfaces()) {
      foreach ($interfaces as $interface_name => $interface) {
        // Exclude all parents from the list of implemented interfaces of the
        // class.
        if ($parent_interfaces = $interface->getInterfaceNames()) {
          foreach ($parent_interfaces as $parent_interface) {
            if (isset($interfaces[$parent_interface])) {}
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
 * @var string
 */
protected $serviceId;

/**
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
      return "    $value";
    }, explode("\n", $output)));

    $final_output = $class_documentation . $class_start . "\n{\n\n" . $output . "\n}\n";

    $final_output = str_replace('{{ class_name }}', $class_name, $final_output);
    $final_output = str_replace('{{ proxy_class_name }}', $this->buildProxyClassName($class_name), $final_output);

    return $final_output;
  }

  /**
   * Generates the string for the method which loads the actual service.
   *
   * @return string
   */
  protected function buildLazyLoadItselfMethod() {
    $output = <<<'EOS'
protected function lazyLoadItself()
{
    if (!isset($this->service)) {
        $method_name = 'get' . Container::camelize($this->serviceId) . 'Service';
        $this->service = $this->container->$method_name(false);
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
    if ($reflection_method->isStatic()) {
      $signature_line = 'public static function ' . $reference . $function_name . '(';
    }
    else {
      $signature_line = 'public function ' . $reference . $function_name . '(';
    }

    $signature_line .= implode(', ', $parameters);
    $signature_line .= ')';

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
   */
  protected function buildParameter(\ReflectionParameter $parameter) {
    $parameter_string = '';

    if ($parameter->isArray()) {
      $parameter_string .= 'array ';
    }
    elseif ($parameter->isCallable()) {
      $parameter_string .= 'callable ';
    }
    elseif ($class = $parameter->getClass()) {
      $parameter_string .= '\\' . $class->getName() . ' ';
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
   */
  protected function buildMethodBody(\ReflectionMethod $reflection_method) {
    $output = '';

    $function_name = $reflection_method->getName();

    if (!$reflection_method->isStatic()) {
      $output .= '    return $this->lazyLoadItself()->' . $function_name . '(';
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
   */
  protected function buildConstructorMethod() {
    $output = <<<'EOS'
public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $serviceId)
{
    $this->container = $container;
    $this->serviceId = $serviceId;
}

EOS;

    return $output;
  }

  /**
   * Build the required use statements of the proxy class.
   *
   * @return string
   */
  protected function buildUseStatements() {
    $output = '';

    return $output;
  }

}
