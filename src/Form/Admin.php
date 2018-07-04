<?php

namespace Drupal\islandora_compound_object\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Module settings form.
 */
class Admin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_compound_object_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora_compound_object.settings');

    $config->set('islandora_compound_object_compound_children', $form_state->getValue('islandora_compound_object_compound_children'));
    $config->set('islandora_compound_object_thumbnail_child', $form_state->getValue('islandora_compound_object_thumbnail_child'));
    $config->set('islandora_compound_object_hide_child_objects_ri', $form_state->getValue('islandora_compound_object_hide_child_objects_ri'));
    $config->set('islandora_compound_object_hide_child_objects_solr', $form_state->getValue('islandora_compound_object_hide_child_objects_solr'));
    $config->set('islandora_compound_object_solr_fq', $form_state->getValue('islandora_compound_object_solr_fq'));
    $config->set('islandora_compound_object_relationship', $form_state->getValue('islandora_compound_object_relationship'));
    $config->set('islandora_compound_object_use_jail_view', $form_state->getValue('islandora_compound_object_use_jail_view'));
    $config->set('islandora_compound_object_tn_deriv_hooks', $form_state->getValue('islandora_compound_object_tn_deriv_hooks'));
    $config->set('islandora_compound_object_show_compound_parents_in_breadcrumbs', $form_state->getValue('islandora_compound_object_show_compound_parents_in_breadcrumbs'));
    $config->set('islandora_compound_object_redirect_to_first', $form_state->getValue('islandora_compound_object_redirect_to_first'));
    $config->set('islandora_compound_object_query_backend', $form_state->getValue('islandora_compound_object_query_backend'));

    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_compound_object.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_compound_object', 'inc', 'includes/admin.form');
    $config = \Drupal::config('islandora_compound_object.settings');
    $backend_options = \Drupal::moduleHandler()->invokeAll('islandora_compound_object_query_backends');
    $map_to_title = function ($backend) {
      return $backend['title'];
    };

    $form = [];

    $form['islandora_compound_object_compound_children'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only allow compound objects to have child objects associated with them'),
      '#description' => $this->t('If unchecked, all objects may have child objects.'),
      '#default_value' => $config->get('islandora_compound_object_compound_children'),
    ];

    $form['islandora_compound_object_thumbnail_child'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate a thumbnail for compound objects from their first child'),
      '#description' => $this->t('If checked, the thumbnail for a compound object will be generated from its first child object.'),
      '#default_value' => $config->get('islandora_compound_object_thumbnail_child'),
    ];

    $form['islandora_compound_object_hide_child_objects_ri'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide child objects in RI results'),
      '#description' => $this->t('If checked, child objects will be hidden. Only visible within the compound context.'),
      '#default_value' => $config->get('islandora_compound_object_hide_child_objects_ri'),
    ];

    $form['islandora_compound_object_hide_child_objects_solr'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide child objects in Solr results'),
      '#description' => $this->t('If checked, child objects will be hidden. Only visible within the compound context.'),
      '#default_value' => $config->get('islandora_compound_object_hide_child_objects_solr'),
    ];

    $form['islandora_compound_object_solr_fq'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr filter query'),
      '#description' => $this->t('Filter compound object children from Solr results.'),
      '#default_value' => $config->get('islandora_compound_object_solr_fq'),
      '#element_validate' => [
        'islandora_compound_object_solr_fq_validate',
      ],
      '#states' => [
        'visible' => [
          ':input[name="islandora_compound_object_hide_child_objects_solr"]' => [
            'checked' => TRUE,
            ],
          ],
        'required' => [
          ':input[name="islandora_compound_object_hide_child_objects_solr"]' => [
            'checked' => TRUE,
            ],
          ],
      ],
    ];

    $form['islandora_compound_object_relationship'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Child relationship predicate'),
      '#description' => $this->t('Changing this after objects have been created will break functionality. Should be part of info:fedora/fedora-system:def/relations-external#'),
      '#default_value' => $config->get('islandora_compound_object_relationship'),
      '#required' => TRUE,
    ];

    $form['islandora_compound_object_use_jail_view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use alternative, autoloading display for compounds?'),
      '#description' => $this->t('<b>Requires</b> <a href="@url">JAIL</a> library to be present.', [
        '@url' => \Drupal\Core\Url::fromUri('https://github.com/sebarmeli/JAIL')->toString(),
      ]),
      '#default_value' => $config->get('islandora_compound_object_use_jail_view'),
      '#element_validate' => [
        'islandora_compound_object_admin_form_jail_validation',
        ],
    ];

    $form['islandora_compound_object_tn_deriv_hooks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use derivative hooks for parent thumbnail updates?'),
      '#description' => $this->t('Changes to the TN datastream of a compounds first child will be reflected on the parent.'),
      '#default_value' => $config->get('islandora_compound_object_tn_deriv_hooks'),
    ];

    $form['islandora_compound_object_show_compound_parents_in_breadcrumbs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display compound object parents in the breadcrumbs on children objects.'),
      '#default_value' => $config->get('islandora_compound_object_show_compound_parents_in_breadcrumbs'),
    ];

    $form['islandora_compound_object_redirect_to_first'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect to first child when a compound object is requested.'),
      '#description' => $this->t('Users will be redirected to the first child of a Compound Object when enabling this setting.'),
      '#default_value' => $config->get('islandora_compound_object_redirect_to_first'),
      '#element_validate' => [
        'islandora_compound_object_admin_form_redirect_to_first_validation',
        ],
    ];

    $form['islandora_compound_object_query_backend'] = [
      '#type' => 'radios',
      '#title' => $this->t('Compound Member Query'),
      '#description' => $this->t('Select the method that will be used to find the children of the compound objects.'),
      '#options' => array_map($map_to_title, $backend_options),
      '#default_value' => $config->get('islandora_compound_object_query_backend'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

}
