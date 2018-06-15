<?php

/**
 * @file
 * includes/admin.form.inc
 *
 * Module admin form.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Check if the JAIL is available.
 *
 * @param array $element
 *   The element to check.
 * @param FormStateInterface $form_state
 *   The Drupal form state.
 * @param array $form
 *   The Drupal form definition.
 */
function islandora_compound_object_admin_form_jail_validation(array $element, FormStateInterface &$form_state, array $form) {
  if ($element['#value'] == 1 && !libraries_get_path('JAIL')) {
    $element['#value'] = 0;
    $form_state->setValue($element['#title'], $element['#value']);
    drupal_set_message(t('The <a href="@url">JAIL</a> library must be present in the libraries folder to use this display.', array(
      '@url' => \Drupal\Core\Url::fromUri('https://github.com/sebarmeli/JAIL')->toString(),
    )), 'error');
  }
}

/**
 * Warn if both redirect to first and compound parent in breadcrumb are enabled.
 *
 * @param array $element
 *   The element to check.
 * @param FormStateInterface $form_state
 *   The Drupal form state.
 * @param array $form
 *   The Drupal form definition.
 */
function islandora_compound_object_admin_form_redirect_to_first_validation(array $element, FormStateInterface &$form_state, array $form) {
  if ($element['#value'] == 1) {
    if ($form_state->getValue('islandora_compound_object_show_compound_parents_in_breadcrumbs') == 1) {
      $first_title = rtrim($element['#title'], '.!?');
      $parent_title = rtrim($form['islandora_compound_object_show_compound_parents_in_breadcrumbs']['#title'], '.!?');
      drupal_set_message(t("Enabling both @first and @parent is not recommended, because the compound object parent in the breadcrumb will be redirect to the first child, so cannot be actually reached.",
        array('@first' => $first_title, '@parent' => $parent_title)), 'warning');
    }
  }
}

/**
 * Solr fq element state validation callback.
 *
 * @param $element
 *   The form element.
 * @param FormStateInterface $form_state
 *   The form state.
 */
function islandora_compound_object_solr_fq_validate(array $element, FormStateInterface &$form_state) {
  if ($form_state->getValue('islandora_compound_object_hide_child_objects_solr') &&
    empty($form_state->getValue('islandora_compound_object_solr_fq'))) {
    form_error($element, t('Solr filter query field is required.'));
  }
}
