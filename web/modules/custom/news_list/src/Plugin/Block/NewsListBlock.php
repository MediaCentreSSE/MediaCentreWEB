<?php

namespace Drupal\news_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\news_list\NewsListManager;

/**
 * Provides a 'NewsListBlock' block.
 *
 * @Block(
 *   id = "news_list_block",
 *   admin_label = @Translation("News list block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class NewsListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\news_list\NewsListManager definition.
   *
   * @var \Drupal\news_list\NewsListManager
   */
  protected $newsListManager;

  /**
   * Constructs a new NewsListBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\news_list\NewsListManager $news_list_manager
   *   The news list manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NewsListManager $news_list_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->newsListManager = $news_list_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('news_list.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $news_data = $this->newsListManager->getNewsItems($node->id());
    $pager = pager_default_initialize($news_data['total'], NewsListManager::PAGE_SIZE);

    return [
      '#theme' => 'news_list_block',
      '#node' => $node,
      '#items' => $news_data['items'],
    ];
  }

}
