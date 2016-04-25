<?php

namespace Drupal\Core\Block;

/**
 * The interface for "messages" (#type => status_messages) blocks.
 *
 * @see drupal_set_message()
 * @see drupal_get_message()
 * @see \Drupal\Core\Render\Element\StatusMessages
 * @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
 *
 * @ingroup block_api
 */
interface MessagesBlockPluginInterface extends BlockPluginInterface { }
