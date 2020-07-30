<?php

/**
 * @file
 * API documentation for Workflows module.
 */

/**
 * @defgroup workflow_type_plugins Workflow Type Plugins
 * @{
 * Any module harnessing the Workflows module must define a Workflow Type
 * Plugin. This allows the module to tailor the workflow to its specific need.
 * For example, the Content Moderation module uses its Workflow Type Plugin to
 * link workflows to entities.
 * On their own, workflows are a stand-alone concept. It takes a module such as
 * Content Moderation to give the workflow context.
 * @}
 */
