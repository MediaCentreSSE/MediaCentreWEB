<?php

namespace Drupal\event_list;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\content_page\ContentPageManager;
use Drupal\node\Entity\Node;

/**
 * Class EventListManager.
 */
class EventListManager {

  /**
   * Default page size.
   *
   * @var int
   */
  const PAGE_SIZE = 10;

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
   * Constructs a new EventListManager object.
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
   * Gets a page of event items.
   *
   * @param int $event_list_id
   *   The event list ID.
   *
   * @return array
   *   The event data.
   */
  public function getEventItems($event_list_id) {
    $query_year = $this->request->query->get('year');
    $query_month = $this->request->query->get('month');
    $page = $this->request->query->get('page') ? (int) $this->request->query->get('page') : 0;
    $storage = $this->entityTypeManager->getStorage('node');

    $event_items_ids = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_EVENT)
      ->condition('field_event_list', $event_list_id)
      ->condition('status', 1)
      ->sort('field_date_from', 'DESC')
      ->execute();

    $event_items = Node::loadMultiple($event_items_ids);
    $unique_years = [];
    $unique_months = [];

    foreach ($event_items as $event_item) {
      $timestamp = strtotime($event_item->get('field_date_from')->value);
      $year = date('Y', $timestamp);
      if (!in_array($year, $unique_years)) {
        $unique_years[] = $year;
      }
    }

    $current_year = in_array($query_year, $unique_years) ? $query_year : NULL;

    foreach ($event_items as $event_item) {
      $timestamp = strtotime($event_item->get('field_date_from')->value);
      $year = date('Y', $timestamp);
      if ($year != $current_year) {
        continue;
      }

      $month = date('m', $timestamp);
      if (!in_array($month, $unique_months)) {
        $unique_months[] = $month;
      }
    }

    $current_month = in_array($query_month, $unique_months) ? $query_month : NULL;
    $current_event_items = [];

    foreach ($event_items as $event_item) {
      $timestamp = strtotime($event_item->get('field_date_from')->value);
      $year = date('Y', $timestamp);
      $month = date('m', $timestamp);

      if ((!$current_year || $year == $current_year) && (!$current_month || $month == $current_month)) {
        $current_event_items[] = $event_item;
      }
    }

    if (($page > (count($current_event_items) / self::PAGE_SIZE)) || $page < 0) {
      $page = 0;
    }

    $items = array_slice($current_event_items, $page * self::PAGE_SIZE, self::PAGE_SIZE);

    $data = [
      'items' => $items,
      'total' => count($current_event_items),
      'years' => $unique_years,
      'months' => $unique_months,
      'current_year' => $current_year,
      'current_month' => $current_month,
    ];

    return $data;
  }

  /**
   * Categorizes event items by months.
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
      $date = strtotime($item->field_date_from->value);
      $months[date('F', $date)][] = $item;
    }

    return $months;
  }

  /**
   * Gets the upcoming events.
   *
   * @param int $event_list_id
   *   The event list identifier.
   *
   * @return array
   *   The upcoming events.
   */
  public function getUpcomingEvents($event_list_id) {
    $limit = 5;

    $event_ids = [];

    $future_event_ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_EVENT)
      ->condition('field_event_list', $event_list_id)
      ->condition('status', 1)
      ->range(0, $limit)
      ->sort('field_date_from', 'ASC')
      ->condition('field_date_from', date('Y-m-d'), '>=')
      ->execute();

    if (count($future_event_ids) < $limit) {
      $past_event_ids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', ContentPageManager::CONTENT_TYPE_EVENT)
        ->condition('field_event_list', $event_list_id)
        ->condition('status', 1)
        ->range(0, $limit - count($future_event_ids))
        ->sort('field_date_from', 'DESC')
        ->condition('field_date_from', date('Y-m-d'), '<')
        ->execute();

      $event_ids += array_reverse($past_event_ids);
    }

    $event_ids += $future_event_ids;

    return count($event_ids) ? Node::loadMultiple($event_ids) : [];
  }

  /**
   * Gets a page of archived event items.
   *
   * @param int $event_list_id
   *   The event list ID.
   *
   * @return array
   *   The event data.
   */
  public function getArchivedeventItems($event_list_id) {
    $query_year = $this->request->query->get('year');
    $query_month = $this->request->query->get('month');
    $page = $this->request->query->get('page') ? (int) $this->request->query->get('page') : 0;
    $storage = $this->entityTypeManager->getStorage('node');

    $event_items_ids = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_EVENT)
      ->condition('field_event_list', $event_list_id)
      ->condition('status', 1)
      ->sort('field_date_from', 'DESC')
      ->execute();

    $event_items = Node::loadMultiple($event_items_ids);
    $unique_years = [];
    $unique_months = [];

    foreach ($event_items as $event_item) {
      $timestamp = strtotime($event_item->get('field_date_from')->value);
      $year = date('Y', $timestamp);
      if (!in_array($year, $unique_years)) {
        $unique_years[] = $year;
      }
    }

    $current_year = in_array($query_year, $unique_years) ? $query_year : reset($unique_years);

    foreach ($event_items as $event_item) {
      $timestamp = strtotime($event_item->get('field_date_from')->value);
      $year = date('Y', $timestamp);
      if ($year != $current_year) {
        continue;
      }

      $month = date('m', $timestamp);
      if (!in_array($month, $unique_months)) {
        $unique_months[] = $month;
      }
    }

    $current_month = in_array($query_month, $unique_months) ? $query_month : NULL;
    $current_event_items = [];

    foreach ($event_items as $event_item) {
      $timestamp = strtotime($event_item->get('field_date_from')->value);
      $year = date('Y', $timestamp);
      $month = date('m', $timestamp);

      if ($year == $current_year && (!$current_month || $month == $current_month)) {
        $current_event_items[] = $event_item;
      }
    }

    if (($page > (count($current_event_items) / self::PAGE_SIZE)) || $page < 0) {
      $page = 0;
    }

    $items = array_slice($current_event_items, $page * self::PAGE_SIZE, self::PAGE_SIZE);
    $items = $this->categorizeByMonths($items);

    $data = [
      'items' => $items,
      'total' => count($current_event_items),
      'years' => $unique_years,
      'months' => $unique_months,
      'current_year' => $current_year,
      'current_month' => $current_month,
    ];

    return $data;
  }

}
