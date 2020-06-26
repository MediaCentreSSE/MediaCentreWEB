<?php

namespace Drupal\news_list;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\content_page\ContentPageManager;
use Drupal\node\Entity\Node;

/**
 * Class NewsListManager.
 */
class NewsListManager {

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
   *   Current request.
   */
  protected $request;

  /**
   * Constructs a new NewsListManager object.
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
   * Gets a page of news items.
   *
   * @param int $news_list_id
   *   The news list ID.
   *
   * @return array
   *   The news data.
   */
  public function getNewsItems($news_list_id) {
    $storage = $this->entityTypeManager->getStorage('node');
    $total = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_NEWS)
      ->condition('field_news_list', $news_list_id)
      ->condition('status', 1)
      ->execute();

    $page = $this->request->query->get('page') ?: 0;
    $page_size = self::PAGE_SIZE;

    $items = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_NEWS)
      ->condition('field_news_list', $news_list_id)
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

  /**
   * Categorizes news items by months.
   *
   * @param array $items
   *   The items.
   *
   * @return array
   *   Categorized items.
   */
  private function categorizeByMonths(array $items) {
    $months = [];
    foreach ($items as $item) {
      $months[date('F', $item->created->value)][] = $item;
    }

    return $months;
  }

  /**
   * Gets a page of news items.
   *
   * @param int $news_list_id
   *   The news list ID.
   *
   * @return array
   *   The news data.
   */
  public function getLatestNews($news_list_id) {
    $news_ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_NEWS)
      ->condition('field_news_list', $news_list_id)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 4)
      ->execute();

    return count($news_ids) ? Node::loadMultiple($news_ids) : [];
  }

  /**
   * Gets a page of archived news items.
   *
   * @param int $news_list_id
   *   The news list ID.
   *
   * @return array
   *   The news data.
   */
  public function getArchivedNewsItems($news_list_id) {
    $query_year = $this->request->query->get('year');
    $query_month = $this->request->query->get('month');
    $page = $this->request->query->get('page') ? (int) $this->request->query->get('page') : 0;
    $storage = $this->entityTypeManager->getStorage('node');

    $news_items_ids = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_NEWS)
      ->condition('field_news_list', $news_list_id)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->execute();

    $news_items = Node::loadMultiple($news_items_ids);
    $unique_years = [];
    $unique_months = [];

    foreach ($news_items as $news_item) {
      $year = date('Y', $news_item->get('created')->value);
      if (!in_array($year, $unique_years)) {
        $unique_years[] = $year;
      }
    }

    $current_year = in_array($query_year, $unique_years) ? $query_year : reset($unique_years);

    foreach ($news_items as $news_item) {
      $year = date('Y', $news_item->get('created')->value);
      if ($year != $current_year) {
        continue;
      }

      $month = date('m', $news_item->get('created')->value);
      if (!in_array($month, $unique_months)) {
        $unique_months[] = $month;
      }
    }

    $current_month = in_array($query_month, $unique_months) ? $query_month : NULL;
    $current_news_items = [];

    foreach ($news_items as $news_item) {
      $year = date('Y', $news_item->get('created')->value);
      $month = date('m', $news_item->get('created')->value);

      if ($year == $current_year && (!$current_month || $month == $current_month)) {
        $current_news_items[] = $news_item;
      }
    }

    if (($page > (count($current_news_items) / self::PAGE_SIZE)) || $page < 0) {
      $page = 0;
    }

    $items = array_slice($current_news_items, $page * self::PAGE_SIZE, self::PAGE_SIZE);
    $items = $this->categorizeByMonths($items);

    $data = [
      'items' => $items,
      'total' => count($current_news_items),
      'years' => $unique_years,
      'months' => $unique_months,
      'current_year' => $current_year,
      'current_month' => $current_month,
    ];

    return $data;
  }

}
