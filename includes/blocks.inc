<?php

/**
 * @file
 * Holds block definitions for the compound solution pack.
 */

use Drupal\Component\Utility\UrlHelper;

/**
 * Constructs the JAIL display block for a compound.
 *
 * @return array
 *   Renderable array for the block.
 */
function islandora_compound_object_jail_display_block() {
  $block = [];
  $object = \Drupal::routeMatch()->getParameter('islandora_object', 2);
  if ($object) {
    $compound_info = islandora_compound_object_retrieve_compound_info($object, TRUE);
    if (!empty($compound_info)) {
      $module_path = drupal_get_path('module', 'islandora_compound_object');
      $jail_path = libraries_get_path('JAIL');
      $block['#attached']['js'] = [
        "$jail_path/lib/jquery.js" => [
          'group' => JS_LIBRARY,
        ],
        "$jail_path/dist/jail.min.js" => [
          'group' => JS_LIBRARY,
        ],
        "$module_path/js/compound_jail.js" => [
          'group' => JS_LIBRARY,
        ],
      ];
      // @FIXME
// url() expects a route name or an external URI.
// $block['#attached']['js'][] = array(
//         'data' => array(
//           'islandora_compound_object' => array(
//             'image_path' => url(drupal_get_path('module', 'islandora') . '/images/folder.png'),
//           ),
//         ),
//         'type' => 'setting',
//       );

      $block['#attached']['css'] = [
        "$module_path/css/islandora_compound_object.jail_block.css",
      ];
      $block['part_title'] = [
        '#type' => 'item',
        '#markup' => t('Part of: @label (@count @obj)', [
          '@label' => $compound_info['parent_label'],
          '@count' => $compound_info['child_count'],
          '@obj' => \Drupal::translation()->formatPlural($compound_info['child_count'], 'object', 'objects'),
        ]),
      ];
      if ($compound_info['parent_url']) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $block['manage_link'] = array(
//           '#type' => 'item',
//           '#markup' => l(t('manage parent'), $compound_info['parent_url']),
//         );

      }

      $query_params = UrlHelper::filterQueryParameters();
      foreach ($compound_info['siblings_detailed'] as $sibling) {
        $path = 'islandora/object/' . $sibling['pid'] . '/datastream/TN/view';
        $class = [
          'islandora-compound-object-jail',
        ];
        if ($sibling['pid'] == $compound_info['current_pid']) {
          $class[] = 'islandora-compound-object-jail-active';
        }
        // @FIXME
// url() expects a route name or an external URI.
// $img = array(
//           'image' => array(
//             '#theme' => 'image',
//             '#path' => "$module_path/images/loader.png",
//             '#attributes' => array(
//               'class' => $class,
//               'data-src' => url($path),
//             ),
//             '#href' => "islandora/object/{$sibling['pid']}",
//           ),
//           'noscript' => array(
//             '#theme' => 'image',
//             '#path' => $path,
//             '#prefix' => '<noscript>',
//             '#suffix' => '</noscript>',
//           ),
//         );

        $block[$sibling['pid']] = [
          '#type' => 'container',
          '#attributes' => [],
          'link' => [
            '#type' => 'link',
            '#title' => $sibling['title'],
            '#href' => "islandora/object/{$sibling['pid']}",
            '#options' => ['query' => $query_params],
          ],
          'content' => [
            '#theme' => 'link',
            '#text' => \Drupal::service("renderer")->render($img),
            '#path' => "islandora/object/{$sibling['pid']}",
            '#options' => [
              'attributes' => [],
              'html' => TRUE,
              'query' => $query_params,
            ],
          ],
        ];
      }
    }
  }
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
  module_load_include('inc', 'islandora', 'includes/utilities');
  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
// 
// 
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_css(drupal_get_path('module', 'islandora_compound_object') . '/css/islandora_compound_object.block.css');

  $object = $object ? $object : \Drupal::routeMatch()->getParameter('islandora_object', 2);
  if ($object) {
    $output = '';
    if ($object) {
      $compound_info = islandora_compound_object_retrieve_compound_info($object, TRUE);
      // This object is part of a compound object.
      if (!empty($compound_info)) {
        // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $prev_next = theme('islandora_compound_prev_next', $compound_info);

        $output .= $prev_next;
      }

      if (!empty($output)) {
        return ['#markup' => $output];
      }
    }
  }
}
