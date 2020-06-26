<?php

namespace Drupal\content_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\content_page\ContentPageManager;
use Drupal\content_search\ContentSearchManager;

/**
 * Class ContentSearchController.
 */
class ContentSearchController extends ControllerBase {

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
   * Constructs a new ContentSearchController object.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Gets the search results page and redirects to it with the query.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A Symfony direct response object.
   */
  public function getSearchResultsPage() {
    $node_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $search_page_ids = $node_query
      ->condition('type', ContentPageManager::CONTENT_TYPE_SEARCH_RESULTS)
      ->range(0, 1)
      ->execute();

    $search_page_id = count($search_page_ids) ? reset($search_page_ids) : 0;
    if (!$search_page_id) {
      return $this->redirect('<front>');
    }

    $route_parameters = [
      'node' => $search_page_id,
      'query' => $this->request->query->get('query')
    ];

    $sort = $this->request->query->get('sort');
    if ($sort && in_array($sort, ContentSearchManager::getSorts())) {
      $route_parameters['sort'] = $this->request->query->get('sort');
    }

    return $this->redirect('entity.node.canonical', $route_parameters);
  }

}
