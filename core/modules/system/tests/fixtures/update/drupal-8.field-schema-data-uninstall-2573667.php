<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2573667.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.body',
    'value' => 'a:2:{s:19:"block_content__body";a:4:{s:11:"description";s:42:"Data storage for block_content field body.";s:6:"fields";a:9:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:10:"body_value";a:3:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:1;}s:12:"body_summary";a:3:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;}s:11:"body_format";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;s:8:"not null";b:0;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:3:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:11:"body_format";a:1:{i:0;s:11:"body_format";}}}s:28:"block_content_revision__body";a:4:{s:11:"description";s:54:"Revision archive storage for block_content field body.";s:6:"fields";a:9:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:10:"body_value";a:3:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:1;}s:12:"body_summary";a:3:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;}s:11:"body_format";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;s:8:"not null";b:0;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:3:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:11:"body_format";a:1:{i:0;s:11:"body_format";}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.changed',
    'value' => 'a:2:{s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:7:"changed";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:7:"changed";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.default_langcode',
    'value' => 'a:2:{s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:16:"default_langcode";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:16:"default_langcode";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.id',
    'value' => 'a:4:{s:13:"block_content";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:22:"block_content_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.info',
    'value' => 'a:2:{s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:4:"info";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;s:8:"not null";b:0;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:4:"info";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.langcode',
    'value' => 'a:4:{s:13:"block_content";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:12;s:8:"is_ascii";b:1;s:8:"not null";b:1;}}}s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:12;s:8:"is_ascii";b:1;s:8:"not null";b:1;}}}s:22:"block_content_revision";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:12;s:8:"is_ascii";b:1;s:8:"not null";b:1;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:12;s:8:"is_ascii";b:1;s:8:"not null";b:1;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.revision_id',
    'value' => 'a:4:{s:13:"block_content";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:22:"block_content_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.revision_log',
    'value' => 'a:1:{s:22:"block_content_revision";a:1:{s:6:"fields";a:1:{s:12:"revision_log";a:3:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.revision_translation_affected',
    'value' => 'a:2:{s:24:"block_content_field_data";a:1:{s:6:"fields";a:1:{s:29:"revision_translation_affected";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}s:28:"block_content_field_revision";a:1:{s:6:"fields";a:1:{s:29:"revision_translation_affected";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.type',
    'value' => 'a:2:{s:13:"block_content";a:2:{s:6:"fields";a:1:{s:4:"type";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:36:"block_content_field__type__target_id";a:1:{i:0;s:4:"type";}}}s:24:"block_content_field_data";a:2:{s:6:"fields";a:1:{s:4:"type";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:36:"block_content_field__type__target_id";a:1:{i:0;s:4:"type";}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'block_content.field_schema_data.uuid',
    'value' => 'a:1:{s:13:"block_content";a:2:{s:6:"fields";a:1:{s:4:"uuid";a:4:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:1;}}s:11:"unique keys";a:1:{s:32:"block_content_field__uuid__value";a:1:{i:0;s:4:"uuid";}}}}',
  ])
  ->execute();
