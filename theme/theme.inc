<?php

/**
 * @file
 * Theme functions.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Theme function for the compound navigation block parts.
 */
function template_preprocess_islandora_compound_prev_next(array &$variables) {
  $themed_siblings = [];
  $image_path = drupal_get_path('module', 'islandora');
  $folder_image_path = "$image_path/images/folder.png";

  // If the parent hasModel islandora:compoundCModel,
  // the first child is typically identical to the parent;
  // do not show the parent TN.
  // If the parent does not have hasModel islandora:compoundCModel,
  // the first child and parent are different;
  // show the parent TN.
  $variables['parent_tn'] = NULL;
  $parent_object = islandora_object_load($variables['parent_pid']);
  if ($parent_object && !in_array(ISLANDORA_COMPOUND_OBJECT_CMODEL, $parent_object->models)) {
    if (isset($parent_object['TN']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $parent_object['TN'])) {
      $variables['parent_tn'] = Url::fromRoute('islandora.view_datastream', ['object' => $parent_object->id, 'datastream' => 'TN']);
    }
    else {
      $variables['parent_tn'] = $folder_image_path;
    }
  }

  // If the parent TN is not set, only show children if there's more than one.
  // If the parent TN is set, show all children even if there's only one.
  if (($variables['child_count'] > 1 && !$variables['parent_tn']) || ($variables['child_count'] > 0 && $variables['parent_tn'])) {
    for ($i = 0; $i < count($variables['siblings']); $i += 1) {
      $sibling = [];
      $sibling['pid'] = $variables['siblings'][$i];
      $sibling['class'] = [];
      if ($sibling['pid'] === $variables['pid']) {
        $sibling['class'][] = 'active';
      }
      $sibling_object = islandora_object_load($sibling['pid']);
      if (isset($sibling_object['TN']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $sibling_object['TN'])) {
        $sibling['TN'] = 'islandora/object/' . $sibling['pid'] . '/datastream/TN/view';
      }
      else {
        // Object either does not have a thumbnail or it's restricted show a
        // default in its place.
        $sibling['TN'] = $folder_image_path;
      }
      $sibling['label'] = $sibling_object->label;
      $themed_siblings[] = $sibling;
    }
  }
  $variables['themed_siblings'] = $themed_siblings;

  // Set value for islandora-compound-title class.
  $variables['islandora_compound_title'] = t('Part of: @parent (@count @objects)', [
      '@parent' => $variables['parent_label'],
      '@count' => $variables['child_count'],
      '@objects' => \Drupal::translation()->formatPlural($variables['child_count'], 'object', 'objects'),
    ]);
}
