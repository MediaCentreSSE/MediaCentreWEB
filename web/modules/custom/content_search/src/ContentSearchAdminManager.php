<?php

namespace Drupal\content_search;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\content_page\ContentPageManager;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ContentSearchAdminManager.
 */
class ContentSearchAdminManager {

  /**
   * Array of indexable node types.
   *
   * @var array
   */
  const INDEXABLE_NODE_TYPES = [
    ContentPageManager::CONTENT_TYPE_NEWS,
    ContentPageManager::CONTENT_TYPE_NEWS_LIST,
    ContentPageManager::CONTENT_TYPE_EVENT,
    ContentPageManager::CONTENT_TYPE_EVENT_LIST,
    ContentPageManager::CONTENT_TYPE_CONTENT_PAGE,
    ContentPageManager::CONTENT_TYPE_ADMINISTRATION,
    ContentPageManager::CONTENT_TYPE_FACULTY,
    ContentPageManager::CONTENT_TYPE_CONTACTS,
    ContentPageManager::CONTENT_TYPE_STAFF,
    ContentPageManager::CONTENT_TYPE_BLOG,
    ContentPageManager::CONTENT_TYPE_BLOG_LIST,
  ];

  /**
   * Drupal\content_search\ContentSearchManager definition.
   *
   * @var \Drupal\content_search\ContentSearchManager
   */
  protected $contentSearchManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentSearchAdminManager object.
   *
   * @param \Drupal\content_search\ContentSearchManager $content_search_manager
   *   The content search manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ContentSearchManager $content_search_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->contentSearchManager = $content_search_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the indices.
   *
   * @return array
   *   The indices.
   */
  public function getIndices() {
    try {
      $response = $this->contentSearchManager->getSearchClient()->indices()->stats();
      return array_keys($response['indices']);
    }
    catch (Missing404Exception $exception) {
      drupal_set_message('Failed to get indices.', 'error');
      return [];
    }
    catch (NoNodesAvailableException $exception) {
      drupal_set_message('Elasticsearch service offline.', 'error');
      return [];
    }
  }

  /**
   * Deletes an index.
   *
   * @return array
   *   Client response.
   */
  public function deleteIndex() {
    try {
      $response = $this->contentSearchManager->getSearchClient()->indices()->delete(['index' => ContentSearchManager::INDEX_CONTENT]);
      return $response;
    }
    catch (Missing404Exception $exception) {
      drupal_set_message('Failed to delete index.', 'error');
      return [];
    }
  }

