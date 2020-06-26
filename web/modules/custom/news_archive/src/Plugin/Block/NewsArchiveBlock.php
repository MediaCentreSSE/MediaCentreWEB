<?php

namespace Drupal\news_archive\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\news_list\NewsListManager;

/**
 * Provides a 'NewsArchiveBlock' block.
 *
 * @Block(
 *   id = "news_archive_block",
 *   admin_label = @Translation("News archive block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class NewsArchiveBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\news_list\NewsListManager definition.
   *
   * @var \Drupal\news_list\NewsListManager
   */
  protected $newsListManager;

  /**
   * Constructs a new NewsArchiveBlock object.
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
    $news_data = $this->newsListManager->getArchivedNewsItems($node->id());
    $pager = pager_default_initialize($news_data['total'], NewsListManager::PAGE_SIZE);

    return [
      '#theme' => 'news_archive_block',
      '#items' => $news_data['items'],
      '#years' => $news_data['years'],
      '#months' => $news_data['months'],
      '#current_year' => $news_data['current_year'],
      '#current_month' => $news_data['current_month'],
      '#attached' => [
        'library' => [
          'news_archive/form',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'url.path', 'url.query_args'
        ]
      ],
    ];
  }

}
