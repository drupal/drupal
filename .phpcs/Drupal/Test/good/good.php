<?php

/**
 * @file
 * This file contains all the valid notations for the drupal coding standard.
 *
 * The goal is to create a style checker that validates all of this
 * constructs.
 *
 * Theme files often have lists in their file block:
 * - item 1
 * - item 2
 * - sublist:
 *   - sub item 1
 *   - sub item2
 */

use Drupal\very_long_module_name_i_am_inventing_here_trololololo\SuperManager;
use \Drupal\some_module\ExampleClass as AliasedExampleClass;

// Singleline comment before a code line.
$foo = 'bar';

/**
 * Doxygen comment style is allowed before define() statements.
 */
define('FOO_BAR', 5);

// Global variable names.
global $argc, $argv, $user, $is_https, $_mymodule_myvar;

/*
 * Multiline comment
 *
 * @see my_function()
 */

// PHP Constants must be written in CAPITAL letters.
TRUE;
FALSE;
NULL;

// Has whitespace at the end of the line.
$whitespaces = 'Yes, Please';

// Operators - have a space before and after.
$i = 0;
$i += 0;
$i -= 0;
$i == 0;
$i != 0;
$i > 0;
$i < 0;
$i >= 0;
$i <= 0;

// Unary operators must not have a space.
$i--;
--$i;
$i++;
++$i;
$x = $success ? $context['results']['success']++ : $context['results']['error']++;
$x = $success ? foo()++ : bar()++;
$i = -1;
array('i' => -1);
$i = (1 == -1);
$i = (1 === -1);
range(-50, -45);
$i[0] + 1;
$x->{$i} + 1;
REQUEST_TIME + 42;
!$x;
!($x + $y);
array(-1, -2, -3);
[-1, -2, -3];

// Operator line break for long lines.
$x = 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 +
  1 + 1;

$x = $test ? -1 : 1;
$x = $test ? 1 : -1;

// Operators on new lines are allowed.
$x = (CRM_Foo_Bar::singleton()->checkWhizBang($option1, $option2))
  ? $something . $notes['baz']
  : $notes['quux'] . $something_else;

// Casting has a space.
(int) $i;

// The last item in an multiline array should be followed by a comma.
// But not in a inline array.
$a = array();
$a = array('1', '2', '3');
$a = array(
  '1',
  '2',
  '3',
);
$a = array('1', '2', array('3'));
$a = array('1', '2',
  array(
    'one',
    'two',
    'three',
    array(
      'key' => $value,
      'title' => 'test',
    ),
  ),
);
// Short array syntax.
$a = [];
$a = ['1', '2', '3'];
$a = [
  '1',
  '2',
  '3',
];
$a = ['1', '2', ['3']];
$a = ['1', '2',
  [
    'one',
    'two',
    'three',
    [
      'key' => $value,
      'title' => 'test',
    ],
  ],
];

// Array indentation.
$x = array(
  'foo' => 'bar',
  'fi' => long_function_call('hsdfsdmfsldkfnmdflkngdfngfg',
    'fghfghfghfghfgh', $z),
  'a' => 'b',
  'foo' => array(
    'blu' => 1,
    'f' => x(1) + array(
      'h' => 'x',
    ),
  ),
);
$x = [
  'foo' => 'bar',
  'fi' => long_function_call('hsdfsdmfsldkfnmdflkngdfngfg',
    'fghfghfghfghfgh', $z),
  'a' => 'b',
  'foo' => [
    'blu' => 1,
    'f' => x(1) + [
      'h' => 'x',
    ],
  ],
];

// Arrays in function calls.
foo(
  array(
    'value' => 0,
    'description' => t('xyz @url',
      array(
        '@url' => 'http://example.com',
      )
    ),
  )
);
foo(
  [
    'value' => 0,
    'description' => t('xyz @url',
      [
        '@url' => 'http://example.com',
      ]
    ),
  ]
);

// Pretty array layout.
$a = array(
  'title'    => 1,
  'weight'   => 2,
  'callback' => 3,
);
$a = [
  'title'    => 1,
  'weight'   => 2,
  'callback' => 3,
];

