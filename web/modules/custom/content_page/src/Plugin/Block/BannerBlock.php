<?php

namespace Drupal\content_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_page\ContentPageManager;

/**
 * Provides a 'BannerBlock' block.
 *
 * @Block(
 *   id = "banner_block",
 *   admin_label = @Translation("Banner block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class BannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\content_page\ContentPageManager definition.
   *
   * @var \Drupal\content_page\ContentPageManager
   */
  protected $contentPageManager;

  /**
   * Constructs a new BannerBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_page\ContentPageManager $contentPageManager
   *   The content page manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContentPageManager $contentPageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contentPageManager = $contentPageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_page.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $depth = 0;
    $banner_title = $node ? $node->get('title')->value : '';

    $banner_node = NULL;

    if ($node->hasField('field_banner') && $node->get('field_banner')->target_id) {
      $banner_node = $node;
    }

    return [
      '#theme' => 'banner_block',
      '#depth' => $depth,
      '#node' => $banner_node,
      '#banner_title' => $banner_title,
    ];
  }

}
