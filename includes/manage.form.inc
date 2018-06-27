<?php

/**
 * @file
 * includes/manage.form.inc
 *
 * Compound object management callback and form.
 */

use \Drupal\Component\Utility\SortArray;

/**
 * Returns the table to be used for tabledragging.
 *
 * @param AbstractObject $object
 *   An AbstractObject representing an object within Fedora.
 *
 * @return array
 *   An array defining the markup for the table.
 */
function islandora_compound_object_get_tabledrag_element(AbstractObject $object) {
  $children = islandora_compound_object_get_parts($object->id, TRUE);
  $seq = 0;
  $delta = count($children);
  $map = function($child) use($delta, &$seq) {
    $seq++;
    return [
      '#attributes' => [
        'class' => ['draggable'],
      ],
      '#weight' => $seq,
      'label' => [
        '#type' => 'item',
        '#markup' => \Drupal\Component\Utility\Html::escape($child['title']),
      ],
      'pid' => [
      '#type' => 'item',
      '#markup' => \Drupal\Component\Utility\Html::escape($child['pid']),
      ],
      'weight' => array(
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#default_value' => $seq,
        '#delta' => $delta,
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['islandora-compound-object-reorder-table-order-weight'],
        ],
      ),
    ];
  };
  $rows = array_map($map, $children);
  $tabledrag_element = [
    [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [t('Title'), t('Object ID'), t('Weight')],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'islandora-compound-object-reorder-table-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'islandora-compound-object-reorder-table',
      ],
    ] + $rows,
    [
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => t('Save Changes'),
          '#submit' => ['islandora_compound_object_sequence_submit'],
        ],
      ],
    ],
  ];
  return $tabledrag_element;
}

/**
 * Submit handler for the re-ordering table.
 *
 * @param array $form
 *   The Drupal form.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_compound_object_sequence_submit($form, &$form_state) {
$form_state_values = &$form_state->getValues();
  $object = $form_state_values['object'];
  $compounds = &$form_state_values['0'];
  if ($compounds) {
    uasort($compounds, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    islandora_compound_object_sequence_batch($object->id, $compounds);
  }
}

/**
 * Defines and constructs the batch used to re-order compound objects.
 *
 * @param string $pid
 *   The pid of the parent object.
 * @param array $compounds
 *   An array of compound objects we are iterating over.
 */
function islandora_compound_object_sequence_batch($pid, array $compounds) {
  $object = islandora_object_load($pid);
  $context['results'] = array(
    'success' => array(),
    'failure' => array(),
  );
  $context['sandbox']['progress'] = 0;
  $context['sandbox']['total'] = count($compounds);
  $operations = islandora_compound_object_build_sequence_operations($pid, $compounds);
  $batch = array(
    'operations' => $operations,
    'title' => t("Sequencing @label's compound objects ...", array('@label' => $object->label)),
    'init_message' => t("Preparing to sequence @label's child objects ...", array('@label' => $object->label)),
    'progress_message' => t('Time elapsed: @elapsed <br/>Estimated time remaining @estimate.'),
    'error_message' => t('An error has occurred.'),
    'file' => drupal_get_path('module', 'islandora_compound_object') . '/includes/manage.form.inc',
  );
  batch_set($batch);
}

/**
 * To change the thumbnail after update sequence.
 *
 * @param string $parent
 *   The parent pid.
 */
function islandora_compound_object_change_thumbnail($parent) {
  $create_thumbs = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_thumbnail_child');
  if ($create_thumbs) {
    islandora_compound_object_update_parent_thumbnail(islandora_object_load($parent));
  }
}

/**
 * Builds up a sequence of operations for sequence numbering.
 *
 * @param string $parent_pid
 *   The pid of the parent object.
 * @param array $compounds
 *   An array of compound objects.
 *
 * @return array
 *   The built up operations array.
 */
