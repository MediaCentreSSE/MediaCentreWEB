<?php

namespace Drupal\blog_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blog_list\BlogListManager;

/**
 * Provides a 'BlogListBlock' block.
 *
 * @Block(
 *   id = "blog_list_block",
 *   admin_label = @Translation("Blog list block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class BlogListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\blog_list\BlogListManager definition.
   *
   * @var \Drupal\blog_list\BlogListManager
   */
  protected $blogListManager;

  /**
   * Constructs a new BlogListBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\blog_list\BlogListManager $blog_list_manager
   *   The blog list manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    BlogListManager $blog_list_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blogListManager = $blog_list_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('blog_list.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $blog_data = $this->blogListManager->getBlogItems($node->id());
    $pager = pager_default_initialize($blog_data['total'], BlogListManager::PAGE_SIZE);

    return [
      '#theme' => 'blog_list_block',
      '#node' => $node,
      '#items' => $blog_data['items'],
    ];
  }

}
