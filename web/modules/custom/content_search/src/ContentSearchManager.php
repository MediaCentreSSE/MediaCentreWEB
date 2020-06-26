<?php

namespace Drupal\content_search;

use Drupal\Core\Entity\EntityTypeManager;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Elasticsearch\ClientBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ContentSearchManager.
 */
class ContentSearchManager {

  /**
   * Content index name.
   *
   * @var string
   */
  const INDEX_CONTENT = 'sse_content_index';

  /**
   * Content type name.
   *
   * @var string
   */
  const TYPE_CONTENT = 'sse_content_type';

  /**
   * Sort by relevance.
   *
   * @var string
   */
  const SORT_RELEVANCE = 'relevance';

  /**
   * Sort by creation date.
   *
   * @var string
   */
  const SORT_DATE = 'date';

  /**
   * Highlight field priority.
   *
   * @var array
   */
  const HIGHLIGHT_FIELD_PRIORITY = [
    'body_text',
    'lead_text',
    'summary_text',
    'about_text',
    'location',
  ];

  /**
   * Search highlight fragment size.
   *
   * @var int
   */
  const FRAGMENT_SIZE = 500;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Elasticsearch client entity.
   *
   * @var \Elasticsearch\Client
   */
  private $searchClient;

  /**
   * Constructs a new ContentSearchManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('content_search.settings');
  }

  /**
   * Gets the search client.
   *
   * @return \Elasticsearch\Client
   *   The search client.
   */
  public function getSearchClient() {
    if (!$this->searchClient) {
      $this->createSearchClient();
    }
    return $this->searchClient;
  }

  /**
   * Gets the page size.
   *
   * @return int
   *   The page size.
   */
  public function getPageSize() {
    return $this->config->get('search_result_page_size');
  }

  /**
   * Gets the sorts.
   *
   * @return array
   *   The sorts.
   */
  public static function getSorts() {
    return [
      self::SORT_RELEVANCE,
      self::SORT_DATE,
    ];
  }

  /**
   * Creates a search client.
   */
  private function createSearchClient() {
    $this->searchClient = ClientBuilder::create()->build();
  }

  /**
   * Searches the content index.
   *
   * @param string $query
   *   The query.
   * @param int $page
   *   The page.
   * @param string $sort
   *   Sort type.
   *
   * @return array
   *   Client response.
   */
  public function searchIndex($query, $page = 0, $sort = self::SORT_RELEVANCE) {
    $sort_params = [];

    if ($sort == self::SORT_DATE) {
      $sort_params = [
        'sort' => [
          'created' => [
            'order' => 'desc',
          ],
        ],
      ];
    }

    $query_params = [
      'explain' => TRUE,
      'query' => [
        'bool' => [
          'should' => [
            [
              'multi_match' => [
                'query' => $query,
                'type' => 'most_fields',
                'operator' => 'or',
                'fields' => [
                  'title^2',
                  'body_text',
                  'lead_text',
                  'summary_text',
                  'about_text',
                  'location',
                ],
              ],
            ],
            [
              'match_phrase' => [
                'title' => [
                  'query' => $query,
                  'boost' => 2,
                ],
              ],
            ],
            [
              'match_phrase' => [
                'body_text' => [
                  'query' => $query,
                  'boost' => 1,
                ],
              ],
            ],
            [
              'match_phrase' => [
                'lead_text' => [
                  'query' => $query,
                  'boost' => 1,
                ],
              ],
            ],
            [
              'match_phrase' => [
                'summary_text' => [
                  'query' => $query,
                  'boost' => 1,
                ],
              ],
            ],
            [
              'match_phrase' => [
                'about_text' => [
                  'query' => $query,
                  'boost' => 1,
                ],
              ],
            ],
            [
              'match_phrase' => [
                'location' => [
                  'query' => $query,
                  'boost' => 1,
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $highlight_fields = [];
    foreach (self::HIGHLIGHT_FIELD_PRIORITY as $field) {
      $highlight_fields[$field] = new \stdClass();
    }

    $highlight_params = [
      'highlight' => [
        'pre_tags' => ['<em>'],
        'post_tags' => ['</em>'],
        'fragment_size' => self::FRAGMENT_SIZE,
        'number_of_fragments' => 1,
        'boundary_scanner' => 'word',
        'type' => 'fvh',
        'fields' => $highlight_fields,
      ],
    ];

    $parameters = [
      'index' => self::INDEX_CONTENT,
      'type' => self::TYPE_CONTENT,
      'size' => $this->getPageSize(),
      'from' => $page * $this->getPageSize(),
      'body' => array_merge($sort_params, $query_params, $highlight_params),
    ];

    try {
      return $this->getSearchClient()->search($parameters);
    }
    catch (Missing404Exception $exception) {
      return [];
    }
    catch (NoNodesAvailableException $exception) {
      return [];
    }
  }

  /**
   * Gets the main highlight.
   *
   * @param array $hit
   *   The search result.
   *
   * @return string
   *   The main highlight.
   */
  public function getMainHighlight(array $hit) {
    $highlights = $hit['highlight'] ?? [];
    $highlight = '';
    $highlight_fieldname = '';

    // Get highlights from the first field in which a highlight was made.
    // Field order is set in $highlight_params['highlight']['fields'].
    $first_field_highlight = reset($highlights);
    if ($first_field_highlight) {
      // Get first (only one available if 'number_of_fragments' => 1) highlight
      // from this field.
      $highlight = reset($first_field_highlight);
      $highlight_fieldname = key($highlights);
    }

    // If no highlight could be made, create one from source.
    else {
      foreach (self::HIGHLIGHT_FIELD_PRIORITY as $field) {
        if (isset($hit['_source'][$field])) {
          $highlight = substr($hit['_source'][$field], 0, self::FRAGMENT_SIZE);
          break;
        }
      }
    }

    if ($highlight) {
      $prefix = '';
      $suffix = '';

      if ($highlight_fieldname && isset($hit['_source'][$highlight_fieldname])) {
        // If highlight's first 50 characters do not match source field's first
        // 50 characters, prepend ellispsis.
        if (substr(strip_tags($highlight), 0, 50) != substr($hit['_source'][$highlight_fieldname], 0, 50)) {
          $prefix = "&hellip;";
        }

        // If highlight's last 50 characters do not match source field's last
        // 50 characters, append ellispsis.
        if (substr(strip_tags($highlight), -50) != substr($hit['_source'][$highlight_fieldname], -50)) {
          $suffix = "&hellip;";
        }
      }

      $highlight = str_replace("&ndash;", '-', $highlight);
      $highlight = str_replace("&nbsp;", ' ', $highlight);
      $highlight = trim($highlight, "\t\n )(&;");
      $highlight = ltrim($highlight, " -?");

      $highlight = $prefix . $highlight . $suffix;
    }

    return $highlight;
  }

}
