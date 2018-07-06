<?php

namespace Drupal\islandora_compound_object\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

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

  /**
   * Manage callback for compound object management.
   */
  public function manage(FedoraObject $object) {
    return \Drupal::formBuilder()->getForm('islandora_compound_object_manage_form', $object);
  }

  /**
   * Autocomplete callback for child object search.
   *
   * @param Request $request
   *   The user supplied request containing the string being searched for.
   *
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
   */
  public static function islandoraCompoundObjectTaskAccess($object, AccountInterface $account) {
    $object = islandora_object_load($object);
    $perm = islandora_compound_object_task_access($object);
    return ($perm && \Drupal::routeMatch()->getRouteName() == 'islandora.view_object') ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
