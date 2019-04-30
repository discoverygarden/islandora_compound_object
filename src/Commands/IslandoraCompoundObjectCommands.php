<?php

namespace Drupal\islandora_compound_object\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class IslandoraCompoundObjectCommands extends DrushCommands {

  use DependencySerializationTrait;

  const OLD_PRED = 'isPartOf';
  const NEW_PRED = 'isConstituentOf';

  /**
   * Configuration factory.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger factory.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Update relationship predicate on compound object children predicates.
   *
   * @command islandora_compound_object:update-rels-predicate
   * @aliases update_rels_predicate
   * @islandora-user-wrap
   */
  public function updateRelsPredicate() {
    // Update all compound object child relationships to use
    // isConstituentOf.
    module_load_include('inc', 'islandora', 'includes/utilities');

    $tuque = islandora_get_tuque_connection();
    $query = <<<EOQ
  SELECT ?object ?label
  FROM <#ri>
  WHERE {
    ?object <fedora-model:label> ?label ;
            <fedora-model:hasModel> <info:fedora/!model> ;
            <fedora-model:state> <fedora-model:Active>
  }
  ORDER BY ?label
EOQ;
    $query = strtr($query, [
      '!model' => ISLANDORA_COMPOUND_OBJECT_CMODEL,
    ]);

    $results = $tuque->repository->ri->sparqlQuery($query, 'unlimited');
    $operations = [];
    foreach ($results as $compound_object) {
      array_push($operations, [
        [
          get_class($this),
          'setNewPredicate',
        ],
        [
          $compound_object['object']['value'],
          'Setting child relationship(s) predicate from compound object: ',
        ],
      ]);
      // Now set up the operation to set the new predicate. In this case,
      // fedora:isConstituentOf.
      array_push($operations, [
        [
          get_class($this),
          'deleteOldPredicate',
        ],
        [
          $compound_object['object']['value'],
          'Deleting old child relationship(s) predicate from compound object: ',
        ],
      ]);
    }

    // Construct the batch array for processing.
    $batch = [
      'operations' => $operations,
      'title' => dt('Rels predicate update batch'),
      'finished' => [$this, 'finished'],
      'file' => drupal_get_path('module', 'islandora_compound_object') . '/includes/islandora_compound_object.drush.inc',
    ];

    // Get the batch process set up.
    batch_set($batch);
    $batch = & batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

  /**
   * Set the rels_predicate of the compound object member.
   *
   * @param string $compound_object
   *   The PID of the compound object.
   * @param string $operation_details
   *   The status message to for this context.
   * @param DrushBatchContext|array $context
   *   The current processing context.
   */
  public static function setNewPredicate($compound_object, $operation_details, &$context) {
    $context['message'] = $operation_details . $compound_object;
    $parts = islandora_compound_object_get_parts($compound_object);
    $insert_seq = static::getInsertSequence($parts);
    foreach ($parts as $part) {
      $escaped_pid = str_replace(':', '_', $compound_object);
      $child_object = islandora_object_load($part);
      $rels = $child_object->relationships->get(FEDORA_RELS_EXT_URI, static::NEW_PRED, $compound_object);
      if (count($rels) == 0) {
        $child_object->relationships->add(FEDORA_RELS_EXT_URI, static::NEW_PRED, $compound_object);
      }

      $rels = $child_object->relationships->get(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf$escaped_pid");
      if (count($rels) == 0) {
        $child_object->relationships->add(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf$escaped_pid", $insert_seq, RELS_TYPE_PLAIN_LITERAL);
      }
    }
  }

  /**
   * Remove the rels_predicate of the compound object member.
   *
   * @param string $compound_object
   *   The AbstractFedoraObject pid.
   * @param string $operation_details
   *   Details of current operation being processed.
   * @param DrushBatchContext|array $context
   *   Active context of the current batch.
   */
  public static function deleteOldPredicate($compound_object, $operation_details, &$context) {
    $context['message'] = $operation_details . $compound_object;
    $parts = islandora_compound_object_get_parts($compound_object);
    foreach ($parts as $part) {
      $child_object = islandora_object_load($part);
      $child_object->relationships->remove(FEDORA_RELS_EXT_URI, static::OLD_PRED, $compound_object);
    }
  }

  /**
   * Retrieve the new insertion point.
   *
   * @param array $children
   *   The array of the compound object's children.
   *
   * @return int
   *   The position to insert the new compound object.
   */
  protected static function getInsertSequence(array $children) {
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
   * Batch process complete handler.
   *
   * @param bool $success
   *   The batch status success message.
   * @param array $results
   *   The batch staus result.
   * @param array $operations
   *   The batch operations performed.
   */
  public function finished($success, array $results, array $operations) {
    // Print finished message to user.
    $this->configFactory
      ->getEditable('islandora_compound_object.settings')
      ->set('islandora_compound_object_relationship', static::NEW_PRED)
      ->save();
    $this->loggerFactory->get('islandora_compound_object')->info('Finished updating compound object relationship predicate(s).');
  }

}
