<?php

/**
 * @file
 * Holds backend callable definitions for the compound solution pack.
 */

/**
 * Implements callback_islandora_compound_object_query_backends().
 */
function islandora_compound_object_legacy_query_sparql($pid) {
  $rels_predicate = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship');
  $objects = [];

  $connection = islandora_get_tuque_connection();
  $escaped_pid = str_replace(':', '_', $pid);
  if ($connection) {
    $query = <<<EOQ
PREFIX islandora-rels-ext: <http://islandora.ca/ontology/relsext#>
SELECT ?object ?title ?seq
FROM <#ri>
WHERE {
  ?object <fedora-model:label> ?title ;
          <fedora-rels-ext:$rels_predicate> <info:fedora/$pid> .
  OPTIONAL {
    ?object islandora-rels-ext:isSequenceNumberOf$escaped_pid ?seq
  }
}
EOQ;
    $results = $connection->repository->ri->sparqlQuery($query);

    // Sort the objects into their proper order.
    $sort = function ($a, $b) {
      $a = $a['seq']['value'];
      $b = $b['seq']['value'];
      if ($a === $b) {
        return 0;
      }
      if (empty($a)) {
        return 1;
      }
      if (empty($b)) {
        return -1;
      }
      return $a - $b;
    };
    uasort($results, $sort);

    foreach ($results as $result) {
      $object = islandora_object_load($result['object']['value']);
      if (islandora_object_access(ISLANDORA_VIEW_OBJECTS, $object)) {
        $objects[$result['object']['value']] = [
          'pid' => $result['object']['value'],
          'title' => $result['title']['value'],
          'seq' => $result['seq']['value'],
        ];
      }
    }
  }

  return $objects;
}

/**
 * Implements callback_islandora_compound_object_query_backends().
 */
function islandora_compound_object_query_sparql($pid) {
  module_load_include('inc', 'islandora', 'includes/utilities');

  $rels_predicate = \Drupal::config('islandora_compound_object.settings')->get('islandora_compound_object_relationship');
  $objects = [];

  // Handle XACML restrictions in query.
  $user = \Drupal::currentUser();
  if ($user->id() === 0) {
    $user_name = 'anonymous';
  }
  else {
    $user_name = $user->getAccountName();
  }

  $role_matches = [];
  foreach ($user->getRoles() as $role) {
    $role_matches[] = "?role='{$role}'";
  }
  $role_matcher = implode(' || ', $role_matches);

  // Handle namespace restrictions in query.
  $enforced = \Drupal::config('islandora.settings')->get('islandora_namespace_restriction_enforced');

  $namespace_filter = '';
  if ($enforced) {
    $namespace_array = islandora_get_allowed_namespaces();
    $namespace_sparql = implode('|', $namespace_array);
    $namespace_filter = strtr('FILTER(regex(str(?object), "info:fedora/(!namespaces):"))', ['!namespaces' => $namespace_sparql]);
  }

  $escaped_pid = str_replace(':', '_', $pid);
  $query = <<<EOQ
PREFIX islandora-rels-ext: <http://islandora.ca/ontology/relsext#>
SELECT DISTINCT ?object ?title ?seq
FROM <#ri>
WHERE {
  ?object <fedora-model:label> ?title ;
          <fedora-rels-ext:$rels_predicate> <info:fedora/$pid> .
  OPTIONAL {
      ?object islandora-rels-ext:isSequenceNumberOf$escaped_pid ?seq
  }
  OPTIONAL {{
      ?object islandora-rels-ext:isViewableByRole ?role
    } UNION {
      ?object islandora-rels-ext:isViewableByUser ?user
  }}
  FILTER((!bound(?role) && !bound(?user)) || (bound(?user) && ?user='{$user_name}') || (bound(?role) && ($role_matcher)))
  $namespace_filter
}
ORDER BY ASC(?seq)
EOQ;

  $connection = islandora_get_tuque_connection();
  $results = $connection ? $connection->repository->ri->sparqlQuery($query) : [];

  foreach ($results as $result) {
    $objects[$result['object']['value']] = [
      'pid' => $result['object']['value'],
      'title' => $result['title']['value'],
      'seq' => $result['seq']['value'],
    ];
  }

  return $objects;
}