  /**
   * Creates an index.
   *
   * @return array
   *   Client response.
   */
  public function createIndex() {
    try {
      $response = $this->contentSearchManager->getSearchClient()->indices()->create([
        'index' => ContentSearchManager::INDEX_CONTENT,
        'body' => [
          'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
          ],
          'mappings' => [
            '_default_' => [
              '_source' => [
                'enabled' => TRUE
              ],
              'properties' => [
                'name' => [
                  'type' => 'text',
                  'analyzer' => 'english',
                  'term_vector' => 'with_positions_offsets',
                ],
                'location' => [
                  'type' => 'text',
                  'analyzer' => 'english',
                  'term_vector' => 'with_positions_offsets',
                ],
                'body_text' => [
                  'type' => 'text',
                  'analyzer' => 'english',
                  'term_vector' => 'with_positions_offsets',
                ],
                'lead_text' => [
                  'type' => 'text',
                  'analyzer' => 'english',
                  'term_vector' => 'with_positions_offsets',
                ],
                'summary_text' => [
                  'type' => 'text',
                  'analyzer' => 'english',
                  'term_vector' => 'with_positions_offsets',
                ],
                'about_text' => [
                  'type' => 'text',
                  'analyzer' => 'english',
                  'term_vector' => 'with_positions_offsets',
                ],
                'created' => [
                  'type' => 'integer'
                ],
              ],
            ],
          ],
        ],
      ]);
      return $response;
    }
    catch (Missing404Exception $exception) {
      drupal_set_message('Failed to create index.', 'error');
      return [];
    }
    catch (NoNodesAvailableException $exception) {
      drupal_set_message('Elasticsearch service offline.', 'error');
      return [];
    }
  }

  /**
   * Creates a document for a content node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return array
   *   Client response.
   */
  public function createDocument(Node $node) {
    try {
      $params = [
        'index' => ContentSearchManager::INDEX_CONTENT,
        'id' => $node->id(),
        'type' => ContentSearchManager::TYPE_CONTENT,
        'body' => $this->getDocumentBody($node),
      ];
      $response = $this->contentSearchManager->getSearchClient()->index($params);
      return $response;
    }
    catch (Missing404Exception $exception) {
      drupal_set_message('Failed to create a node document.', 'error');
      return [];
    }
  }

  /**
   * Gets the document body.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return array
   *   The document body.
   */
  private function getDocumentBody(Node $node) {
    $body = [
      'created' => $node->get('created')->value,
      'title' => $node->get('title')->value,
      'lead_text' => '',
      'body_text' => '',
      'summary_text' => '',
      'about_text' => '',
      'location' => '',
    ];

    switch ($node->getType()) {
      case ContentPageManager::CONTENT_TYPE_EVENT:
        $body['summary_text'] = $this->processWysiwyg($node->get('field_summary')->value);
        $body['location'] = $node->get('field_location')->value;
      case ContentPageManager::CONTENT_TYPE_CONTENT_PAGE:
      case ContentPageManager::CONTENT_TYPE_NEWS:
      case ContentPageManager::CONTENT_TYPE_BLOG:
        $body['lead_text'] = $this->processWysiwyg($node->get('field_lead_text')->value);
        $body['body_text'] = $this->processWysiwyg($node->get('field_body')->value);
        $body['body_text'] .= ' ' . $this->processWysiwyg($node->get('field_body_2')->value);
        $body['body_text'] .= ' ' . $this->processWysiwyg($node->get('field_private_widget_title')->value);
        $body['body_text'] .= ' ' . $this->processWysiwyg($node->get('field_private_widget_text')->value);

        $merge_texts = [];
        $paragraph_fields = [
          'field_expandable_list_items',
          'field_file_attachments',
        ];
        foreach ($paragraph_fields as $field) {
          $paragraphs = $node->get($field)->referencedEntities();
          $paragraphs_text = $this->processParagraphs($paragraphs);

          if ($paragraphs_text) {
            $merge_texts[] = $paragraphs_text;
          }
        }

        if (count($merge_texts)) {
          $body['body_text'] .= ' ' . implode(' ', $merge_texts);
        }
        break;

      case ContentPageManager::CONTENT_TYPE_STAFF:
        $body['body_text'] = $this->processWysiwyg($node->get('field_body')->value);

        $merge_fields = ['field_email', 'field_phone', 'field_position'];
        $merge_texts = [];
        foreach ($merge_fields as $field) {
          if ($node->get($field)->value) {
            $merge_texts[] = $node->get($field)->value;
          }
        }

        $body['body_text'] .= ' ' . $this->processWysiwyg(implode(' ', $merge_texts));
        break;

      case ContentPageManager::CONTENT_TYPE_CATEGORY_PAGE:
        $body['about_text'] = $node->get('field_about')->value;
        break;

      case ContentPageManager::CONTENT_TYPE_CONTACTS:
        $merge_fields = [
          'field_column_1_title', 'field_column_2_title', 'field_email',
          'field_phone', 'field_column_1_text', 'field_column_2_text',
          'field_map_title'
        ];

        $merge_texts = [];
        foreach ($merge_fields as $field) {
          if ($node->get($field)->value) {
            $merge_texts[] = $node->get($field)->value;
          }
        }

        $paragraph_fields = ['field_contact_groups', 'field_markers'];
        foreach ($paragraph_fields as $field) {
          $paragraphs = $node->get($field)->referencedEntities();
          $paragraphs_text = $this->processParagraphs($paragraphs);

          if ($paragraphs_text) {
            $merge_texts[] = $paragraphs_text;
          }
        }

        $contact_groups = $node->get('field_contact_groups')->referencedEntities();

        $body['body_text'] = $this->processWysiwyg(implode(' ', $merge_texts));
        break;

      default:
        break;
    }

    return $body;
  }

  /**
   * Gets the document count.
   *
   * @return int
   *   Document count.
   */
  public function getDocumentCount() {
    try {
      $params = [
        'index' => ContentSearchManager::INDEX_CONTENT,
        'type' => ContentSearchManager::TYPE_CONTENT,
      ];
      $response = $this->contentSearchManager->getSearchClient()->count($params);
      return $response['count'];
    }
    catch (Missing404Exception $exception) {
      drupal_set_message('Failed to get document count.', 'error');
      return 0;
    }
  }

  /**
   * Gets all indexable node IDs.
   *
   * @return array
   *   Node ID array.
   */
  public function getNodes($start = 0, $limit = 0) {
    $node_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $node_query
      ->condition('type', self::INDEXABLE_NODE_TYPES, 'in')
      ->condition('status', 1);

    if ($limit) {
      $node_query->range($start, $limit);
    }

    return $node_query->execute();
  }

  /**
   * Reindexes all nodes.
   *
   * @return int
   *   Count of nodes that were attempted to be indexed.
   */
  public function reindexNodes($start, $limit) {
    // If reindexing from 0, delete all current node documents.
    if (!$start) {
      try {
        $params = [
          'index' => ContentSearchManager::INDEX_CONTENT,
          'type' => ContentSearchManager::TYPE_CONTENT,
          'body' => [
            'query' => [
              'match_all' => new \stdClass()
            ]
          ]
        ];
        $this->contentSearchManager->getSearchClient()->deleteByQuery($params);
      }
      catch (Missing404Exception $exception) {
        drupal_set_message('Failed to delete all node documents.', 'error');
        return [];
      }
    }

    // Reindex $limit nodes from $start.
    $node_ids = $this->getNodes($start, $limit);
    foreach ($node_ids as $node_id) {
      $node = Node::load($node_id);
      $this->createDocument($node);
    }

    return count($node_ids);
  }

  /**
   * Processes wysiwyg text for indexing.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The processed text.
   */
  private function processWysiwyg($text) {
    // Replace line breaks with spaces, to separate words.
    $text = str_replace('<br>', ' ', $text);
    $text = str_replace('<br/>', ' ', $text);

    // Strip formatting.
    $text = strip_tags($text);

    return $text;
  }

  /**
   * Processes paragraphs according to thier type.
   *
   * @param array $paragraphs
   *   The paragraphs.
   *
   * @return string
   *   Combined paragraph text value.
   */
  private function processParagraphs(array $paragraphs) {
    $texts = [];

    foreach ($paragraphs as $paragraph) {
      switch ($paragraph->getType()) {
        case 'contact_group':
          $text = $this->processParagraphContactGroup($paragraph);
          break;

        case 'marker':
          $text = $this->processParagraphMarker($paragraph);
          break;

        case 'expandable_list_item':
          $text = $this->processParagraphExpandableList($paragraph);
          break;

        case 'file_attachment':
          $text = $this->processParagraphFileAttachment($paragraph);
          break;

        case 'calendar_season':
          $text = $this->processParagraphCalendarSeasons($paragraph);
          break;

        default:
          $text = NULL;
          break;
      }

      if ($text) {
        $texts[] = $text;
      }
    }

    return count($texts) ? implode(' ', $texts) : '';
  }

  /**
   * Processes paragraph 'Contact group' fields.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph.
   *
   * @return string
   *   Processed paragraph text.
   */
  private function processParagraphContactGroup(Paragraph $paragraph) {
    $additional = $this->processWysiwyg($paragraph->get('field_additional')->value);
    $text = $paragraph->get('field_title')->value . ($additional ? ' ' . $additional : '');

    return $text;
  }

  /**
   * Processes paragraph 'Marker' fields.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph.
   *
   * @return string
   *   Processed paragraph text.
   */
  private function processParagraphMarker(Paragraph $paragraph) {
    $texts = [];
    $fields = [
      'field_title_long', 'field_phone', 'field_email', 'field_address', 'field_fax'
    ];

    foreach ($fields as $field) {
      if ($paragraph->get($field)->value) {
        $texts[] = $paragraph->get($field)->value;
      }
    }

    return count($texts) ? implode(' ', $texts) : '';
  }

  /**
   * Processes paragraph 'Expandable list item' fields.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph.
   *
   * @return string
   *   Processed paragraph text.
   */
  private function processParagraphExpandableList(Paragraph $paragraph) {
    $text = $this->processWysiwyg($paragraph->get('field_text')->value);
    $text = $paragraph->get('field_title')->value . ($text ? ' ' . $text : '');

    return $text;
  }

  /**
   * Processes paragraph 'File attachment' fields.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph.
   *
   * @return string
   *   Processed paragraph text.
   */
  private function processParagraphFileAttachment(Paragraph $paragraph) {
    if (!$paragraph->get('field_file')->target_id) {
      return '';
    }
    $file = $paragraph->get('field_file')->entity;

    return $paragraph->get('field_file')->description ?: $file->get('filename')->value;
  }

  /**
   * Processes paragraph 'Calendar - season' fields.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph.
   *
   * @return string
   *   Processed paragraph text.
   */
  private function processParagraphCalendarSeasons(Paragraph $paragraph) {
    $text = $paragraph->get('field_title')->value;
    $months = $paragraph->get('field_months')->referencedEntities();

    foreach ($months as $month) {
      $text .= ' ' . $month->get('field_title')->value;
      $events = $month->get('field_events')->referencedEntities();

      foreach ($events as $event) {
        $text .= ' ' . $event->get('field_title')->value;
        $text .= ' ' . $event->get('field_date_text')->value;
        $text .= ' ' . $event->get('field_language')->value;
      }
    }

    return $text;
  }

  /**
   * Deletes node document from index.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   */
  public function deleteDocument(Node $node) {
    try {
      $params = [
        'index' => ContentSearchManager::INDEX_CONTENT,
        'type' => ContentSearchManager::TYPE_CONTENT,
        'id' => $node->id(),
      ];
      $this->contentSearchManager->getSearchClient()->delete($params);
    }
    catch (Missing404Exception $exception) {
      drupal_set_message('Failed to delete indexed node document.', 'error');
    }
  }

}
