<?php

namespace Drupal\islandora_compound_object\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * Manage compound form.
 *
 * @package \Drupal\islandora_compound_object\Form
 */
class Manage extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_compound_object_manage_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_compound_object', 'inc', 'includes/manage.form');
    $form = array();
    $config = \Drupal::config('islandora_compound_object.settings');
    $form_state->set(['object'], $object);
    $rels_predicate = $config->get('islandora_compound_object_relationship');

    // Add child objects.
    if ((\Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_compound_children') && in_array(ISLANDORA_COMPOUND_OBJECT_CMODEL, $object->models)) || !\Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_compound_children')) {
      $form['add_children'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Add Child Objects'),
        '#description' => $this->t('Add child objects as part of this compound object'),
      );
      $form['add_children']['child'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Child Object Pid/Label'),
        '#autocomplete_route_name' => 'islandora_compound_object.autocomplete_child',
        '#autocomplete_route_parameters' => ['object' => $object->id],
      );

      // Remove children.
      $children = islandora_compound_object_get_parts($object->id, TRUE);
      if (!empty($children)) {
        $form['children'] = array(
          '#type' => 'details',
          '#title' => $this->t('Remove Child Objects'),
          '#description' => $this->t('Remove child objects of as part of this compound object'),
          '#open' => FALSE,
        );

        $header = array('title' => $this->t('Title'), 'pid' => $this->t('Object ID'));
        $form['children']['remove_children'] = array(
          '#type' => 'tableselect',
          '#title' => $this->t('Children'),
          '#header' => $header,
          '#options' => $children,
        );
        $form['reorder_fieldset'] = array(
          '#type' => 'details',
          '#title' => $this->t('Reorder'),
          '#open' => FALSE,
        );
        $form['reorder_fieldset']['table'] = array(
        ) + islandora_compound_object_get_tabledrag_element($object);
      }
    }
    // Add parents.
    $form['add_to_parent'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Associate with Parent Object'),
      '#description' => $this->t('Add this object to a parent object'),
    );
    $form['add_to_parent']['parent'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Parent Object Pid/Label'),
      '#autocomplete_route_name' => 'islandora_compound_object.autocomplete_parent',
      '#autocomplete_route_parameters' => ['object' => $object->id],
    );

    // Remove parents.
    $parent_part_of = $object->relationships->get('info:fedora/fedora-system:def/relations-external#', $rels_predicate);
    if (!empty($parent_part_of)) {
      $form['parents'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Unlink From Parent'),
        '#description' => $this->t('Remove the relationship between this object and parent objects.'),
      );

      $parents = array();
      foreach ($parent_part_of as $parent) {
        // Shouldn't be too much of a hit but would be good to avoid the
        // islandora_object_loads.
        $pid = $parent['object']['value'];
        $parent_object = islandora_object_load($pid);
        $parents[$pid] = array('title' => $parent_object->label, 'pid' => $pid);
      }

      $form['parents']['unlink_parents'] = array(
        '#type' => 'tableselect',
        '#title' => $this->t('Parents'),
        '#header' => array('title' => $this->t('Title'), 'pid' => $this->t('Object ID')),
        '#options' => $parents,
      );
    }

    $form['object'] = array(
      '#type' => 'value',
      '#value' => $object,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  $object = $form_state->getValue('object');
  $create_thumbs = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_thumbnail_child');
  $rels_predicate = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship');
  // Relationship from child to this object.
  if (!empty($form_state->getValue('child'))) {
    $child_object = islandora_object_load($form_state->getValue('child'));
    islandora_compound_object_add_parent($child_object, $object->id);
    if ($create_thumbs) {
      islandora_compound_object_update_parent_thumbnail($object);
    }
  }

  // Add relationship to parent.
  if (!empty($form_state->getValue('parent'))) {
    islandora_compound_object_add_parent($object, $form_state->getValue('parent'));
    if ($create_thumbs) {
      islandora_compound_object_update_parent_thumbnail(islandora_object_load($form_state->getValue('parent')));
    }
  }

  // Remove children.
  // @todo Batch.
  if (!empty($form_state->getValue('remove_children'))) {
    islandora_compound_object_remove_parent(array_map('islandora_object_load', array_filter($form_state->getValue('remove_children'))), $object->id);
    if ($create_thumbs) {
      islandora_compound_object_update_parent_thumbnail($object);
    }
  }

  // Unlink parents.
  if (!empty($form_state->getValue('unlink_parents'))) {
    foreach (array_filter($form_state->getValue('unlink_parents')) as $parent) {
      islandora_compound_object_remove_parent($object, $parent);
      if ($create_thumbs) {
        $parent_object = islandora_object_load($parent);
        islandora_compound_object_update_parent_thumbnail($parent_object);
      }
    }
  }

  // @TODO: Actually confirm.
  drupal_set_message(t('Compound relationships modified.'));
  }

}