// Arrays with multi line strings.
$query = db_query("
  SELECT * FROM {foobar} WHERE nid IN (1, 2, 3)
  AND date BETWEEN '%s' AND '%s'
  ", array(
    ':from_date' => $from_date,
    ':to_date' => $to_date,
  )
);
$query = db_query("
  SELECT * FROM {foobar} WHERE nid IN (1, 2, 3)
  AND date BETWEEN '%s' AND '%s'
  ", [
    ':from_date' => $from_date,
    ':to_date' => $to_date,
  ]
);

// Array with multi line comments.
$query = db_query("
  SELECT * FROM {foobar} WHERE nid IN (1, 2, 3)
  AND date BETWEEN '%s' AND '%s'", /* comment
  in here */ array(
    ':from_date' => $from_date,
    ':to_date' => $to_date,
  )
);
$query = db_query("
  SELECT * FROM {foobar} WHERE nid IN (1, 2, 3)
  AND date BETWEEN '%s' AND '%s'", /* comment
  in here */ [
    ':from_date' => $from_date,
    ':to_date' => $to_date,
  ]
);

// Array with multi-line constant string in it.
$array = array(
  'name' => 'example_a',
  'title' => 'Example A',
  'xml' => '
<foo>
  <bar>
    123456789 123456789 123456789 123456789 123456789 123456789 123456789
    123456789 123456789 123456789 123456789 123456789 123456789 123456789
  </bar>
</foo>',
);
$array = [
  'name' => 'example_a',
  'title' => 'Example A',
  'xml' => '
<foo>
  <bar>
    123456789 123456789 123456789 123456789 123456789 123456789 123456789
    123456789 123456789 123456789 123456789 123456789 123456789 123456789
  </bar>
</foo>',
];

// Indentation: multi line function call with array and fuction closer on the
// same line.
$result = example_fetch_data($id,
  array(
    'include_detail' => TRUE,
    'quiet' => TRUE,
  ));
some_function();
$result = example_fetch_data($id,
  [
    'include_detail' => TRUE,
    'quiet' => TRUE,
  ]);
some_function();

// Indentation: multi line function call with array and closing brace on the
// same line.
watchdog('example', 'Some warning %code for %id',
  array(
    '%code' => $code,
    '%doi' => $id,
  ),
  WATCHDOG_WARNING);
watchdog('example', 'Some warning %code for %id',
  [
    '%code' => $code,
    '%doi' => $id,
  ],
  WATCHDOG_WARNING);

// Nested short array syntax.
$x = [
  'label' => x(['test' => 'bar']),
];

// Nested arrays with object operators.
$derivatives["entity:$entity_type_id"] = array(
  'label' => t('Create @entity_type path alias', array('@entity_type' => $entity_type->getLowercaseLabel())),
  'category' => t('Path'),
  'entity_type_id' => $entity_type_id,
  'context' => array(
    'entity' => ContextDefinition::create("entity:$entity_type_id")
      ->setLabel($entity_type->getLabel())
      ->setRequired(TRUE)
      ->setDescription(t('The @entity_type for which to create a path alias.', array('@entity_type' => $entity_type->getLowercaseLabel()))),
    'alias' => ContextDefinition::create('string')
      ->setLabel(t('Path alias'))
      ->setRequired(TRUE)
      ->setDescription(t("Specify an alternative path by which the content can be accessed. For example, 'about' for an about page. Use a relative path and do not add a trailing slash.")),
  ),
  'provides' => array(),
) + $base_plugin_definition;

$derivatives["entity:$entity_type_id"] = [
  'label' => t('Create @entity_type path alias', ['@entity_type' => $entity_type->getLowercaseLabel()]),
  'category' => t('Path'),
  'entity_type_id' => $entity_type_id,
  'context' => [
    'entity' => ContextDefinition::create("entity:$entity_type_id")
      ->setLabel($entity_type->getLabel())
      ->setRequired(TRUE)
      ->setDescription(t('The @entity_type for which to create a path alias.', ['@entity_type' => $entity_type->getLowercaseLabel()])),
    'alias' => ContextDefinition::create('string')
      ->setLabel(t('Path alias'))
      ->setRequired(TRUE)
      ->setDescription(t("Specify an alternative path by which the content can be accessed. For example, 'about' for an about page. Use a relative path and do not add a trailing slash.")),
  ],
  'provides' => [],
] + $base_plugin_definition;

