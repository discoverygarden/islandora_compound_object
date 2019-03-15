<?php

namespace Drupal\islandora_compound_object\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Provides a block for navigating a compound object's children using JAIL.
 *
 * @Block(
 *   id = "compound_jail_display",
 *   admin_label = @Translation("Islandora Compound Object JAIL Display"),
 * )
 */
class JailDisplay extends BlockBase implements ContainerFactoryPluginInterface {

  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('islandora_compound_object.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'islandora_compound_object', 'includes/blocks');
    return islandora_compound_object_jail_display_block();
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($this->config->get('islandora_compound_object_use_jail_view'))
      ->addCacheableDependency($this->config);
  }

}
