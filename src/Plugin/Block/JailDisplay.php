<?php

namespace Drupal\islandora_compound_object\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block for navigating a compound object's children using JAIL.
 *
 * @Block(
 *   id = "compound_jail_display",
 *   admin_label = @Translation("Islandora Compound Object JAIL Display"),
 * )
 */
class JailDisplay extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'islandora_compound_object', 'includes/blocks');
    $nav = islandora_compound_object_jail_display_block();
    if ($nav) {
      return $nav;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if ($this->config('islandora_compound_object.settings')->get('islandora_compound_object_use_jail_view')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
