<?php

namespace Drupal\TestTools;

use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Provides handlers for the Symfony VarDumper to work within tests.
 *
 * This allows the dump() function to produce output on the terminal without
 * causing PHPUnit to complain.
 */
class TestVarDumper {

  /**
   * Class properties to remove when dump()ing an object.
   *
   * Keys are fully-qualified class names; values are arrays of property names.
   * Only protected properties are supported.
   *
   * @var array
   */
  static protected $classPropertiesToRemove = [
    \Drupal\Core\Entity\ContentEntityStorageBase::class => [
      'fieldStorageDefinitions',
      'tableMapping',
      'database',
      'moduleHandler',
      'entityFieldManager',
      'languageManager',
      'entityTypeManager',
    ],
    \Drupal\Core\Entity\ContentEntityBase::class => [
      'languages',
      'fieldDefinitions',
      'fields',
      'typedData',
    ],
  ];

  /**
   * A CLI handler for \Symfony\Component\VarDumper\VarDumper.
   */
  public static function cliHandler($var) {
    $cloner = new VarCloner();

    $casters = [];
    foreach (static::$classPropertiesToRemove as $class => $properties) {
      $casters[$class] = static::class . '::' . 'removePropertiesCaster';
    }

    $cloner->addCasters($casters);

    $dumper = new CliDumper();
    fwrite(STDOUT, "\n");
    $dumper->setColors(TRUE);
    $dumper->dump(
      $cloner->cloneVar($var),
      function ($line, $depth, $indent_pad) {
        // A negative depth means "end of dump".
        if ($depth >= 0) {
          // Adds a two spaces indentation to the line.
          fwrite(STDOUT, str_repeat($indent_pad, $depth) . $line . "\n");
        }
      }
    );
  }

  /**
   * Caster to remove properties from objects.
   *
   * Uses the property lists set in static::$classPropertiesToRemove.
   */
  public static function removePropertiesCaster($object, $array, Stub $stub, $isNested, $filter) {
    foreach (class_parents($object) as $parent_class) {
      if (isset(static::$classPropertiesToRemove[$parent_class])) {
        foreach (static::$classPropertiesToRemove[$parent_class] as $property_name) {
          unset($array["\0*\0" . $property_name]);
        }
      }
    }

    return $array;
  }


  /**
   * A HTML handler for \Symfony\Component\VarDumper\VarDumper.
   */
  public static function htmlHandler($var) {
    $cloner = new VarCloner();
    $dumper = new HtmlDumper();
    $dumper->dump($cloner->cloneVar($var));
  }

}
