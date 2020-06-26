<?php

namespace Drupal\blog_list;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\content_page\ContentPageManager;
use Drupal\node\Entity\Node;

/**
 * Class BlogListManager.
 */
class BlogListManager {

  /**
   * Default page size.
   *
   * @var int
   */
  const PAGE_SIZE = 9;

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
   *   Current request.
   */
  protected $request;

  /**
   * Constructs a new BlogListManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    RequestStack $request_stack
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Gets a page of blog items.
   *
   * @param int $blog_list_id
   *   The blog list ID.
   *
   * @return array
   *   The blog data.
   */
  public function getBlogItems($blog_list_id) {
    $storage = $this->entityTypeManager->getStorage('node');
    $total = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_BLOG)
      ->condition('field_blog_list', $blog_list_id)
      ->condition('status', 1)
      ->execute();

    $page = $this->request->query->get('page') ?: 0;
    $page_size = self::PAGE_SIZE;

    $items = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_BLOG)
      ->condition('field_blog_list', $blog_list_id)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range($page * $page_size, $page_size)
      ->execute();

    $items = Node::loadMultiple($items);

    $data = [
      'items' => $items,
      'total' => count($total),
    ];

    return $data;
  }

}
