<?php

namespace Drupal\event_form\Controller;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\event_form\EventFormManager;
use Drupal\views\Views;
use Drupal\views\Plugin\views\pager\None;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class EventFormAdminController.
 */
class EventFormAdminController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\event_form\EventFormManager definition.
   *
   * @var \Drupal\event_form\EventFormManager
   */
  protected $eventFormManager;

  /**
   * Constructs a new EventFormAdminController object.
   */
  public function __construct(
    RequestStack $request_stack,
    EventFormManager $event_form_manager
  ) {
    $this->requestStack = $request_stack;
    $this->eventFormManager = $event_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('event_form.manager')
    );
  }

  /**
   * Returns file with filtered, unpaged results.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   File download response.
   */
  public function getEventApplicantExport() {
    // Build applicant view and apply query values.
    $view = Views::getView('event_applicants');
    $args = $this->requestStack->getCurrentRequest()->query->all();

    if (empty($args["field_event_form_target_id"])) {
      return new Response(
        'You must specify Event form ID',
        Response::HTTP_BAD_REQUEST
      );
    }

    $event_form = BlockContent::load($args["field_event_form_target_id"]);
    if (!$event_form || 'event_form' !== $event_form->type->target_id) {
      return new Response(
        'Form not found',
        Response::HTTP_BAD_REQUEST
      );
    }

    $view->setArguments($args);
    $view->initDisplay();
    $view->initQuery();
    $view->preExecute();

    // Unpage the results with this weird 'None' pager.
    $view->pager = new None([], '', []);
    $view->pager->init($view, $view->display_handler);
    $view->execute();

    // Retrieve applicant entities from results.
    $applicants = [];
    foreach ($view->result as $result) {
      $applicants[] = $result->_entity->id();
    }

    $file = $this->eventFormManager->getApplicantDataXls($applicants, $event_form);

    $response = new BinaryFileResponse($file);
    $response->deleteFileAfterSend(TRUE);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      'applicants.xlsx'
    );

    return $response;
  }

}
