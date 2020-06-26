<?php

namespace Drupal\research;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ResearchManager.
 */
class ResearchManager {

  /**
   * Default page size.
   *
   * @var int
   */
  const PAGE_SIZE = 5;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Contains current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new ResearchManager object.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    RequestStack $request_stack
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Gets a page of research items.
   *
   * @param \Drupal\node\Entity\Node $research_page
   *   The research page.
   *
   * @return array
   *   The research data.
   */
  public function getResearchItems(Node $research_page) {
    $page = $this->request->query->get('page') ?: 0;
    $page_size = self::PAGE_SIZE;

    $references = $research_page->get('field_research_papers')->getValue();
    if ($page * $page_size > count($references)) {
      $page = 0;
    }

    $references = array_reverse($references);
    $items = array_slice($references, $page * $page_size, $page_size);
    foreach ($items as $index => $item) {
      $items[$index] = Paragraph::load($item['target_id']);
    }

    $data = [
      'items' => $items,
      'total' => count($references),
    ];

    return $data;
  }

}
