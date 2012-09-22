<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteCompiler.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RouteCompilerInterface;
use Symfony\Component\Routing\Route;

/**
 * Compiler to generate derived information from a Route necessary for matching.
 */
class RouteCompiler implements RouteCompilerInterface {

  /**
   * The maximum number of path elements for a route pattern;
   */
  const MAX_PARTS = 9;

  /**
   * Utility constant to use for regular expressions against the path.
   */
  const REGEX_DELIMITER = '#';

  /**
   * Compiles the current route instance.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   *
   * @return CompiledRoute
   *   A CompiledRoute instance.
   */
  public function compile(Route $route) {

    $stripped_path = $this->getPathWithoutDefaults($route);

    $fit = $this->getFit($stripped_path);

    $pattern_outline = $this->getPatternOutline($stripped_path);

    $num_parts = count(explode('/', trim($pattern_outline, '/')));

    $regex = $this->getRegex($route, $route->getPattern());

    return new CompiledRoute($route, $fit, $pattern_outline, $num_parts, $regex);
  }

  /**
   * Generates a regular expression that will match this pattern.
   *
   * This regex can be used in preg_match() to extract values inside {}.
   *
   * This algorithm was lifted directly from Symfony's RouteCompiler class.
   * It is not factored out nicely there, so we cannot simply subclass it.
   * @todo Refactor Symfony's RouteCompiler so that it's useful to subclass.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param string $pattern
   *   The pattern for which we want a matching regex.
   *
   * @return type
   *
   * @throws \LogicException
   */
  public function getRegex(Route $route, $pattern) {
    $len = strlen($pattern);
    $tokens = array();
    $variables = array();
    $pos = 0;
    preg_match_all('#.\{(\w+)\}#', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
    foreach ($matches as $match) {
      if ($text = substr($pattern, $pos, $match[0][1] - $pos)) {
        $tokens[] = array('text', $text);
      }

      $pos = $match[0][1] + strlen($match[0][0]);
      $var = $match[1][0];

      if ($req = $route->getRequirement($var)) {
        $regexp = $req;
      }
      else {
        // Use the character preceding the variable as a separator
        $separators = array($match[0][0][0]);

        if ($pos !== $len) {
          // Use the character following the variable as the separator when available
          $separators[] = $pattern[$pos];
        }
        $regexp = sprintf('[^%s]+', preg_quote(implode('', array_unique($separators)), self::REGEX_DELIMITER));
      }

      $tokens[] = array('variable', $match[0][0][0], $regexp, $var);

      if (in_array($var, $variables)) {
        throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $route->getPattern(), $var));
      }

      $variables[] = $var;
    }

    if ($pos < $len) {
      $tokens[] = array('text', substr($pattern, $pos));
    }

    // find the first optional token
    $first_optional = INF;
    for ($i = count($tokens) - 1; $i >= 0; $i--) {
        $token = $tokens[$i];
        if ('variable' === $token[0] && $route->hasDefault($token[3])) {
            $first_optional = $i;
        } else {
            break;
        }
    }

    // compute the matching regexp
    $regexp = '';
    for ($i = 0, $nbToken = count($tokens); $i < $nbToken; $i++) {
        $regexp .= $this->computeRegexp($tokens, $i, $first_optional);
    }

    return self::REGEX_DELIMITER.'^'.$regexp.'$'.self::REGEX_DELIMITER.'s';
  }

  /**
   * Computes the regexp used to match a specific token. It can be static text or a subpattern.
   *
   * @param array $tokens
   *   The route tokens
   * @param integer $index
   *   The index of the current token
   * @param integer $first_optional
   *   The index of the first optional token
   *
   * @return string
   *   The regexp pattern for a single token
   */
  private function computeRegexp(array $tokens, $index, $first_optional) {
    $token = $tokens[$index];
    if ('text' === $token[0]) {
      // Text tokens
      return preg_quote($token[1], self::REGEX_DELIMITER);
    }
    else {
      // Variable tokens
      if (0 === $index && 0 === $first_optional) {
        // When the only token is an optional variable token, the separator is
        // required.
        return sprintf('%s(?<%s>%s)?', preg_quote($token[1], self::REGEX_DELIMITER), $token[3], $token[2]);
      }
      else {
        $regexp = sprintf('%s(?<%s>%s)', preg_quote($token[1], self::REGEX_DELIMITER), $token[3], $token[2]);
        if ($index >= $first_optional) {
          // Enclose each optional token in a subpattern to make it optional.
          // "?:" means it is non-capturing, i.e. the portion of the subject
          // string that matched the optional subpattern is not passed back.
          $regexp = "(?:$regexp";
          $nbTokens = count($tokens);
          if ($nbTokens - 1 == $index) {
            // Close the optional subpatterns.
            $regexp .= str_repeat(")?", $nbTokens - $first_optional - (0 === $first_optional ? 1 : 0));
          }
        }

        return $regexp;
      }
    }
  }

  /**
   * Returns the pattern outline.
   *
   * The pattern outline is the path pattern but normalized so that all
   * placeholders are equal strings and default values are removed.
   *
   * @param string $path
   *   The path for which we want the normalized outline.
   *
   * @return string
   *   The path pattern outline.
   */
  public function getPatternOutline($path) {
    return preg_replace('#\{\w+\}#', '%', $path);
  }

  /**
   * Determines the fitness of the provided path.
   *
   * @param string $path
   *   The path whose fitness we want.
   *
   * @return int
   *   The fitness of the path, as an integer.
   */
  public function getFit($path) {
    $parts = explode('/', trim($path, '/'), static::MAX_PARTS);
    $number_parts = count($parts);
    // We store the highest index of parts here to save some work in the fit
    // calculation loop.
    $slashes = $number_parts - 1;

    $fit = 0;
    foreach ($parts as $k => $part) {
      if (strpos($part, '{') === FALSE) {
        $fit |=  1 << ($slashes - $k);
      }
    }

    return $fit;
  }

  /**
   * Returns the path of the route, without placeholders with a default value.
   *
   * When computing the path outline and fit, we want to skip default-value
   * placeholders.  If we didn't, the path would never match.  Note that this
   * only works for placeholders at the end of the path. Infix placeholders
   * with default values don't make sense anyway, so that should not be a
   * problem.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to have the placeholders removed from.
   *
   * @return string
   *   The path string, stripped of placeholders that have default values.
   */
  protected function getPathWithoutDefaults(Route $route) {
    $path = $route->getPattern();
    $defaults = $route->getDefaults();

    // Remove placeholders with default values from the outline, so that they
    // will still match.
    $remove = array_map(function($a) {
      return '/{' . $a . '}';
    }, array_keys($defaults));
    $path = str_replace($remove, '', $path);

    return $path;
  }

}
