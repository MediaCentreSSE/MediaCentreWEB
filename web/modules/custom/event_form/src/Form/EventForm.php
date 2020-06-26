<?php

namespace Drupal\event_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\event_form\EventFormManager;
use Drupal\content_page\CustomFormManager;
use Drupal\simplenews\Subscription\SubscriptionManager;
use Drupal\content_page\Form\SubscriptionForm;

/**
 * Class EventForm.
 */
class EventForm extends FormBase {

  /**
   * Event node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $event;

  /**
   * Custom block content.
   *
   * @var \Drupal\block_content\Entity\BlockContent
   */
  protected $blockContent;

  /**
   * Custom form fields.
   *
   * @var array
   */
  protected $additionalFields;

  /**
   * Drupal\event_form\EventFormManager definition.
   *
   * @var \Drupal\event_form\EventFormManager
   */
  protected $eventFormManager;

  /**
   * Drupal\content_page\CustomFormManager definition.
   *
   * @var \Drupal\content_page\CustomFormManager
   */
  protected $customFormManager;

  /**
   * Drupal\simplenews\Subscription\SubscriptionManager definition.
   *
   * @var \Drupal\simplenews\Subscription\SubscriptionManager
   */
  protected $subscriptionManager;

  /**
   * Constructs a new EventForm object.
   *
   * @param \Drupal\event_form\EventFormManager $event_form_manager
   *   The event form manager.
   * @param \Drupal\content_page\CustomFormManager $custom_form_manager
   *   The custom form manager.
   * @param \Drupal\simplenews\Subscription\SubscriptionManager $simplenews_subscription_manager
   *   The simplenews subscription manager.
   */
  public function __construct(
    EventFormManager $event_form_manager,
    CustomFormManager $custom_form_manager,
    SubscriptionManager $simplenews_subscription_manager
  ) {
    $this->eventFormManager = $event_form_manager;
    $this->customFormManager = $custom_form_manager;
    $this->subscriptionManager = $simplenews_subscription_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_form.manager'),
      $container->get('content_page.custom_form_manager'),
      $container->get('simplenews.subscription_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_form';
  }

  /**
   * Sets the event node.
   *
   * @param \Drupal\node\Entity\Node $event
   *   The node.
   */
  public function setEvent(Node $event) {
    $this->event = $event;
    $this->blockContent = $event->get('field_event_form')->entity;
    $this->additionalFields = $this->blockContent->get('field_additional_fields')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(FALSE);

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => TRUE,
    ];
    $form['surname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surname', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => TRUE,
    ];
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => FALSE,
    ];
    $form['organisation_or_school'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organisation or School', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => FALSE,
    ];
    $form['position_or_grade'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Position or Grade', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => FALSE,
    ];

    $form['subscription'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Newsletter', [], ['context' => 'event form']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => FALSE,
      '#attributes' => [
        'class' => ['form-checkbox checkbox-input'],
      ],
    ];

    $form = $this->customFormManager->buildCustomFields($this->additionalFields, $form);

    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit', [], ['context' => 'event form']),
      '#attributes' => [
        'class' => ['button-white-on-orange centered submit-registration']
      ],
      '#ajax' => [
        'callback' => [$this, 'submitForm'],
        'event' => 'click',
        'progress' => [
          'type' => '',
          'message' => '',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->eventFormManager->validateDefaultFields($form_state);
    $this->customFormManager->validateCustomFields($this->additionalFields, $form_state);

    // Check if any seats available.
    if (!$this->eventFormManager->hasAvailableSeats($this->event, $this->blockContent)) {
      $form_state->setErrorByName('submit', $this->eventFormManager->getMessage());
    }

    // Check if application time not expired.
    if (!$this->eventFormManager->hasOpenRegistration($this->event, $this->blockContent)) {
      $form_state->setErrorByName('submit', $this->eventFormManager->getMessage());
    }

    // Clear validation messages.
    drupal_get_messages();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear validation messages.
    drupal_get_messages();

    $form_state->cleanValues();
    $errors = $form_state->getErrors();
    $this->customFormManager->setFormId($this->getFormId());

    // Create error response.
    if (count($errors)) {
      return $this->customFormManager->getFormErrorResponse($errors);
    }

    $values = $form_state->getValues();
    $values['additional'] = [];

    // Move additional field values to a separate array entry.
    foreach ($values as $field_name => $value) {
      if (strpos($field_name, 'field_') === 0) {
        $values['additional'][$form[$field_name]['#title']] = $value;
        unset($values[$field_name]);
      }
    }

    // Create applicant entity.
    $values['ip_address'] = $this->customFormManager->getClientIp();
    $applicant = $this->eventFormManager->createApplicant($values, $this->event->id(), $this->blockContent->id());
    $this->eventFormManager->sendApplicantConfirmation($applicant, $this->event);

    // Add subscriber if checked.
    if ($values['subscription'] && !$this->subscriptionManager->isSubscribed($values['email'], SubscriptionForm::NEWSLETTER_DEFAULT)) {
      $this->subscriptionManager->subscribe($values['email'], SubscriptionForm::NEWSLETTER_DEFAULT);
    }

    $success_message = $this->t('Form submitted successfully!', [], ['context' => 'event form']);

    return $this->customFormManager->getFormSuccessResponse($success_message);
  }

}