function islandora_compound_object_build_sequence_operations($parent_pid, $compounds) {
  $operations = array();
  $start_seq = 1;
  foreach ($compounds as $child_pid => $weight) {
    $operations[] = array(
      'islandora_compound_object_update_sequence', array(
        $parent_pid,
        $child_pid,
        $start_seq,
      ),
    );
    $start_seq++;
  }
  $operations[] = array(
    'islandora_compound_object_change_thumbnail', array(
      $parent_pid,
    ),
  );
  return $operations;
}

/**
 * Updates the relationships to reflect the object's order to the parent.
 *
 * @param string $parent_pid
 *   The pid of the parent object.
 * @param array $child_pid
 *   The pid of the child object;
 * @param array $context
 *   Context used in the batch.
 */
function islandora_compound_object_update_sequence($parent_pid, $child_pid, $start_seq, &$context) {
  $escaped_pid = str_replace(':', '_', $parent_pid);
  $child_object = islandora_object_load($child_pid);
  $child_object->relationships->remove(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf$escaped_pid");
  $child_object->relationships->add(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf$escaped_pid", $start_seq, RELS_TYPE_PLAIN_LITERAL);
  $context['message'] = t('Inserting page "@label" (@pid) at position "@pos"', array(
    '@label' => $child_object->label,
    '@pid' => $child_object->id,
    '@pos' => $start_seq,
    )
  );
}

/**
 * Retrieves the insertion sequence number point.
 *
 * @param array $children
 *   All the child objects of a parent we need to iterate over.
 *
 * @return int
 *   The point at which we are to insert the compound object.
 */
function islandora_compound_object_get_insert_sequence_number($children) {
  $insert_seq = 0;
  foreach ($children as $child) {
    if (!empty($child['seq']) && $child['seq'] > $insert_seq) {
      $insert_seq = $child['seq'];
    }
  }
  // Want to insert one past this point.
  $insert_seq++;
  return $insert_seq;
}

/**
 * Add the given objects to the given parents.
 *
 * @param array|AbstractObject $objects
 *   An array of AbstractObject to add to a parent, to produce a compound.
 * @param array|string $parent_pids
 *   An array of PIDs representing the parents to which an object should be
 *   added, to make each member of $objects a member of multiple compounds.
 */
function islandora_compound_object_add_parent($objects, $parent_pids) {
  $objects = $objects instanceof AbstractObject ? array($objects) : $objects;
  $parent_pids = (array) $parent_pids;
  $rels_predicate = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship');

  foreach ($parent_pids as $parent_pid) {
    $escaped_pid = str_replace(':', '_', $parent_pid);

    foreach ($objects as $object) {
      $children = islandora_compound_object_get_parts($parent_pid, TRUE);
      $insert_seq = islandora_compound_object_get_insert_sequence_number($children);

      $object->relationships->autoCommit = FALSE;
      $object->relationships->add(FEDORA_RELS_EXT_URI, $rels_predicate, $parent_pid);
      $object->relationships->add(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf$escaped_pid", $insert_seq, RELS_TYPE_PLAIN_LITERAL);
      $object->relationships->commitRelationships();
    }
  }

  \Drupal::moduleHandler()->invokeAll('islandora_compound_object_children_added_to_parent', [$objects, $parent_pids]);
}

/**
 * Remove the given objects from the given parents.
 *
 * @param array|string $objects
 *   An array of objects to remove from each of the PIDs listed in
 *   $parent_pids.
 * @param array|string $parent_pids
 *   An array of PIDs from which the members of $objects will be removed.
 */
function islandora_compound_object_remove_parent($objects, $parent_pids) {
  $objects = $objects instanceof AbstractObject ? array($objects) : $objects;
  $parent_pids = (array) $parent_pids;
  $rels_predicate = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship');

  foreach ($parent_pids as $parent_pid) {
    $escaped_pid = str_replace(':', '_', $parent_pid);

    foreach ($objects as $object) {
      $object->relationships->remove(FEDORA_RELS_EXT_URI, $rels_predicate, $parent_pid);
      $object->relationships->remove(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf$escaped_pid");
    }
  }

  \Drupal::moduleHandler()->invokeAll('islandora_compound_object_children_removed_from_parent', [$objects, $parent_pids]);
}
