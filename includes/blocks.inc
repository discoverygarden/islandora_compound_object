<?php

/**
 * @file
 * Holds block definitions for the compound solution pack.
 */

use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableMetadata;

use Drupal\islandora\Controller\DefaultController as IslandoraController;

/**
 * Constructs the JAIL display block for a compound.
 *
 * @return array
 *   Renderable array for the block.
 */
function islandora_compound_object_jail_display_block() {
  $block = [];

  $object = \Drupal::routeMatch()->getParameter('object', 2);

  $cache_meta = CacheableMetadata::createFromRenderArray($block)
    ->addCacheContexts([
      'url',
    ])
    ->addCacheableDependency($object)
    ->addCacheTags([
      IslandoraController::LISTING_TAG,
    ]);

  if ($object) {
    $compound_info = islandora_compound_object_retrieve_compound_info($object, TRUE);
    if (!empty($compound_info)) {
      $module_path = drupal_get_path('module', 'islandora_compound_object');
      $block['#attached']['library'][] = 'islandora_compound_object/compound_jail';
      $block['#attached']['drupalSettings']['islandora_compound_object']['image_path'] = file_create_url(drupal_get_path('module', 'islandora') . '/images/folder.png');
      $block['part_title'] = [
        '#type' => 'item',
        '#markup' => t('Part of: @label (@count @obj)', [
          '@label' => $compound_info['parent_label'],
          '@count' => $compound_info['child_count'],
          '@obj' => \Drupal::translation()->formatPlural($compound_info['child_count'], 'object', 'objects'),
        ]),
      ];
      $manage_link = \Drupal::request()->getSchemeAndHttpHost() . '/' . $compound_info['parent_url'];
      if ($compound_info['parent_url']) {
        $block['manage_link'] = [
          '#type' => 'item',
          '#markup' => t('<a href="@href">manage parent</a>', [
            '@href' => $manage_link,
          ]),
        ];

      }

      foreach ($compound_info['siblings_detailed'] as $sibling) {
        $class = [
          'islandora-compound-object-jail',
        ];
        if ($sibling['pid'] == $compound_info['current_pid']) {
          $class[] = 'islandora-compound-object-jail-active';
        }
        // XXX: In order to get the noscript tags to work, we make the image
        // element first. There should be a cleaner way to do this.
        $image_noscript = [
          '#theme' => 'image',
          '#uri' => Url::fromRoute('islandora.view_datastream_view', ['object' => $sibling['pid'], 'datastream' => 'TN'])->toString(),
        ];
        $rendered_image_noscript = drupal_render($image_noscript);

        $img = [
          'image' => [
            '#theme' => 'image',
            '#uri' => file_create_url("$module_path/images/loader.png"),
            '#attributes' => [
              'class' => $class,
              'data-src' => Url::fromRoute('islandora.view_datastream_view', ['object' => $sibling['pid'], 'datastream' => 'TN'])->toString(),
            ],
          ],
          'noscript' => [
            '#type' => 'html_tag',
            '#tag' => 'noscript',
            '#value' => $rendered_image_noscript,
          ],
        ];
        $block[$sibling['pid']] = [
          '#type' => 'container',
          '#attributes' => [],
          'link' => [
            '#type' => 'link',
            '#title' => $sibling['title'],
            '#url' => Url::fromRoute('islandora.view_object', ['object' => $sibling['pid']]),
          ],
          'content' => [
            '#type' => 'link',
            '#title' => drupal_render($img),
            '#url' => Url::fromRoute('islandora.view_object', ['object' => $sibling['pid']]),
          ],
        ];
      }
    }
  }

  $cache_meta->applyTo($block);

  return $block;
}

/**
 * Constructs the default navigation block for a compound.
 *
 * @param AbstractObject $object
 *   If supplied the object to create the block for.
 *
 * @return array
 *   Renderable array for the block.
 */
function islandora_compound_object_navigation_block(AbstractObject $object = NULL) {
  $block = _islandora_compound_object_navigation_block($object);

  if (isset($block['prev_next'])) {
    return ['#markup' => \Drupal::service('renderer')->render($block)];
  }

}

/**
 * Constructs the default navigation block for a compound.
 *
 * @param AbstractObject $object
 *   If supplied the object to create the block for.
 *
 * @return array
 *   Renderable array for the block.
 */
function _islandora_compound_object_navigation_block(AbstractObject $object = NULL) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  $block = [];

  $cache_meta = CacheableMetadata::createFromRenderArray($block)
    ->addCacheContexts([
      'url',
    ])
    ->addCacheableDependency($object)
    ->addCacheTags([
      IslandoraController::LISTING_TAG,
    ]);

  $object = $object ? $object : \Drupal::routeMatch()->getParameter('object', 2);
  if ($object) {
    $compound_info = islandora_compound_object_retrieve_compound_info($object, TRUE);
    // This object is part of a compound object.
    if (!empty($compound_info)) {
      $block['#attached']['library'][] = 'islandora_compound_object/compound_navigation_css';
      // XXX: The theme processing requires '#'s. There should be a better way.
      $block['prev_next'] = [
        '#theme' => 'islandora_compound_prev_next',
        '#parent_label' => $compound_info['parent_label'],
        '#parent_pid' => $compound_info['parent_pid'],
        '#parent_url' => $compound_info['parent_url'],
        '#previous_pid' => $compound_info['previous_pid'],
        '#next_pid' => $compound_info['next_pid'],
        '#current_pid' => $compound_info['current_pid'],
        '#child_count' => $compound_info['child_count'],
        '#siblings' => $compound_info['siblings'],
        '#siblings_detailed' => $compound_info['siblings_detailed'],
        '#pid' => $compound_info['pid'],
        '#label' => $compound_info['label'],
        '#sequence' => $compound_info['sequence'],
      ];

    }

  }

  $cache_meta->applyTo($block);

  return $block;
}