$test = array(
  'columns' => $columns,
  'indexes' => array(),
  'foreign keys' => array(
    'format' => array(
      'table' => 'filter_format',
      'columns' => array('format' => 'format'),
    ),
    'file_managed' => array(
      'table' => 'file_managed',
      'columns' => array('fid' => 'carousel_image'),
    ),
  ),
);

// Arrays by reference in arrays.
$x = array('foo');
$y = array(&$x);

$x = ['foo'];
$y = [&$x];

// Multi-line function call with array and anonymous function.
multiline_call(Inspector::assertAllCallable([
  'strchr',
  [$x, 'callMe'],
  ['test', 'callMeStatic'],
  function() {
    return TRUE;
  },
]));
multiline_call(Inspector::assertAllCallable(array(
  'strchr',
  array($x, 'callMe'),
  array('test', 'callMeStatic'),
  function() {
    return TRUE;
  },
)));

// Nested array indentation with closures.
$options = array(
  'value' => array(
    'Callback' => array(
      'callback' => function ($value, ExecutionContextInterface $context) {
        TheaterItem::theaterValidate($value, $context);
      },
    ),
  ),
);

$options = array(
  'value' => [
    'Callback' => array(
      'callback' => function ($value, ExecutionContextInterface $context) {
        TheaterItem::theaterValidate($value, $context);
      },
    ),
  ],
);

// Item assignment operators must be prefixed and followed by a space.
$a = array('one' => '1', 'two' => '2');
foreach ($a as $key => $value) {
}

// If conditions have a space before and after the condition parenthesis.
if (TRUE || TRUE) {
  $i;
}
elseif (TRUE && TRUE) {
  $i;
}
// Commenting is allowed here.
else {
  $i;
}

while (1 == 1) {
  if (TRUE || TRUE) {
    $i;
  }
  else {
    $i;
  }
}

switch ($condition) {
  case 1:
    $i;
    break;

  case 2:
    $i;
    break;

  // Blank line after the case statement is allowed.
  case 3:

    $i;
    break;

  default:
    $i;
}

// Empty case statements are allowed.
switch ($code) {
  case 200:
  case 304:
    break;

  case 301:
  case 302:
  case 307:
    call_function();
    break;

  default:
    $result->error = $status_message;
}

/**
 * Allowed switch() statement in a function without breaks.
 */
function foo() {
  switch ($x) {
    case 1:
      return 5;

    case 2:
      return 6;

    case 3:
      return array(
        'whiz',
        'bang',
      );

    case 4:
      return helper_func(
        'whiz',
        'bang'
      );

    default:
      throw new Exception();
  }
}

do {
  $i;
} while ($condition);

/**
 * Short description.
 *
 * We use doxygen style comments.
 * What's sad because eclipse PDT and
 * PEAR CodeSniffer base on phpDoc comment style.
 * Makes working with drupal not easier :|
 *
 * @param string $field1
 *   Doxygen style comments.
 * @param int $field2
 *   Doxygen style comments.
 * @param bool $field3
 *   Doxygen style comments.
 * @param bool $field4
 *   Don't check for & in docblock of referenced variable.
 *
 * @return array
 *   Doxygen style comments.
 *
 * @see example_reference()
 * @see Example::exampleMethod()
 * @see https://www.drupal.org
 * @see http://example.com/see/documentation/is/allowed/to/exceed/eighty/characters
 */
function foo_bar($field1, $field2, $field3 = NULL, &$field4 = NULL) {
  $system["description"] = t("This module inserts funny text into posts randomly.");
  return $system[$field];
}

// Function call.
$var = foo($i, $i, $i);

// Multiline function call.
$var = foo(
  $i,
  $i,
  $i
);

// Multiline function call with array.
$var = foo(
  array(
    $i,
    $i,
    $i,
  ),
  $i,
  $i
);

// Multiline function call with only one array.
$var = foo(
  array(
    $i,
    $i,
    $i,
  )
);

/**
 * Class declaration.
 *
 * Classes always have a multiline comment.
 */
