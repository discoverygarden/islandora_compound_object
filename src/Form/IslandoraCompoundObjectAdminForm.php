<?php

/**
 * @file
 * Contains \Drupal\islandora_compound_object\Form\IslandoraCompoundObjectAdminForm.
 */

namespace Drupal\islandora_compound_object\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraCompoundObjectAdminForm extends ConfigFormBase {

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

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_compound_object.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $backend_options = \Drupal::moduleHandler()->invokeAll('islandora_compound_object_query_backends');
    $map_to_title = function ($backend) {
      return $backend['title'];
    };

    $form = [];

    $form['islandora_compound_object_compound_children'] = [
      '#type' => 'checkbox',
      '#title' => t('Only allow compound objects to have child objects associated with them'),
      '#description' => t('If unchecked, all objects may have child objects.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_compound_children'),
    ];

    $form['islandora_compound_object_thumbnail_child'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate a thumbnail for compound objects from their first child'),
      '#description' => t('If checked, the thumbnail for a compound object will be generated from its first child object.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_thumbnail_child'),
    ];

    $form['islandora_compound_object_hide_child_objects_ri'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide child objects in RI results'),
      '#description' => t('If checked, child objects will be hidden. Only visible within the compound context.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_hide_child_objects_ri'),
    ];

    $form['islandora_compound_object_hide_child_objects_solr'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide child objects in Solr results'),
      '#description' => t('If checked, child objects will be hidden. Only visible within the compound context.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_hide_child_objects_solr'),
    ];

    $form['islandora_compound_object_solr_fq'] = [
      '#type' => 'textfield',
      '#title' => t('Solr filter query'),
      '#description' => t('Filter compound object children from Solr results.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_solr_fq'),
      '#element_validate' => [
        'islandora_compound_object_solr_fq_validate'
        ],
      '#states' => [
        'visible' => [
          ':input[name="islandora_compound_object_hide_child_objects_solr"]' => [
            'checked' => TRUE
            ]
          ],
        'required' => [
          ':input[name="islandora_compound_object_hide_child_objects_solr"]' => [
            'checked' => TRUE
            ]
          ],
      ],
    ];

    $form['islandora_compound_object_relationship'] = [
      '#type' => 'textfield',
      '#title' => t('Child relationship predicate'),
      '#description' => t('Changing this after objects have been created will break functionality. Should be part of info:fedora/fedora-system:def/relations-external#'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship'),
      '#required' => TRUE,
    ];

    $form['islandora_compound_object_use_jail_view'] = [
      '#type' => 'checkbox',
      '#title' => t('Use alternative, autoloading display for compounds?'),
      '#description' => t('<b>Requires</b> <a href="@url">JAIL</a> library to be present.', [
        '@url' => \Drupal\Core\Url::fromUri('https://github.com/sebarmeli/JAIL')
        ]),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_use_jail_view'),
      '#element_validate' => [
        'islandora_compound_object_admin_form_jail_validation'
        ],
    ];

    $form['islandora_compound_object_tn_deriv_hooks'] = [
      '#type' => 'checkbox',
      '#title' => t('Use derivative hooks for parent thumbnail updates?'),
      '#description' => t('Changes to the TN datastream of a compounds first child will be reflected on the parent.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_tn_deriv_hooks'),
    ];

    $form['islandora_compound_object_show_compound_parents_in_breadcrumbs'] = [
      '#type' => 'checkbox',
      '#title' => t('Display compound object parents in the breadcrumbs on children objects.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_show_compound_parents_in_breadcrumbs'),
    ];

    $form['islandora_compound_object_redirect_to_first'] = [
      '#type' => 'checkbox',
      '#title' => t('Redirect to first child when a compound object is requested.'),
      '#description' => t('Users will be redirected to the first child of a Compound Object when enabling this setting.'),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_redirect_to_first'),
      '#element_validate' => [
        'islandora_compound_object_admin_form_redirect_to_first_validation'
        ],
    ];

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/islandora_compound_object.settings.yml and config/schema/islandora_compound_object.schema.yml.
    $form['islandora_compound_object_query_backend'] = [
      '#type' => 'radios',
      '#title' => t('Compound Member Query'),
      '#description' => t('Select the method that will be used to find the children of the compound objects.'),
      '#options' => array_map($map_to_title, $backend_options),
      '#default_value' => \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_query_backend'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
?>
