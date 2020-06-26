<?php

namespace Drupal\content_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_search\ContentSearchManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'SearchResultsBlock' block.
 *
 * @Block(
 *   id = "search_results_block",
 *   admin_label = @Translation("Search results block"),
 * )
 */
class SearchResultsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\content_search\ContentSearchManager definition.
   *
   * @var \Drupal\content_search\ContentSearchManager
   */
  protected $contentSearchManager;

  /**
   * Contains current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new SearchResultsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_search\ContentSearchManager $content_search_manager
   *   The content search manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContentSearchManager $content_search_manager,
    RequestStack $request_stack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contentSearchManager = $content_search_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_search.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = [];

    if (($query = $this->request->query->get('query')) && strlen($query) > 2) {
      $page = (int) $this->request->query->get('page') ?: 0;
      $page_size = (int) $this->contentSearchManager->getPageSize();
      $sort = in_array($this->request->query->get('sort'), ContentSearchManager::getSorts())
        ? $this->request->query->get('sort')
        : ContentSearchManager::SORT_RELEVANCE;

      $data = [
        'query' => $query,
        'page' => $page,
        'page_size' => $page_size,
        'sort' => $sort,
      ];
      $data = array_merge($data, $this->contentSearchManager->searchIndex($query, $page, $sort));

      if (isset($data['hits']['hits'])) {
        foreach ($data['hits']['hits'] as $index => $hit) {
          $data['hits']['hits'][$index]['main_highlight'] = $this->contentSearchManager->getMainHighlight($hit);
        }
      }

      pager_default_initialize($data['hits']['total'], $page_size);
    }
    else {
      $data['no_search'] = TRUE;
    }

    return [
      '#theme' => 'search_results_block',
      '#data' => $data,
      '#cache' => [
        'max-age' => 0
      ]
    ];
  }

}