class Bar {

  // Private properties have no prefix.
  private $secret = 1;

  // Protected properties also don't have a prefix.
  protected $foo = 1;

  // Longer properties use camelCase naming.
  public $barProperty = 1;

  // Public static variables use camelCase, too.
  public static $basePath = NULL;

  /**
   * Enter description here ...
   */
  public function foo() {

  }

  /**
   * Enter description here ...
   */
  protected function barMethod() {

  }

  /**
   * Test the ++ and -- operator.
   */
  public function incDecTest() {
    $this->foo++;
    $this->foo--;
    --$this->foo;
    ++$this->foo;
  }

}

/**
 * Enter description here ...
 */
function _private_foo() {

}

// When calling class constructors with no arguments, always include
// parentheses.
$bar = new Bar();

$bar = new Bar($arg1, $arg2);

$bar = 'Bar';
$foo = new $bar();
$foo = new $bar($i, $i);

// Static class variables use camelCase.
Bar::$basePath = '/foo';

// Concatenation - there has to be a space.
$i . "test";
$i . 'test';
$i . $i;
$i . NULL;

/**
 * It is allowed to have the closing "}" on the same line if the class is empty.
 */
class MyException extends Exception {}

/**
 * Nice alignment is allowed for assignments.
 */
class MyExampleLog {
  const INFO      = 0;
  const WARNING   = 1;
  const ERROR     = 2;
  const EMERGENCY = 3;

  /**
   * Empty method implementation is allowed.
   */
  public function emptyMethod() {}

  /**
   * Protected functions are allowed.
   */
  protected function protectedTest() {

  }

}

/**
 * Nice allignment in functions.
 */
function test_test2() {
  $a   = 5;
  $aa  = 6;
  $aaa = 7;
}

/**
 * Example of chained method invocations in a function.
 */
function test3() {
  // Uses the Rules API as example.
  $rule = rule();
  $rule->condition('rules_test_condition_true')
       ->condition('rules_test_condition_true')
       ->condition(rules_or()
         // Inline test comment that continues on a
         // second line.
         ->condition(rules_condition('rules_test_condition_true')->negate())
         ->condition('rules_test_condition_false')
         ->condition(rules_and()
           ->condition('rules_test_condition_false')
           ->condition('rules_test_condition_true')
           ->negate()
         )
       );
}

// Test usages of t().
t('special character: \"');
t("special character: \'");
// Escaping is allowed here because we make use of the other quote type, too.
t('Link to Drupal\'s <a href="@url">admin pages</a>.', array('@url' => url('admin')));

// Test inline comment style.
// Comment one.
t('x');
// Comment two.
// @todo this is valid!
t('x');
// Goes on?
t('x');
/* Longer comment
 * bla bla
 * end!
 */
t('x');
// @see http://example.com/with/a/very/long/link/that/is/longer/than/80/characters
t('x');
// http://example.com/with/a/very/long/link/that/is/longer/than/80/characters/really
t('x');
// @see my_function()
t('x');
// Some text here, then a reference.
// @see \Drupal\rules\Entity\ReactionRuleStorage
t('x');
// t() refers to a function name and should not be capitalized.
t('x');
// rules_admin is a fancy machine name word with underscores and should not be
// capitalized and ignored.
t('x');
// {} this comment start should not be flagged.
t('x');
// Some code examples in inline comments:
// @code
//   function mymodule_menu() {
//     $items['abc/def'] = array(
//       'page callback' => 'mymodule_abc_view',
//     );
//     return $items;
//   }
// @endcode
// @todo A long description what is left to be done. Exceeds the first line and
//   is indented on the second line.
// Now follows a list:
// - item 1
//   additional description line.
// - item 2
// - item 3 comes with a sub list:
//   - sub item 1
//   - sub item 2
//     description of sub item 2.
// List is closed, normal indentation here. Now comes a paragraph empty line.
//
// And here continues the long comment.
// Now some UTF-8 characters that do not exceed 80 characters.
// Hà Nội là thủ đô, đồng thời là thành phố đứng đầu Việt Nam về diện tích tự.
t('x');

