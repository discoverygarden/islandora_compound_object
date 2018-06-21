<?php /**
 * @file
 * Contains \Drupal\islandora_compound_object\Controller\DefaultController.
 */

namespace Drupal\islandora_compound_object\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Default controller for the islandora_compound_object module.
 */
class DefaultController extends ControllerBase {

  public function access(AbstractObject $object, Drupal\Core\Session\AccountInterface $account) {
    return islandora_object_access('administer compound relationships', $object);
  }

  public function manage(FedoraObject $object) {
    return \Drupal::formBuilder()->getForm('islandora_compound_object_manage_form', $object);
  }

  public function autocomplete(Request $request, $parent = FALSE) {
    $string = $request->query->get('q');
    $matches = [];
    $islandora_tuque = islandora_get_tuque_connection();
    $compound_enforcement = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_compound_children');

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
      $matches[$result['pid']['value']] = t('@title (@pid)', [
        '@title' => $result['title']['value'],
        '@pid' => $result['pid']['value'],
      ]);
    }
    return new JsonResponse(array_values($matches));
  }

  public function islandora_compound_object_task_access(AbstractObject $object, Drupal\Core\Session\AccountInterface $account) {
    $rels_predicate = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship');
    $part_of = $object->relationships->get(FEDORA_RELS_EXT_URI, $rels_predicate);
    if (\Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_use_jail_view') && islandora_object_access(ISLANDORA_VIEW_OBJECTS, $object) && ((in_array(ISLANDORA_COMPOUND_OBJECT_CMODEL, $object->models) && islandora_compound_object_children_exist($object)) || !empty($part_of))) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
