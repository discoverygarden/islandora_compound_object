<?php

namespace Drupal\islandora_compound_object\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Default controller for the islandora_compound_object module.
 */
class DefaultController extends ControllerBase {

  /**
   * Access callback for compound object management.
   */
  public function access(AbstractObject $object, Drupal\Core\Session\AccountInterface $account) {
    return islandora_object_access('administer compound relationships', $object);
  }

  public function manage(FedoraObject $object) {
    return \Drupal::formBuilder()->getForm('islandora_compound_object_manage_form', $object);
  }

  /**
   * Autocomplete callback for child object search.
   *
   * @param string $string
   *   The user supplied string that is being searched for.
   * @param bool $parent
   *   A flag indicating if we are to return objects usable as parents.
   */
  public function autocomplete(Request $request, $parent = FALSE) {
    $config = \Drupal::config('islandora_compound_object.settings');
    $string = $request->query->get('q');
    $matches = [];
    $islandora_tuque = islandora_get_tuque_connection();
    $compound_enforcement = $config->get('islandora_compound_object_compound_children');

    // Build escapes as per:
    // - https://www.w3.org/TR/xmlschema-2/#dt-metac and
    // - https://www.w3.org/TR/xmlschema-2/#dt-cces1
    $meta = [
      '\\',
      '.',
      '?',
      '*',
      '+',
      '{',
      '}',
      '(',
      ')',
      '[',
      ']',
      '|',
      '-',
      '^',
    ];
    $escape_meta = function ($meta) {
      return "\\\\$meta";
    };
    $meta_replacements = array_map($escape_meta, array_combine($meta, $meta));

    $replacements = [
      '!compound_model' => '?model',
      '!text' => str_replace(array_keys($meta_replacements), $meta_replacements, $string),
    ];
    if ($compound_enforcement && $parent) {
      $compound_model = ISLANDORA_COMPOUND_OBJECT_CMODEL;
      $replacements['!compound_model'] = "<info:fedora/$compound_model>";
    }

    $query = <<<'EOQ'
SELECT DISTINCT ?pid ?title
FROM <#ri>
WHERE {
  ?pid <fedora-model:hasModel> !compound_model ;
       <fedora-model:label> ?title .
  FILTER(regex(?title, "!text", 'i') || regex(str(?pid), "!text", 'i'))
}
LIMIT 10
EOQ;
    $query = strtr($query, $replacements);
    $results = $islandora_tuque->repository->ri->sparqlQuery($query, 'unlimited');
    foreach ($results as $result) {
      $matches[] = [
        'value' => $this->t('@pid', [
          '@pid' => $result['pid']['value']
        ]),
        'label' => $this->t('@title (@pid)', [
          '@title' => $result['title']['value'],
          '@pid' => $result['pid']['value'],
        ]),
      ];
    }
    return new JsonResponse($matches);
  }

  /**
   * Access callback for tabs that aren't tabs.
   *
   * @param AbstractObject $object
   *   An AbstractObject representing a Fedora object.
   *
   * @return bool
   *   TRUE if the user has access, FALSE otherwise.
   */
  public function islandora_compound_object_task_access(AbstractObject $object, Drupal\Core\Session\AccountInterface $account) {
    $config = \Drupal::config('islandora_compound_object.settings');
    $rels_predicate = $config->get('islandora_compound_object_relationship');
    $part_of = $object->relationships->get(FEDORA_RELS_EXT_URI, $rels_predicate);
    if ($config->get('islandora_compound_object_use_jail_view') && islandora_object_access(ISLANDORA_VIEW_OBJECTS, $object) && ((in_array(ISLANDORA_COMPOUND_OBJECT_CMODEL, $object->models) && islandora_compound_object_children_exist($object)) || !empty($part_of))) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