/**
 * Doc block with some code tags.
 *
 * Some description and here comes the code:
 * @code
 *   $options = drupal_parse_url($_GET['destination']);
 *   $my_url = url($options['path'], $options);
 *   $my_link = l('Example link', $options['path'], $options);
 * @endcode
 *
 * Some more description here. And now the parameters.
 *
 * @param int $x
 *   Parameter comment here.
 */
function test1($x) {

}

/**
 * Variable amount of parameters is allowed with the "..." notation.
 *
 * Example:
 * @code
 *   test_invoke_all('node_view', $node, $view_mode);
 * @endcode
 *
 * @param string $hook
 *   The name of the hook to invoke.
 * @param ...
 *   Arguments to pass to the hook.
 */
function test_invoke_all($hook) {

}

// Test string concatenation.
$x = 'This string is very long and thus it can and should be concatenated.' .
     'Othverwise the string will be very hard to maintain and or read';
$x = 'This string should be concatenated. Even if it is just a little bit ' .
     'longer';
$x = (1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1 + 1) . 'This is initially ' .
     'short but since it has a lot of code before the real text it can be ' .
     'concatenated';

// Requiring files conditionally is allowed.
if ($a == TRUE) {
  require_once 'good.tpl.php';
}

/**
 * This is not an implementation of hook_foobar().
 */
function hook_foobar() {

}

/**
 * Implements hook_foo().
 *
 * @link http://example.com/long-link-here-whatever Some long documentation link @endlink
 */
function mymodule_foo() {

}

/**
 * Implements hook_foo_BAR_ID_bar() for some_type_bar().
 */
function mymodule_foo_some_type_bar() {

}

/**
 * Implements hook_foo_bar() for foo_bar.tpl.php.
 */
function mymodule_foo_bar_phptemplate() {

}

/**
 * Implements hook_foo_bar() for foo-bar.html.twig.
 */
function mymodule_foo_bar_twig() {

}

/**
 * Implements drush_hook_foo_bar().
 */
function drush_mymodule_foo_bar() {

}

/**
 * Not documenting all parameters is allowed.
 *
 * @param Node $node
 *   The loaded node entity that we will use to do whatever.
 */
function mymodule_form_callback($form, &$form_state, Node $node) {

}

$x = 'Some markup text<br />';

// Inline if statements with ?: are allowed.
$x = $y == $z ? 23 : 42;

// Standard watchdog message.
watchdog('mymodule', 'Log message here.');

// For assigning by reference it is allowed that there is no space after the
// "=" oeprator.
$batch =& batch_get();

// Security issue: https://www.drupal.org/node/750148
preg_match('/.+/i', 'subject');
preg_match('/.+/imsuxADSUXJ', 'subject');
preg_filter('/.+/i', 'replacement', 'subject');
preg_replace('/.+/i', 'replacement', 'subject');
// Use a not so common delimiter.
preg_match('@.+@i', 'subject');
preg_match('@.+@imsuxADSUXJ', 'subject');
preg_filter('@.+@i', 'replacement', 'subject');
preg_replace('@.+@i', 'replacement', 'subject');
preg_match("/test(\d+)/is", 'subject');

/**
 * Interfaces must have a comment block.
 */
interface MyWellNamedInterface {}

// Correctly formed try/catch block.
try {
  do_something();
}
catch (Exception $e) {
  scream();
}

$result = $x ?: FALSE;

/**
 * All classes need to have a docblock.
 */
class Foo implements FooInterface {

