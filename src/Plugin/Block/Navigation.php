<?php

namespace Drupal\islandora_compound_object\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Provides a block for navigating a compound object's children.
 *
 * @Block(
 *   id = "compound_navigation",
 *   admin_label = @Translation("Islandora Compound Object Navigation"),
 * )
 */
class Navigation extends BlockBase implements ContainerFactoryPluginInterface {

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
    return _islandora_compound_object_navigation_block();
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf(!$this->config->get('islandora_compound_object_use_jail_view'))
      ->addCacheableDependency($this->config);
  }

}
