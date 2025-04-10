<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$data_path in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../composer/Plugin/Scaffold/Operations/AppendOp.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$app_root might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/assets/scaffold/files/default.settings.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$site_path might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/assets/scaffold/files/default.settings.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$new_set_index might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/form.inc',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method getFromDriverName\\(\\) of class Drupal\\\\Core\\\\Extension\\\\DatabaseDriverList\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  DatabaseDriverList\\:\\:get\\(\\) instead, passing a database driver namespace\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/install.core.inc',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Function install_config_download_translations\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/install.core.inc',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Function install_download_translation\\(\\) should return string but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/includes/install.core.inc',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/theme.inc',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$custom_theme might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/theme.maintenance.inc',
];
$ignoreErrors[] = [
	// identifier: staticMethod.deprecated
	'message' => '#^Call to deprecated method registerLoader\\(\\) of class Doctrine\\\\Common\\\\Annotations\\\\AnnotationRegistry\\:
This method is deprecated and will be removed in
            doctrine/annotations 2\\.0\\. Annotations will be autoloaded in 2\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Annotation/Plugin/Discovery/AnnotatedClassDiscovery.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Component\\\\Datetime\\\\DateTimePlus\\:\\:getTimeZone\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Datetime/DateTimePlus.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$value might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Datetime/DateTimePlus.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Drupal\\\\Component\\\\DependencyInjection\\\\ServiceIdHashTrait in class Drupal\\\\Component\\\\DependencyInjection\\\\Container\\:
in drupal\\:9\\.5\\.1 and is removed from drupal\\:11\\.0\\.0\\. Use the
  \'Drupal\\\\Component\\\\DependencyInjection\\\\ReverseContainer\' service instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/DependencyInjection/Container.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$x0 might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$xi in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$y0 might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$yi in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$in_seq\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$lcs\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$seq\\.$#',
	'count' => 7,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$xchanged\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$xind\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$xv\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$ychanged\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$yind\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$yv\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$k might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$seps might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$cache_backend_class\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$cache_backend_configuration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$collection\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$prefix\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Component\\\\Gettext\\\\PoMemoryWriter\\:\\:getHeader\\(\\) should return Drupal\\\\Component\\\\Gettext\\\\PoHeader but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Gettext/PoMemoryWriter.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Component\\\\Gettext\\\\PoMemoryWriter\\:\\:getLangcode\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Gettext/PoMemoryWriter.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$log_vars might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Gettext/PoStreamReader.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method ReflectionMethod\\:\\:createFromMethodName\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Utility/ArgumentsResolver.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$permission might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Access/AccessResult.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Access\\\\CsrfRequestHeaderAccessCheck\\:\\:applies\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Access/CsrfRequestHeaderAccessCheck.php',
];
$ignoreErrors[] = [
	// identifier: arguments.count
	'message' => '#^Method Drupal\\\\Core\\\\Executable\\\\ExecutableInterface\\:\\:execute\\(\\) invoked with 1 parameter, 0 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Action/ActionBase.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Action/ActionManager.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Action/ActionManager.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Archiver\\\\ArchiverManager\\:\\:getInstance\\(\\) should return object\\|false but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Archiver/ArchiverManager.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$group_keys might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Asset/CssCollectionGrouper.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$group_keys might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Asset/JsCollectionGrouper.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Block/BlockManager.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Block/BlockManager.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Cache\\\\NullBackend has an unused parameter \\$bin\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/NullBackend.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Condition/ConditionManager.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Condition/ConditionManager.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/DatabaseStorage.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/Entity/Query/Condition.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$theme_list might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/ExtensionInstallStorage.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_NULL of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_STATEMENT of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$statement might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Delete.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Database\\\\Query\\\\Delete\\:\\:execute\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Delete.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Insert.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Merge.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Database\\\\Query\\\\Merge\\:\\:__toString\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Merge.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_STATEMENT of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Select.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Truncate.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Update.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Upsert.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$affected_rows might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Upsert.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Drupal\\\\Component\\\\DependencyInjection\\\\ServiceIdHashTrait in class Drupal\\\\Core\\\\DependencyInjection\\\\ContainerBuilder\\:
in drupal\\:9\\.5\\.1 and is removed from drupal\\:11\\.0\\.0\\. Use the
  \'Drupal\\\\Component\\\\DependencyInjection\\\\ReverseContainer\' service instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DependencyInjection/ContainerBuilder.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method closing\\(\\) of class Drupal\\\\Component\\\\Diff\\\\WordLevelDiff\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method orig\\(\\) of class Drupal\\\\Component\\\\Diff\\\\WordLevelDiff\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\DrupalKernel\\:\\:discoverServiceProviders\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernel.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$container might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernel.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$container_definition might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernel.php',
];
$ignoreErrors[] = [
	// identifier: interface.extendsDeprecatedInterface
	'message' => '#^Interface Drupal\\\\Core\\\\DrupalKernelInterface extends deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernelInterface.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityConfirmFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityConfirmFormBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityNullStorage\\:\\:doLoadMultiple\\(\\) should return array\\<Drupal\\\\Core\\\\Entity\\\\EntityInterface\\> but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityNullStorage.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityNullStorage\\:\\:doSave\\(\\) should return bool\\|int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityNullStorage.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityNullStorage\\:\\:has\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityNullStorage.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$current_affected in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityStorageBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\Controller\\\\EntityController\\:\\:deleteTitle\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Controller/EntityController.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$candidate_ids might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Entity/EntityFormDisplay.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$string in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityAutocompleteMatcher.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\Entity\\\\EntityBase\\:\\:\\$id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\EntityConfirmFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityConfirmFormBase.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\Entity\\\\EntityDisplayBase\\:\\:\\$_serializedKeys\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityDisplayBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\KeyValueStore\\\\KeyValueContentEntityStorage\\:\\:createTranslation\\(\\) should return Drupal\\\\Core\\\\Entity\\\\ContentEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/KeyValueStore/KeyValueContentEntityStorage.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\KeyValueStore\\\\KeyValueContentEntityStorage\\:\\:createWithSampleValues\\(\\) should return Drupal\\\\Core\\\\Entity\\\\FieldableEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/KeyValueStore/KeyValueContentEntityStorage.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Plugin/Validation/Constraint/ReferenceAccessConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Plugin/Validation/Constraint/ValidReferenceConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\Query\\\\QueryBase\\:\\:getClass\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Query/QueryBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$next_index_prefix might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Query/Sql/Tables.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$relationship_specifier might not be defined\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Query/Sql/Tables.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorage.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$revision_query might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorage.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorage.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$initial_storage_value in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorageSchema.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method getFromDriverName\\(\\) of class Drupal\\\\Core\\\\Extension\\\\DatabaseDriverList\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  DatabaseDriverList\\:\\:get\\(\\) instead, passing a database driver namespace\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Extension/DatabaseDriverList.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$minor_version might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Extension/ExtensionVersion.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$callback in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldDefinition.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\FieldItemBase\\:\\:generateSampleValue\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\FieldItemBase\\:\\:postSave\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\FieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemList.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$values might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemList.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldTypePluginManager.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldTypePluginManager.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\Plugin\\\\Field\\\\FieldType\\\\EntityReferenceItem\\:\\:generateSampleValue\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldType/EntityReferenceItem.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\Field\\\\Plugin\\\\Field\\\\FieldType\\\\NumericItemBase\\:\\:\\$value\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldType/NumericItemBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\FileTransfer\\\\FileTransfer\\:\\:__get\\(\\) should return bool\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/FileTransfer/FileTransfer.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Form\\\\FormBuilder\\:\\:setInvalidTokenError\\(\\) should return \\$this\\(Drupal\\\\Core\\\\Form\\\\FormBuilder\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormBuilder.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$form_id in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormBuilder.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$input in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormBuilder.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$form in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormCache.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Form\\\\FormValidator\\:\\:setInvalidTokenError\\(\\) should return \\$this\\(Drupal\\\\Core\\\\Form\\\\FormValidator\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$is_empty_multiple might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$is_empty_null might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$is_empty_string might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\KeyValueStore\\\\NullStorageExpirable\\:\\:setIfNotExists\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/KeyValueStore/NullStorageExpirable.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\KeyValueStore\\\\NullStorageExpirable\\:\\:setWithExpireIfNotExists\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/KeyValueStore/NullStorageExpirable.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Lock\\\\NullLockBackend\\:\\:wait\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Lock/NullLockBackend.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$parent in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/Form/MenuLinkDefaultForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Menu\\\\MenuLinkManager\\:\\:getInstance\\(\\) should return object\\|false but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Menu\\\\MenuLinkManager\\:\\:menuNameInUse\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuTreeStorage.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuTreeStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Plugin/DefaultPluginManager.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Queue\\\\Memory has an unused parameter \\$name\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Queue/Memory.php',
];
$ignoreErrors[] = [
	// identifier: class.implementsDeprecatedInterface
	'message' => '#^Class Drupal\\\\Core\\\\Queue\\\\QueueFactory implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Queue/QueueFactory.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Queue\\\\QueueFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Queue/QueueFactory.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$sort in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/Element/RenderElementBase.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$output in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/MainContent/AjaxRenderer.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$page_bottom in empty\\(\\) always exists and is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/MainContent/HtmlRenderer.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$page_top in empty\\(\\) always exists and is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/MainContent/HtmlRenderer.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$elements in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/Renderer.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Routing/MatcherDumper.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$current might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Site/SettingsEditor.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$index might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Core/Site/SettingsEditor.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Template\\\\AttributeValueBase\\:\\:render\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Template/AttributeValueBase.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Template\\\\TwigEnvironment has an unused parameter \\$root\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Template/TwigEnvironment.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Test\\\\TestRunnerKernel has an unused parameter \\$allow_dumping\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Test/TestRunnerKernel.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Theme\\\\ThemeInitialization\\:\\:resolveStyleSheetPlaceholders\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ThemeInitialization.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$candidate might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ThemeManager.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$theme_engine in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ThemeManager.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\TypedData\\\\ListDataDefinition\\:\\:setDataType\\(\\) should return static\\(Drupal\\\\Core\\\\TypedData\\\\ListDataDefinition\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/TypedData/ListDataDefinition.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\TypedData\\\\TypedData\\:\\:\\$value\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/TypedData/TypedData.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$cache_key in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/TypedData/Validation/RecursiveContextualValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Update\\\\UpdateKernel\\:\\:discoverServiceProviders\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Update/UpdateKernel.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\Core\\\\Updater\\\\Module\\:\\:postUpdateTasks\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Updater/Module.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\action\\\\Form\\\\ActionFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/action/src/Form/ActionFormBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$position might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/modules/block/tests/src/Functional/BlockRenderOrderTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$edit might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block/tests/src/Functional/BlockUiTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$dependency in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/src/BlockContentAccessControlHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\block_content\\\\BlockContentForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/block_content/src/BlockContentForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\block_content\\\\BlockContentTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/src/BlockContentTypeForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$loaded might not be defined\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/modules/block_content/tests/src/Functional/BlockContentRevisionsTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$callable in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/BookExport.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\book\\\\BookOutline\\:\\:nextLink\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/BookOutline.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\book\\\\BookOutline\\:\\:prevLink\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/BookOutline.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\book\\\\Form\\\\BookOutlineForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/book/src/Form/BookOutlineForm.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$ghs_config_classes in empty\\(\\) always exists and is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/ckeditor5/src/Plugin/Validation/Constraint/StyleSensibleElementConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\comment\\\\CommentForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/CommentForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\comment\\\\CommentTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/CommentTypeForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$state might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/Plugin/migrate/destination/EntityComment.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\comment\\\\Plugin\\\\views\\\\field\\\\NodeNewComments\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/Plugin/views/field/NodeNewComments.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$comment in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/Plugin/views/row/Rss.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$position might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentBlockTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$comment_values might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentLanguageTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$mode_text might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$data might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/config/src/Form/ConfigSingleImportForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\contact\\\\ContactFormEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contact/src/ContactFormEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\contact\\\\Entity\\\\Message\\:\\:getPersonalRecipient\\(\\) should return Drupal\\\\user\\\\UserInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contact/src/Entity/Message.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\contact\\\\MessageForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/contact/src/MessageForm.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$state in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/content_moderation/src/Form/ContentModerationStateForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\content_moderation\\\\ModerationInformation\\:\\:getAffectedRevisionTranslation\\(\\) should return Drupal\\\\Core\\\\Entity\\\\ContentEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_moderation/src/ModerationInformation.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\content_moderation\\\\ModerationInformation\\:\\:getDefaultRevisionId\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_moderation/src/ModerationInformation.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$checkbox_id might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/content_translation.admin.inc',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$locked_languages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/content_translation.admin.inc',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$locked_languages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/content_translation.module',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$source_name might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/src/Controller/ContentTranslationController.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$unrestricted_tab_count might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contextual/tests/src/FunctionalJavascript/EditModeTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\datetime\\\\Plugin\\\\Field\\\\FieldType\\\\DateTimeFieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Field/FieldType/DateTimeFieldItemList.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$item in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Validation/Constraint/DateTimeFormatConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\datetime_range\\\\Plugin\\\\Field\\\\FieldType\\\\DateRangeFieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeFieldItemList.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\datetime_range\\\\Plugin\\\\Field\\\\FieldType\\\\DateRangeItem\\:\\:\\$end_date\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeItem.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\datetime_range\\\\Plugin\\\\Field\\\\FieldType\\\\DateRangeItem\\:\\:\\$start_date\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeItem.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$view in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/dblog/dblog.module',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$items in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/modules/field_test/src/Plugin/Field/FieldFormatter/TestFieldEmptySettingFormatter.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$items in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/modules/field_test/src/Plugin/Field/FieldFormatter/TestFieldMultipleFormatter.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$values might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/src/Kernel/FieldAttachStorageTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$entity_display might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Access/FormModeAccessCheck.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$entity_display might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Access/ViewModeAccessCheck.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\field_ui\\\\FieldUI\\:\\:getOverviewRouteInfo\\(\\) should return Drupal\\\\Core\\\\Url but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/FieldUI.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\field_ui\\\\Form\\\\EntityDisplayModeFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/EntityDisplayModeFormBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\field_ui\\\\Form\\\\FieldConfigEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/FieldConfigEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\field_ui\\\\Form\\\\FieldStorageConfigEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/FieldStorageConfigEditForm.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$file_upload in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/file.module',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$message might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/file.module',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$rows in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/src/Plugin/Field/FieldFormatter/TableFormatter.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\file\\\\Plugin\\\\Field\\\\FieldType\\\\FileFieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/src/Plugin/Field/FieldType/FileFieldItemList.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\file\\\\Plugin\\\\Field\\\\FieldType\\\\FileFieldItemList\\:\\:postSave\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/file/src/Plugin/Field/FieldType/FileFieldItemList.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$unexpected in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileManagedTestBase.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\file\\\\Functional\\\\FileUploadJsonBasicAuthTest\\:\\:getExpectedUnauthorizedEntityAccessCacheability\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileUploadJsonBasicAuthTest.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\file\\\\Functional\\\\FileUploadJsonCookieTest\\:\\:getExpectedUnauthorizedEntityAccessCacheability\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileUploadJsonCookieTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$unexpected in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Kernel/FileManagedUnitTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$rows might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/filter/src/Plugin/Filter/FilterHtml.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\forum\\\\ForumManager\\:\\:getTopicOrder\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/forum/src/ForumManager.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$vocabulary in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/forum/src/ForumUninstallValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\history\\\\Plugin\\\\views\\\\field\\\\HistoryUserTimestamp\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/history/src/Plugin/views/field/HistoryUserTimestamp.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$image_style in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Controller/ImageStyleDownloadController.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$lock_name might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Controller/ImageStyleDownloadController.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\image\\\\Form\\\\ImageStyleEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Form/ImageStyleEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\image\\\\Form\\\\ImageStyleFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Form/ImageStyleFormBase.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\image\\\\Plugin\\\\Field\\\\FieldType\\\\ImageItem\\:\\:\\$height\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/image/src/Plugin/Field/FieldType/ImageItem.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\image\\\\Plugin\\\\Field\\\\FieldType\\\\ImageItem\\:\\:\\$width\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/image/src/Plugin/Field/FieldType/ImageItem.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$image_that_is_too_small_file might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/image/tests/src/Functional/ImageFieldValidateTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$edit in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/tests/src/Functional/ImageStyleFlushTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$reason in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/Context/FieldResolver.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\jsonapi\\\\JsonApiResource\\\\ResourceIdentifier\\:\\:getDataReferencePropertyName\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/JsonApiResource/ResourceIdentifier.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$entity in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/Normalizer/EntityAccessDeniedHttpExceptionNormalizer.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\jsonapi\\\\Revisions\\\\VersionNegotiator\\:\\:getRevision\\(\\) should return Drupal\\\\Core\\\\Entity\\\\EntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/Revisions/VersionNegotiator.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$created_entity might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$dynamic_cache might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$parseable_invalid_request_body might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$reason in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$id in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Unit/Normalizer/JsonApiDocumentTopLevelNormalizerTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\language\\\\Form\\\\LanguageAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/Form/LanguageAddForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\language\\\\Form\\\\LanguageEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/Form/LanguageEditForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$key might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/language/src/Form/NegotiationBrowserForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$method_id might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/LanguageNegotiator.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$langcode might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/Plugin/LanguageNegotiation/LanguageNegotiationContentEntity.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\link\\\\Plugin\\\\migrate\\\\process\\\\FieldLink has an unused parameter \\$migration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/link/src/Plugin/migrate/process/FieldLink.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Function locale_config_batch_update_components\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/locale.bulk.inc',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$item in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/Form/ExportForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\locale\\\\LocaleDefaultConfigStorage\\:\\:read\\(\\) should return array but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/locale/src/LocaleDefaultConfigStorage.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\locale\\\\LocaleProjectStorage\\:\\:deleteAll\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/LocaleProjectStorage.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\locale\\\\PoDatabaseReader\\:\\:readItem\\(\\) should return Drupal\\\\Component\\\\Gettext\\\\PoItem but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/PoDatabaseReader.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\locale\\\\PoDatabaseWriter\\:\\:importString\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/PoDatabaseWriter.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$plural in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/PoDatabaseWriter.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\locale\\\\StringDatabaseStorage\\:\\:dbStringTable\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/StringDatabaseStorage.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$error in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/media.install',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\media\\\\MediaTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/src/MediaTypeForm.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$source in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/src/MediaTypeForm.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.variable
	'message' => '#^Variable \\$resource_url on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/src/OEmbed/UrlResolver.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$jpg_image might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media_library/tests/src/FunctionalJavascript/EmbeddedFormWidgetTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\menu_link_content\\\\Form\\\\MenuLinkContentForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/menu_link_content/src/Form/MenuLinkContentForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$uuid might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/menu_link_content/src/Plugin/Menu/MenuLinkContent.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\menu_ui\\\\MenuForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/menu_ui/src/MenuForm.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\migrate\\\\MigrateException has an unused parameter \\$code\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/MigrateException.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\migrate\\\\MigrateException has an unused parameter \\$previous\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/MigrateException.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.deprecated
	'message' => '#^Call to deprecated method registerLoader\\(\\) of class Doctrine\\\\Common\\\\Annotations\\\\AnnotationRegistry\\:
This method is deprecated and will be removed in
            doctrine/annotations 2\\.0\\. Annotations will be autoloaded in 2\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/Discovery/AnnotatedClassDiscoveryAutomatedProviders.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateDestinationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateSourcePluginManager.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\migrate\\\\Plugin\\\\MigrationPluginManager has an unused parameter \\$language_manager\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrationPluginManager.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\ComponentEntityDisplayBase\\:\\:fields\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/ComponentEntityDisplayBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\Config\\:\\:fields\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/Config.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\Entity\\:\\:fields\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/Entity.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$config might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/EntityConfigBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\NullDestination\\:\\:import\\(\\) should return array\\|bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/NullDestination.php',
];
$ignoreErrors[] = [
	// identifier: phpunit.dataProviderMethod
	'message' => '#^@dataProvider providerSource related method not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/tests/src/Kernel/MigrateSourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: phpunit.dataProviderMethod
	'message' => '#^@dataProvider providerSource related method not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/tests/src/Kernel/MigrateSqlSourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method Drupal\\\\Tests\\\\migrate\\\\Kernel\\\\MigrateTestBase\\:\\:migrateDumpAlter\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/tests/src/Kernel/MigrateTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$sub_process_plugins might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/migrate/tests/src/Unit/process/SubProcessTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/MigrationPluginManager.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/NodeMigrateType.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\migrate_drupal\\\\Plugin\\\\migrate\\\\EntityReferenceTranslationDeriver has an unused parameter \\$base_plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/Plugin/migrate/EntityReferenceTranslationDeriver.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\migrate_drupal\\\\Plugin\\\\migrate\\\\source\\\\ContentEntityDeriver has an unused parameter \\$base_plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/Plugin/migrate/source/ContentEntityDeriver.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Kernel/StateFileExistsTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Kernel/d6/ValidateMigrationStateTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Kernel/d7/ValidateMigrationStateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrateFieldPluginManagerTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrationConfigurationTraitTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$statement might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrationConfigurationTraitTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$version might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Form/CredentialForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Form/MigrateUpgradeFormBase.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\CredentialFormTest\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/CredentialFormTest.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MigrateUpgradeExecuteTestBase\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeExecuteTestBase.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MigrateUpgradeFormStepsTest\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeFormStepsTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeFormStepsTest.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MigrateUpgradeTestBase\\:\\:getManagedFiles\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeTestBase.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MultilingualReviewPageTestBase\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MultilingualReviewPageTestBase.php',
];
$ignoreErrors[] = [
	// identifier: property.deprecated
	'message' => '#^Access to deprecated property \\$needsCleanup of class Drupal\\\\mysql\\\\Driver\\\\Database\\\\mysql\\\\Connection\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. There\'s no
   replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/src/Driver/Database/mysql/Connection.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$last_insert_id might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/src/Driver/Database/mysql/Insert.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$string_ascii_check might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/tests/src/Kernel/mysql/SchemaTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$string_check might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/tests/src/Kernel/mysql/SchemaTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\node\\\\ConfigTranslation\\\\NodeTypeMapper\\:\\:setEntity\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/node/src/ConfigTranslation/NodeTypeMapper.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\node\\\\NodeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/node/src/NodeForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\node\\\\NodeGrantDatabaseStorage\\:\\:alterQuery\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/NodeGrantDatabaseStorage.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\node\\\\NodeTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/NodeTypeForm.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$node in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/Plugin/views/row/Rss.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$changed in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Kernel/Migrate/d7/MigrateNodeTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListFloatItem\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListFloatItem.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListIntegerItem\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListIntegerItem.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListItemBase\\:\\:\\$value\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListItemBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListItemBase\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListItemBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListStringItem\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListStringItem.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\path\\\\PathAliasForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/path/src/PathAliasForm.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:\\$alias\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:\\$langcode\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:\\$pid\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:postSave\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method makeSequenceName\\(\\) of class Drupal\\\\Core\\\\Database\\\\Connection\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is
  no replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Driver/Database/pgsql/Schema.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$table_field might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Driver/Database/pgsql/Select.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\pgsql\\\\Driver\\\\Database\\\\pgsql\\\\Upsert\\:\\:execute\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Driver/Database/pgsql/Upsert.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method makeSequenceName\\(\\) of class Drupal\\\\Core\\\\Database\\\\Connection\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is
  no replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Update10101.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$responsive_image_styles in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/responsive_image/src/Plugin/Field/FieldFormatter/ResponsiveImageFormatter.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\responsive_image\\\\ResponsiveImageStyleForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/responsive_image/src/ResponsiveImageStyleForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$created_entity might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/modules/rest/tests/src/Functional/EntityResource/EntityResourceTestBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$working_to might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/search.module',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\search\\\\Form\\\\SearchPageAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/Form/SearchPageAddForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\search\\\\Form\\\\SearchPageEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/Form/SearchPageEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\search\\\\Form\\\\SearchPageFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/Form/SearchPageFormBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\search\\\\SearchPageRepository\\:\\:setDefaultSearchPage\\(\\) should return static\\(Drupal\\\\search\\\\SearchPageRepository\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/SearchPageRepository.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\serialization\\\\Normalizer\\\\EntityNormalizer\\:\\:getCustomSerializedPropertyNames\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/serialization/src/Normalizer/EntityNormalizer.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\shortcut\\\\Form\\\\SetCustomize\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/shortcut/src/Form/SetCustomize.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\shortcut\\\\ShortcutForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/shortcut/src/ShortcutForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\shortcut\\\\ShortcutSetForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/shortcut/src/ShortcutSetForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$args might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/sqlite/src/Driver/Database/sqlite/Connection.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$statement might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/sqlite/src/Driver/Database/sqlite/Connection.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\system\\\\Controller\\\\BatchController\\:\\:batchPage\\(\\) should return array\\|Symfony\\\\Component\\\\HttpFoundation\\\\Response but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Controller/BatchController.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\system\\\\Form\\\\DateFormatFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/DateFormatFormBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\system\\\\Form\\\\ModulesListNonStableConfirmForm\\:\\:getQuestion\\(\\) should return Drupal\\\\Core\\\\StringTranslation\\\\TranslatableMarkup but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/ModulesListNonStableConfirmForm.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$default_theme in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/ThemeSettingsForm.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$theme_settings in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Plugin/migrate/destination/d7/ThemeSettings.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$violation_messages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/SecurityAdvisories/SecurityAdvisory.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$directories might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.install',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$pdo_message might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.install',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$site_path might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.install',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecated
	'message' => '#^Fetching deprecated class constant EXISTS_RENAME of interface Drupal\\\\Core\\\\File\\\\FileSystemInterface\\:
in drupal\\:10\\.3\\.0 and is removed from drupal\\:12\\.0\\.0\\. Use
\\\\Drupal\\\\Core\\\\File\\\\FileExists\\:\\:Rename instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.module',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$messages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/batch_test/batch_test.callbacks.inc',
];
$ignoreErrors[] = [
	// identifier: function.deprecated
	'message' => '#^Call to deprecated function deprecation_test_function\\(\\)\\:
in drupal\\:8\\.4\\.0 and is removed from drupal\\:9\\.0\\.0\\. This is
  the deprecation message for deprecated_test_function\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/deprecation_test/src/DeprecatedController.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\lazy_route_provider_install_test\\\\PluginManager has an unused parameter \\$cache_backend\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\lazy_route_provider_install_test\\\\PluginManager has an unused parameter \\$url_generator\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/module_test/src/PluginManagerCacheClearer.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/DefaultsTestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/MockBlockManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$form_output in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Form/FormTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$parent might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Menu/BreadcrumbTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$test_meta in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Render/HtmlResponseAttachmentsTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$expected_required_list_items might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Theme/ThemeUiTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\taxonomy\\\\Plugin\\\\migrate\\\\source\\\\d7\\\\TermTranslation\\:\\:prepareRow\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Plugin/migrate/source/d7/TermTranslation.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$inner_count might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Plugin/views/argument/IndexTidDepth.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$inner_count might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Plugin/views/filter/TaxonomyIndexTidDepth.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\taxonomy\\\\TermForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/TermForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\taxonomy\\\\TermForm\\:\\:validateForm\\(\\) should return Drupal\\\\Core\\\\Entity\\\\ContentEntityInterface but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/taxonomy/src/TermForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\taxonomy\\\\VocabularyForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/VocabularyForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\tour\\\\TipPluginBase\\:\\:get\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/tour/src/TipPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.variable
	'message' => '#^Variable \\$location on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/tour/src/TipPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$violation_messages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/update/src/ProjectRelease.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$users might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Controller/UserAuthenticationController.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$route_object might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/LanguageNegotiation/LanguageNegotiationUserAdmin.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$account in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/Validation/Constraint/ProtectedUserFieldConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$account in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/Validation/Constraint/UserMailRequiredValidator.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$items in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/Validation/Constraint/UserMailRequiredValidator.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\user\\\\Plugin\\\\views\\\\field\\\\UserData\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/views/field/UserData.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\user\\\\ProfileForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/ProfileForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\user\\\\RegisterForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/modules/user/src/RegisterForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\user\\\\RoleForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/RoleForm.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$name in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserLoginHttpTest.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$pass in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserLoginHttpTest.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\user\\\\Functional\\\\UserRegistrationRestTest\\:\\:getExpectedUnauthorizedEntityAccessCacheability\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserRegistrationRestTest.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$result in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Unit/UserAccessControlHandlerTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Form\\\\ViewsFormMainForm\\:\\:getFormId\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Form/ViewsFormMainForm.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$display in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/Derivative/ViewsBlock.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method \\$this\\(Drupal\\\\views\\\\Plugin\\\\views\\\\HandlerBase\\)\\:\\:getFormula\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/HandlerBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\HandlerBase\\:\\:getTableJoin\\(\\) should return Drupal\\\\views\\\\Plugin\\\\views\\\\join\\\\JoinPluginBase but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/HandlerBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$group_types might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/HandlerBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/area/Broken.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\area\\\\HTTPStatusCode\\:\\:render\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/area/HTTPStatusCode.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$validate_types might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/ArgumentPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/Broken.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\cache\\\\CachePluginBase\\:\\:cacheGet\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/cache/CachePluginBase.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\views\\\\Plugin\\\\views\\\\display\\\\DisplayPluginBase has an unused parameter \\$configuration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/DisplayPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$pager in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/DisplayPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$style in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/DisplayPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$access_plugin in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/PathPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$options might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Boolean.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Broken.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$entity in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/BulkForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\Date\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Date.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$custom_format might not be defined\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Date.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\EntityField\\:\\:renderItems\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/EntityField.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$field_item_list in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/EntityField.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$options in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/FieldPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$raw_items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/FieldPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$style in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/FieldPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\Markup\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Markup.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\PrerenderList\\:\\:renderItems\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/PrerenderList.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/Broken.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$groups might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/FilterPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$source might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/InOperator.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$source might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/NumericFilter.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$source might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/StringFilter.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$left in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/join/JoinPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$join in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/query/Sql.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/relationship/Broken.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$sort_field might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/relationship/GroupwiseMax.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$sort_table might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/relationship/GroupwiseMax.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/sort/Broken.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$plugin in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/style/StylePluginBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$created_column in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$default_field might not be defined\\.$#',
	'count' => 6,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$sorts in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$style_plugin in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$view in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$view in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/modules/views_test_data/views_test_data.views_execution.inc',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$link might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/TaxonomyGlossaryTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$rand1 might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Plugin/StyleTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$rand2 might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Plugin/StyleTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$rand3 might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Plugin/StyleTest.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$relationship_handler in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/Form/Ajax/ConfigHandler.php',
];
$ignoreErrors[] = [
	// identifier: unset.offset
	'message' => '#^Cannot unset offset \'\\#title\' on array\\{\\#theme_wrappers\\: array\\{\'container\'\\}, \\#attributes\\: array\\{class\\: array\\{\'scroll\'\\}, data\\-drupal\\-views\\-scroll\\: true\\}\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/Form/Ajax/Display.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewEditForm.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$group_info might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views_ui/src/ViewEditForm.php',
];
$ignoreErrors[] = [
	// identifier: empty.variable
	'message' => '#^Variable \\$display_plugin in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewFormBase.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewUI\\:\\:getTypedData\\(\\) should return Drupal\\\\Core\\\\TypedData\\\\ComplexDataInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewUI.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewUI\\:\\:set\\(\\) should return \\$this\\(Drupal\\\\views_ui\\\\ViewUI\\) but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views_ui/src/ViewUI.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewUI\\:\\:setSyncing\\(\\) should return \\$this\\(Drupal\\\\views_ui\\\\ViewUI\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewUI.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$message in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/tests/src/FunctionalJavascript/PreviewTest.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowStateAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowStateAddForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowStateEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowStateEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowTransitionAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowTransitionAddForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowTransitionEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowTransitionEditForm.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workspaces\\\\EntityTypeInfo\\:\\:entityBaseFieldInfo\\(\\) should return array\\<Drupal\\\\Core\\\\Field\\\\FieldDefinitionInterface\\> but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/EntityTypeInfo.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workspaces\\\\Form\\\\WorkspaceForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/workspaces/src/Form/WorkspaceForm.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/Plugin/Validation/Constraint/DeletedWorkspaceConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$entity in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/Plugin/Validation/Constraint/EntityWorkspaceConflictConstraintValidator.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspaceAssociation.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workspaces\\\\WorkspaceMerger\\:\\:checkConflictsOnTarget\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspaceMerger.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspaceMerger.php',
];
$ignoreErrors[] = [
	// identifier: return.missing
	'message' => '#^Method Drupal\\\\workspaces\\\\WorkspacePublisher\\:\\:checkConflictsOnTarget\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspacePublisher.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$keyed_content might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/profiles/demo_umami/modules/demo_umami_content/src/InstallHelper.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$structured_content might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/profiles/demo_umami/modules/demo_umami_content/src/InstallHelper.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$project_stabilities might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Composer/Template/ComposerProjectTemplatesTest.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\ClassRequiresAvailable\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\ClassRequiresUnavailable\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\MethodRequires\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\UsesCommandRequirements\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$found might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Cache/ApcuBackendTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Config/ConfigInstallTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Config/ConfigInstallTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$expected_driver might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/DriverSpecificKernelTestBase.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 3,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/StatementTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectErrorMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 3,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/StatementTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$title might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/CreateSampleEntityTest.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$previous_untranslatable_field_value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/EntityDecoupledTranslationRevisionsTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$e might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/FieldableEntityDefinitionUpdateTest.php',
];
$ignoreErrors[] = [
	// identifier: variable.undefined
	'message' => '#^Variable \\$new_field_schema_data might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/FieldableEntityDefinitionUpdateTest.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/KernelTestBase.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\TestSite\\\\Commands\\\\TestSiteInstallCommand\\:\\:assertTrue\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/TestSite/Commands/TestSiteInstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Drupal\\\\TestSite\\\\Commands\\\\TestSiteInstallCommand\\:\\:fail\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/TestSite/Commands/TestSiteInstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method getConfig\\(\\) of class GuzzleHttp\\\\Client\\:
Client\\:\\:getConfig will be removed in guzzlehttp/guzzle\\:8\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/BrowserTestBase.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.deprecated
	'message' => '#^Call to deprecated method registerAutoloadNamespace\\(\\) of class Doctrine\\\\Common\\\\Annotations\\\\AnnotationRegistry\\:
This method is deprecated and will be removed in
            doctrine/annotations 2\\.0\\. Annotations will be autoloaded in 2\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Annotation/Doctrine/DocParserTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Symfony\\\\Component\\\\ExpressionLanguage\\\\Expression has an unused parameter \\$expression\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/DependencyInjection/Dumper/OptimizedPhpArrayDumperTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$charismatic\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$delightful\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$demure\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$electrostatic\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsConfigArrayKey has an unused parameter \\$config_name\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$configuration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$foo\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$plugin_definition\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$what_am_i_doing_here\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsPluginId has an unused parameter \\$plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/StubPluginManagerBaseWithMapper.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.void
	'message' => '#^Result of static method Drupal\\\\Composer\\\\Composer\\:\\:ensureComposerVersion\\(\\) \\(void\\) is used\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Composer/ComposerTest.php',
];
$ignoreErrors[] = [
	// identifier: class.implementsDeprecatedInterface
	'message' => '#^Class Drupal\\\\Tests\\\\Core\\\\Controller\\\\MockContainerAware implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Controller/ControllerResolverTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Database/ConditionTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method findCaller\\(\\) of class Drupal\\\\Core\\\\Database\\\\Log\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  Connection\\:\\:findCallerFromDebugBacktrace\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Database/Stub/StubConnection.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.deprecatedClass
	'message' => '#^Fetching class constant class of deprecated class Drupal\\\\Core\\\\Database\\\\StatementWrapper\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  \\\\Drupal\\\\Core\\\\Database\\\\StatementWrapperIterator instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Database/Stub/StubConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.implementsDeprecatedInterface
	'message' => '#^Class Drupal\\\\Tests\\\\Core\\\\DependencyInjection\\\\DependencySerializationTestDummy implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/DependencyInjection/DependencySerializationTest.php',
];
$ignoreErrors[] = [
	// identifier: phpunit.mockMethod
	'message' => '#^Trying to mock an undefined method getRevisionId\\(\\) on class Drupal\\\\Tests\\\\Core\\\\Entity\\\\UrlTestEntity\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Entity/EntityUrlTest.php',
];
$ignoreErrors[] = [
	// identifier: phpunit.mockMethod
	'message' => '#^Trying to mock an undefined method isDefaultRevision\\(\\) on class Drupal\\\\Tests\\\\Core\\\\Entity\\\\UrlTestEntity\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Entity/EntityUrlTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/EventSubscriber/SpecialAttributesRouteSubscriberTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/EventSubscriber/SpecialAttributesRouteSubscriberTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method getConfig\\(\\) of class GuzzleHttp\\\\Client\\:
Client\\:\\:getConfig will be removed in guzzlehttp/guzzle\\:8\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Http/ClientFactoryTest.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Core\\\\Plugin\\\\Discovery\\\\TestContainerDerivativeDiscovery has an unused parameter \\$example_service\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/Discovery/TestContainerDerivativeDiscovery.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/FilteredPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	// identifier: constructor.unusedParameter
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Core\\\\Plugin\\\\TestPluginManager has an unused parameter \\$namespaces\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	// identifier: isset.variable
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Test/AssertContentTraitTest.php',
];
$ignoreErrors[] = [
	// identifier: includeOnce.fileNotFound
	'message' => '#^Path in include_once\\(\\) "vfs\\://drupal/sites/default/modules/module_a/module_a\\.post_update\\.php" is not a file or it does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Update/UpdateRegistryTest.php',
];
$ignoreErrors[] = [
	// identifier: includeOnce.fileNotFound
	'message' => '#^Path in include_once\\(\\) "vfs\\://drupal/sites/default/modules/module_b/module_b\\.post_update\\.php" is not a file or it does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Update/UpdateRegistryTest.php',
];
$ignoreErrors[] = [
	// identifier: class.implementsDeprecatedInterface
	'message' => '#^Class Drupal\\\\Tests\\\\Core\\\\Utility\\\\MockContainerAware implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Utility/CallableResolverTest.php',
];
$ignoreErrors[] = [
	// identifier: method.deprecated
	'message' => '#^Call to deprecated method getConfig\\(\\) of interface GuzzleHttp\\\\ClientInterface\\:
ClientInterface\\:\\:getConfig will be removed in guzzlehttp/guzzle\\:8\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/DrupalTestBrowser.php',
];
$ignoreErrors[] = [
	// identifier: class.implementsDeprecatedInterface
	'message' => '#^Class Drupal\\\\Tests\\\\Listeners\\\\DrupalListener implements deprecated interface PHPUnit\\\\Framework\\\\TestListener\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Listeners/DrupalListener.php',
];
$ignoreErrors[] = [
	// identifier: traitUse.deprecated
	'message' => '#^Usage of deprecated trait PHPUnit\\\\Framework\\\\TestListenerDefaultImplementation in class Drupal\\\\Tests\\\\Listeners\\\\DrupalListener\\:
The `TestListener` interface is deprecated$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Listeners/DrupalListener.php',
];
$ignoreErrors[] = [
	// identifier: class.extendsDeprecatedClass
	'message' => '#^Class Drupal\\\\Tests\\\\TestSuites\\\\BuildTestSuite extends deprecated class Drupal\\\\Tests\\\\TestSuites\\\\TestSuiteBase\\:
in drupal\\:10\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement and test discovery will be handled differently in PHPUnit 10\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestSuites/BuildTestSuite.php',
];
$ignoreErrors[] = [
	// identifier: class.extendsDeprecatedClass
	'message' => '#^Class Drupal\\\\Tests\\\\TestSuites\\\\FunctionalJavascriptTestSuite extends deprecated class Drupal\\\\Tests\\\\TestSuites\\\\TestSuiteBase\\:
in drupal\\:10\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement and test discovery will be handled differently in PHPUnit 10\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestSuites/FunctionalJavascriptTestSuite.php',
];
$ignoreErrors[] = [
	// identifier: class.extendsDeprecatedClass
	'message' => '#^Class Drupal\\\\Tests\\\\TestSuites\\\\FunctionalTestSuite extends deprecated class Drupal\\\\Tests\\\\TestSuites\\\\TestSuiteBase\\:
in drupal\\:10\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement and test discovery will be handled differently in PHPUnit 10\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestSuites/FunctionalTestSuite.php',
];
$ignoreErrors[] = [
	// identifier: class.extendsDeprecatedClass
	'message' => '#^Class Drupal\\\\Tests\\\\TestSuites\\\\KernelTestSuite extends deprecated class Drupal\\\\Tests\\\\TestSuites\\\\TestSuiteBase\\:
in drupal\\:10\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement and test discovery will be handled differently in PHPUnit 10\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestSuites/KernelTestSuite.php',
];
$ignoreErrors[] = [
	// identifier: class.extendsDeprecatedClass
	'message' => '#^Class Drupal\\\\Tests\\\\TestSuites\\\\UnitTestSuite extends deprecated class Drupal\\\\Tests\\\\TestSuites\\\\TestSuiteBase\\:
in drupal\\:10\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement and test discovery will be handled differently in PHPUnit 10\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestSuites/UnitTestSuite.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$render_start might not be defined\\.$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/Renderer.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