  /**
   * {@inheritdoc}
   */
  public function test() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity;
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = foo();
    /** @var \Drupal\node\NodeInterface|\PHPUnit_Framework_MockObject_MockObject $node_mock */
    $node_mock = mock_node();
    /** @var \Drupal\SomeInterface4You $thing */
    $thing = thing();
    /** @var \Drupal\SomeInterface4You $test2 */
    $test2 = test2();
    return $node;
  }

  /**
   * {@inheritdoc}
   *
   * Some additional documentation here.
   */
  public function test2() {}

  /**
   * Return docs are allowed to use $this.
   *
   * @return $this
   *   This object for chaining method calls.
   */
  public function test3() {
    return $this;
  }

  /**
   * Returns the string representatuion of this object.
   */
  public function __toString() {
    return 'foo';
  }

  /**
   * Omitting the comment when returning $this is allowed.
   *
   * @return $this
   */
  public function test4() {
    return $this;
  }

  /**
   * Omitting the comment when returning static is allowed.
   *
   * @return static
   */
  public function test41() {
    return new static();
  }

  /**
   * Loads multiple string objects.
   *
   * @param array $conditions
   *   Any of the conditions used by dbStringSelect().
   * @param array $options
   *   Any of the options used by dbStringSelect().
   * @param string $class
   *   Class name to use for fetching returned objects.
   *
   * @return \Drupal\locale\StringInterface[]
   *   Array of objects of the class requested.
   */
  protected function dbStringLoad(array $conditions, array $options, $class) {
    $strings = array();
    $result = $this->dbStringSelect($conditions, $options)->execute();
    foreach ($result as $item) {
      /** @var \Drupal\locale\StringInterface $string */
      $string = new $class($item);
      $string->setStorage($this);
      $strings[] = $string;
    }
    return $strings;
  }

  /**
   * Short array syntax is allowed.
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * Array type hints for optional parameters can be omitted.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Description goes here.
   */
  public function test5($contexts = []) {
    return 'test5';
  }

  /**
   * Not documenting a "throws" tag is allowed.
   *
   * @throws Exception
   */
  public function test6() {
    throw new Exception();
  }

}

