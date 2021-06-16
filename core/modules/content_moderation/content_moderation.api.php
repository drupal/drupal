<?php

/**
 * @file
 * API documentation for Content Moderation module.
 */

/**
 * @defgroup content_moderation_plugin Content Moderation Workflow Type Plugin
 * @{
 * The Workflow Type plugin implemented by Content Moderation links revisionable
 * entities to workflows.
 *
 * In the Content Moderation Workflow Type Plugin, one method requires the
 * entity object to be passed in as a parameter, even though the interface
 * defined by Workflows module doesn't require this:
 * @code
 * $workflow_type_plugin->getInitialState($entity);
 * @endcode
 * This is used to determine the initial moderation state based on the
 * publishing status of the entity.
 * @}
 */