t('Some long mulit-line 
  text is weird, but allowed.');

// Anonymous functions should not throw indentation errors here.
$test = array_walk($fragments, function(&$item) {
  if (strpos($item, '%') === 0) {
    $item = '%';
  }
});

/**
 * Doc tags are allowed to exceed 80 characters.
 *
 * @param \Drupal\very_long_module_name_i_am_inventing_here_trololololo\SuperManager $x
 *   The super duper manager comment goes here.
 * @param \Traversable $y
 *   Some PHP core class/interface.
 */
function test2(SuperManager $x, \Traversable $y) {

}

/**
 * Test comment.
 *
 * @throws \Drupal\locale\StringStorageException
 *   The exception description is here and should not cause an error.
 */
function test4() {

}

/**
 * Test chained method indentation which should not throw errors.
 */
function test5() {
  $mock->expects($this->any())
    ->method('findTranslation')
    ->will($this->returnCallback(function ($argument) use ($translations) {
      if (isset($translations[$argument['language']][$argument['source']])) {
        return (object) array('translation' => $translations[$argument['language']][$argument['source']]);
      }
      return TRUE;
    }));
}

/**
 * Array syntax with brackets is allowed in type hints.
 *
 * @param string[] $names
 *   An indexed array of names.
 */
function test6(array $names) {

}

list(,, $bundle) = entity_extract_ids('node', $entity);

l("<i class='icon icon-industrial-building'></i>", 'node/add/job', array(
  'attributes' => array('title' => t('add job')),
  'html'       => TRUE,
));

/**
 * Some short description.
 *
 * @todo TODOs are allowed here.
 *
 * @param string $x
 *   Some parameter.
 */
function test7($x) {

}

/**
 * @coversDefaultClass \Drupal\rules\Plugin\Condition\ListContains
 * @group rules_conditions
 */
class ListContainsTest extends RulesIntegrationTestBase {}

$x = 'Some markup text with allowed HTML5 <br> tag';

/**
 * Provides a 'Delete any path alias' action.
 *
 * @todo: Add access callback information from Drupal 7.
 * @todo: Add group information from Drupal 7.
 *
 * @Action(
 *   id = "rules_path_alias_delete",
 *   label = @Translation("Delete any path alias"),
 *   context = {
 *     "alias" = @ContextDefinition("string",
 *       label = @Translation("Existing system path alias"),
 *       description = @Translation("Specifies the existing path alias you wish to delete, for example 'about/team'. Use a relative path and do not add a trailing slash.")
 *     )
 *   }
 * )
 */
class AliasDelete extends RulesActionBase implements ContainerFactoryPluginInterface {}

/**
 * Some comment with exclamation mark!
 *
 * And the long description!
 *
 * @param int $x
 *   Exclamation mark allowed!
 *
 * @throws MyException
 *   Exclamation mark allowed!
 */
function test8($x) {

}

/**
 * Make sure that link tags are allowed in long descriptions.
 *
 * For forcing it to a boolean TRUE or FALSE please use
 * @link MenuItem::forceAccessCallbackTRUE @endlink() or
 * @link MenuItem::forceAccessCallbackFALSE @endlink() as you see fit.
 */
function test9() {

}

/**
 * Link tags in the long description before param tags are allowed.
 *
 * Visit also:
 * @link https://www.drupal.org/node/323101 Strings at well-known places: built-in menus, .. @endlink.
 *
 * @param string $title
 *   The untranslated title of the menu item.
 */
function test10($title) {

}

/**
 * Some description.
 *
 * @todo Here is a very long link that exceeds 80 charaters on the next line:
 *   http://example.com/test/long/link/with/stuff/here/making/it/even/longer/now/so/that/it/shows
 */
function test11() {

}

/**
 * Description here.
 *
 * @param array $array_param
 *   We document here that the parameter is an array, but we don't use an array
 *   type hint in the function signature which is allowed.
 */
function test12($array_param) {

}

/**
 * Paramter docs with a long nested list.
 *
 * @param string $a
 *   Lists are usually preceded by a line ending in a colon:
 *   - Item in the list.
 *   - Another item.
 *     - key: Sub-list with keys, first item.
 *     - key2: (optional) Second item with a key.
 *   - Back to the outer list. Sometimes list items are quite long, in which
 *     case you can wrap the text like this.
 *   - Last item in the outer list.
 *   Text that is outside of the list continues here.
 */
function test13($a) {

}

/**
 * Using an alias type hint but the fully qualified name in the docs.
 *
 * @param \Drupal\some_module\ExampleClass $a
 *   Example parameter.
 */
function test14(AliasedExampleClass $a) {

}

/**
 * Example annotation that exceeds 80 characters several times, but is valid.
 *
 * @ConfigEntityType(
 *   id = "rules_reaction_rule",
 *   label = @Translation("Reaction Rule"),
 *   handlers = {
 *     "storage" = "Drupal\rules\Entity\ReactionRuleStorage",
 *     "list_builder" = "Drupal\rules\Entity\Controller\RulesReactionListBuilder",
 *     "form" = {
 *        "add" = "\Drupal\rules\Entity\ReactionRuleAddForm",
 *        "edit" = "\Drupal\rules\Entity\ReactionRuleEditForm",
 *        "delete" = "\Drupal\Core\Entity\EntityDeleteForm"
 *      }
 *   },
 *   admin_permission = "administer rules",
 *   config_prefix = "reaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "event",
 *     "module",
 *     "description",
 *     "tag",
 *     "core",
 *     "expression_id",
 *     "configuration",
 *   },
 *   links = {
 *     "collection" = "/admin/config/workflow/rules",
 *     "edit-form" = "/admin/config/workflow/rules/reactions/edit/{rules_reaction_rule}",
 *     "delete-form" = "/admin/config/workflow/rules/reactions/delete/{rules_reaction_rule}"
 *   }
 * )
 */
class ReactionRule extends ConfigEntityBase {

  /**
   * Config entities are allowed to have property names with underscores.
   *
   * @var string
   */
  protected $expression_id;

}

/**
 * Test class.
 */
class OperatorTest {

  protected static $seenIds;

  /**
   * Test method.
   */
  public function test() {
    $id = $id . '--' . ++static::$seenIds[$id];
    return $id;
  }

}

// Namespaced function call is allowed because PHP 5.5 and lower do not support
// use statements for functions.
$default_config = [
  'verify' => TRUE,
  'timeout' => 30,
  'headers' => [
    'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
  ],
  'handler' => $stack,
];

// camelCase and snake_case variables are allowed.
$snake_case = 1;
$camelCase = 1;

// It should be possible to use a parenthesis in a comment without having the
// InlineCommentSniff complain. (About the the last character of the line.)
$comment = 'fine';

/**
 * Comments ending with parentheses.
 *
 * It should be possible to use a parenthesis in a comment without having the
 * DocCommentSniff complain. (About the the last character of the line.)
 */
function test15() {

}
