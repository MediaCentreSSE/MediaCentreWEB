<?php

namespace Drupal\event_form;

use Drupal\content_page\Form\SubscriptionForm;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\node\Entity\Node;
use Drupal\event_form\Form\EventForm;
use Drupal\block_content\Entity\BlockContent;
use Drupal\event_form\Entity\EventApplicant;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\simplenews\Subscription\SubscriptionManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventFormManager.
 */
class EventFormManager {

  use StringTranslationTrait;

  /**
   * Default event form fields.
   *
   * Only field machine names are required, labels will be retrieved from
   * applicant dummy entity field definitions.
   *
   * @var array
   */
  public const DEFAULT_FIELDS = [
    'field_email', 'field_name', 'field_surname', 'field_phone',
    'field_organisation_or_school', 'field_position_or_grade',
  ];

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
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $pluginManagerMail;

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Drupal\simplenews\Subscription\SubscriptionManager definition.
   *
   * @var \Drupal\simplenews\Subscription\SubscriptionManager
   */
  protected $subscriptionManager;

  /**
   * Form message.
   *
   * @var string
   */
  protected $message = '';

  /**
   * Constructs a new EventFormManager object.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    RequestStack $request_stack,
    MailManagerInterface $plugin_manager_mail,
    FormBuilder $form_builder,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory,
    SubscriptionManager $simplenews_subscription_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->pluginManagerMail = $plugin_manager_mail;
    $this->formBuilder = $form_builder;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('event_form');
    $this->subscriptionManager = $simplenews_subscription_manager;
  }

  /**
   * Gets the event form.
   *
   * @param \Drupal\node\Entity\Node $event
   *   The node.
   *
   * @return array|null
   *   The event form.
   */
  public function getEventForm(Node $event) {
    $custom_block = $event->get('field_event_form')->entity;
    $form = EventForm::create(\Drupal::getContainer());
    $form->setEvent($event);
    $form = $this->formBuilder->getForm($form);

    // If form not available anymore, create it, but do not render it, so that
    // an ajax response could be created for form submissions that were made,
    // when the form was available.
    if (
      !$this->hasOpenRegistration($event, $custom_block)
      || !$this->hasAvailableSeats($event, $custom_block)
    ) {
      return NULL;
    }

    return $form;
  }

  /**
   * Gets the message.
   *
   * @return string
   *   The message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Determines if registration open.
   *
   * @param \Drupal\node\Entity\Node $event
   *   The event.
   * @param \Drupal\block_content\Entity\BlockContent $block_content
   *   The block content.
   *
   * @return bool
   *   Has/has not any available seats.
   */
  public function hasOpenRegistration(Node $event, BlockContent $block_content) {
    if ($block_content->get('field_registration_closed')->value) {
      $this->message = $this->t('Registration is closed for this event.', [], ['context' => 'event form']);
      return FALSE;
    }

    $start_date = $event->get('field_date_from')->value;
    if ($start_time = $event->get('field_time_from')->value) {
      $start_date .= ' ' . $start_time . ':00';
    }
    else {
      $start_date .= ' 00:00:00';
    }

    if (strtotime($start_date) <= time()) {
      $this->message = $this->t('Registration is closed for this event.', [], ['context' => 'event form']);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Determines if event has any available seats.
   *
   * @param \Drupal\node\Entity\Node $event
   *   The event.
   * @param \Drupal\block_content\Entity\BlockContent $block_content
   *   The block content.
   *
   * @return bool
   *   Has/has not any available seats.
   */
  public function hasAvailableSeats(Node $event, BlockContent $block_content) {
    $max_applicants = $block_content->get('field_max_number_of_applicants')->value;
    if (!$max_applicants) {
      return TRUE;
    }

    $applicant_ids = $this->getEventApplicants($event->id(), $block_content->id());
    if (count($applicant_ids) >= $max_applicants) {
      $this->message = $this->t('All seats have been taken for this event.', [], ['context' => 'event form']);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the event applicant ids.
   *
   * @param int $event_id
   *   The event identifier.
   * @param int $event_form_id
   *   The event form identifier.
   *
   * @return array
   *   The event applicants.
   */
  private function getEventApplicants($event_id, $event_form_id) {
    $applicant_storage = $this->entityTypeManager->getStorage('event_applicant');
    $applicant_ids = $applicant_storage->getQuery()
      ->condition('field_event', $event_id)
      ->condition('field_event_form', $event_form_id)
      ->execute();

    return $applicant_ids;
  }

  /**
   * Creates an event applicant.
   *
   * @param array $values
   *   The form values.
   * @param int $event_id
   *   The event identifier.
   * @param int $event_form_id
   *   The event form identifier.
   *
   * @return \Drupal\event_form\Entity\EventApplicant
   *   Event applicant entity.
   */
  public function createApplicant(array $values, $event_id, $event_form_id) {
    $applicant = EventApplicant::create([
      'name' => '',
      'field_event' => [
        'target_id' => $event_id
      ],
      'field_event_form' => [
        'target_id' => $event_form_id
      ],
      'field_email' => $values['email'],
      'field_name' => $values['name'],
      'field_surname' => $values['surname'],
      'field_phone' => $values['phone'],
      'field_organisation_or_school' => $values['organisation_or_school'],
      'field_position_or_grade' => $values['position_or_grade'],
      'field_ip_address' => $values['ip_address'],
      'field_notification_status' => EventApplicant::NOTIFICATION_STATUS_PENDING,
    ]);
    $applicant->save();

    foreach ($values['additional'] as $field_name => $value) {
      $additional_field = Paragraph::create([
        'type' => 'additional_form_data',
        'field_field_name' => $field_name,
        'field_field_value' => $value,
      ]);
      $additional_field->save();

      $applicant->get('field_additional_form_data')->appendItem([
        'target_id' => $additional_field->id(),
        'target_revision_id' => $additional_field->getRevisionId()
      ]);
      $applicant->save();
    }

    return $applicant;
  }

  /**
   * Sends an applicant confirmation.
   *
   * @param \Drupal\event_form\Entity\EventApplicant $applicant
   *   The applicant.
   * @param \Drupal\node\Entity\Node $event
   *   The event.
   */
  public function sendApplicantConfirmation(EventApplicant $applicant, Node $event) {
    if ($start_time = $event->get('field_time_from')->value) {
      $start = strtotime($event->get('field_date_from')->value . ' ' . $start_time . ':00');
      $start_date = date('d.m.Y H:i', $start);
    }
    else {
      $start_date = date('d.m.Y', strtotime($event->get('field_date_from')->value . ' 00:00:00'));
    }

    $body = $this->t('Hello! This e-mail confirms that you have successfully submitted your form.', [], ['context' => 'event']) . '<br/><br/>';

    // Add default field labels and values.
    $dummy_applicant = EventApplicant::create();
    foreach (self::DEFAULT_FIELDS as $field) {
      if (!$applicant->get($field)->value) {
        continue;
      }

      $body .= '<strong>' . $dummy_applicant->get($field)->getFieldDefinition()->getLabel() . ':</strong> ';
      $body .= $applicant->get($field)->value . '<br/>';
    }

    // Add additional field labels and values.
    $additional_data = $applicant->get('field_additional_form_data')->referencedEntities();
    $additional = [];

    foreach ($additional_data as $field) {
      $additional[$field->get('field_field_name')->value] = $field->get('field_field_value')->value;
    }

    $event_form = $applicant->get('field_event_form')->entity;
    $additional_fields = $event_form->get('field_additional_fields')->referencedEntities();

    foreach ($additional_fields as $additional_field) {
      $field_label = $additional_field->get('field_label')->value;

      if (isset($additional[$field_label])) {
        $body .= '<strong>' . $field_label . ':</strong> ';
        $body .= (string) $additional[$field_label] . '<br/>';
      }
    }

    // Add event information.
    $body .= '<br/>' . $this->t('Event information:', [], ['context' => 'event']) . '<br/>';
    $body .= '<strong><a target="_blank" href="' . $event->toUrl('canonical', ['absolute' => TRUE])->toString() . '">';
    $body .= $event->get('title')->value . '</a></strong><br/>';
    $body .= '<strong>' . $start_date . '</strong><br/>';

    // Add footer.
    $body .= '<br/>' . $this->t('Stockholm School of Economics in Riga', [], ['context' => 'event']);
    $body .= '<br/>' . $this->t('Strelnieku iela 4a, Riga LV 1010, Latvia', [], ['context' => 'event']);
    $body .= '<br/>' . $this->t('Phone: +371 67015800', [], ['context' => 'event']);
    $body .= '<br/>' . $this->t('office@sseriga.edu', [], ['context' => 'event']);

    $params['subject'] = $event->get('field_event_form')->entity->get('field_subject')->value;
    $params['body'] = $body;

    $this->pluginManagerMail->mail(
      'event_form',
      'event_form_confirmation',
      $applicant->get('field_email')->value,
      'en',
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Sends applicant notifications.
   *
   * @return int
   *   Sent notification count.
   */
  public function sendApplicantNotifications() {
    $this->logger->info('Checking for event form applicant notifications.');
    $notifications_sent = 0;
    $applicant_ids = $this->entityTypeManager
      ->getStorage('event_applicant')
      ->getQuery()
      ->condition('field_notification_status', 'pending')
      ->execute();

    if (!$applicant_ids) {
      $this->logger->info('Sent ' . $notifications_sent . ' applicant notifications.');
      return $notifications_sent;
    }

    $applicants = EventApplicant::loadMultiple($applicant_ids);
    foreach ($applicants as $applicant) {
      // If event/event form deleted, skip applicant.
      if (
        !$applicant->get('field_event')->target_id
        || !$applicant->get('field_event_form')->target_id
      ) {
        continue;
      }

      // Check if event form requires applicant notifications.
      $event_form = $applicant->get('field_event_form')->entity;
      if (!($notify_before = $event_form->get('field_alert_before')->value)) {
        continue;
      }

      // Get event start time.
      $event = $applicant->get('field_event')->entity;
      if ($start_time = $event->get('field_time_from')->value) {
        $start = strtotime($event->get('field_date_from')->value . ' ' . $start_time . ':00');
      }
      else {
        $start = strtotime($event->get('field_date_from')->value . ' 00:00:00');
      }

      // Notify applicant only if notification time was < 1 hour ago, to avoid
      // sending a notification to applicants that applied before the event
      // started but after notification time.
      $notification_time = $start - ($notify_before * 60 * 60);
      if ((time() - $notification_time >= 0) && (time() - $notification_time < 60 * 60)) {
        $sent = $this->sendApplicantNotification($applicant, $event);
        if ($sent) {
          $applicant->set('field_notification_status', 'sent');
          $notifications_sent++;
        }
        else {
          $applicant->set('field_notification_status', 'failed');
        }
        $applicant->save();
      }
    }
    $this->logger->info('Sent ' . $notifications_sent . ' applicant notifications.');

    return $notifications_sent;
  }

  /**
   * Sends an applicant confirmation.
   *
   * @param \Drupal\event_form\Entity\EventApplicant $applicant
   *   The applicant.
   * @param \Drupal\node\Entity\Node $event
   *   The event.
   */
  private function sendApplicantNotification(EventApplicant $applicant, Node $event) {
    if ($start_time = $event->get('field_time_from')->value) {
      $start = strtotime($event->get('field_date_from')->value . ' ' . $start_time . ':00');
      $start_date = date('d.m.Y H:i', $start);
    }
    else {
      $start_date = date('d.m.Y', strtotime($event->get('field_date_from')->value . ' 00:00:00'));
    }

    $body = $this->t('Hello! This is a reminder that you have registered to attend the following event.', [], ['context' => 'event']) . '<br/><br/>';

    // Add event information.
    $body .= $this->t('Event information:', [], ['context' => 'event']) . '<br/>';
    $body .= '<strong><a target="_blank" href="' . $event->toUrl('canonical', ['absolute' => TRUE])->toString() . '">';
    $body .= $event->get('title')->value . '</a></strong><br/>';
    $body .= '<strong>' . $start_date . '</strong><br/>';

    // Add footer.
    $body .= '<br/>' . $this->t('Stockholm School of Economics in Riga', [], ['context' => 'event']);
    $body .= '<br/>' . $this->t('Strelnieku iela 4a, Riga LV 1010, Latvia', [], ['context' => 'event']);
    $body .= '<br/>' . $this->t('Phone: +371 67015800', [], ['context' => 'event']);
    $body .= '<br/>' . $this->t('office@sseriga.edu', [], ['context' => 'event']);

    $params['subject'] = $event->get('field_event_form')->entity->get('field_subject')->value;
    $params['body'] = $body;

    return $this->pluginManagerMail->mail(
      'event_form',
      'event_form_applicant_notification',
      $applicant->get('field_email')->value,
      'en',
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Sends admin notifications.
   *
   * @return int
   *   Sent notification count.
   */
  public function sendAdminNotifications() {
    $this->logger->info('Checking for event form admin notifications.');
    $notifications_sent = 0;

    // Get all events with an event form and a start date from today onward.
    $event_ids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'event')
      ->condition('field_date_from', date('Y-m-d'), '>=')
      ->condition('field_event_form', NULL, 'IS NOT NULL')
      ->execute();

    if (!count($event_ids)) {
      return $notifications_sent;
    }

    $events = Node::loadMultiple($event_ids);
    foreach ($events as $event) {
      $event_form = $event->get('field_event_form')->entity;
      $recipients = $this->sendAdminNotification($event_form, $event);
      if ($recipients) {
        $this->logger->info(sprintf(
          'Sent event "%s" (event form "%s") admin notifications to %s.',
          $event->get('title')->value,
          $event_form->get('info')->value,
          implode(', ', $recipients)
        ));
      }

      $notifications_sent += count($recipients);
    }
    $this->logger->info('Sent ' . $notifications_sent . ' admin notifications.');

    return $notifications_sent;
  }

  /**
   * Sends an admin notification.
   *
   * @param \Drupal\block_content\Entity\BlockContent $event_form
   *   The event form.
   * @param \Drupal\node\Entity\Node $event
   *   The event.
   *
   * @return array
   *   Recipients.
   */
  private function sendAdminNotification(BlockContent $event_form, Node $event) {
    $recipients = explode("\n", str_replace("\r", '', $event->get('field_event_form_recipients')->value));
    foreach ($recipients as $index => $recipient) {
      if (!$recipient) {
        unset($recipients[$index]);
      }
    }

    if (!count($recipients)) {
      $this->logger->error('Failed sending admin notification for ' . $event->get('title')->value . ' - no recipients.');
      return [];
    }

    $applicant_ids = $this->getEventApplicants($event->id(), $event_form->id());

    // If no applicants, skip file creation.
    if (!count($applicant_ids)) {
      $params['files'] = [];
      $body = $this->t('No applicants for this event.', [], ['context' => 'event']);
    }
    // Create file at attach it to mail.
    else {
      $file = $this->getApplicantDataXls($applicant_ids, $event_form);
      $attachment = new \stdClass();
      $attachment->uri = $file;
      $attachment->filename = 'applicants.xlsx';
      $attachment->filemime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      $params['files'][] = $attachment;

      $body = $this->t(
        'Notification about event applicants (applicant data attached in .xls file).',
        [],
        ['context' => 'event']
      );
    }

    // Compose mail body.
    if ($start_time = $event->get('field_time_from')->value) {
      $start = strtotime($event->get('field_date_from')->value . ' ' . $start_time . ':00');
      $start_date = date('d.m.Y H:i', $start);
    }
    else {
      $start_date = date('d.m.Y', strtotime($event->get('field_date_from')->value . ' 00:00:00'));
    }

    $body .= '<br/><br/>';
    $body .= $this->t('Event information:', [], ['context' => 'event']) . '<br/>';
    $body .= '<strong><a target="_blank" href="' . $event->toUrl('canonical', ['absolute' => TRUE])->toString() . '">';
    $body .= $event->get('title')->value . '</a></strong><br/>';
    $body .= '<strong>' . $start_date . '</strong>';

    $params['body'] = $body;
    $params['subject'] = $this->t('Notification about event applicants', [], ['context' => 'event']) . ' - ' . $event->get('title')->value;

    // Send mail.
    $sent = $this->pluginManagerMail->mail(
      'event_form',
      'event_form_admin_notification',
      implode(',', $recipients),
      'en',
      $params,
      NULL,
      TRUE
    );

    // Remove file.
    if (count($applicant_ids)) {
      unlink($file);
    }

    return $sent ? $recipients : [];
  }

  /**
   * Gets the applicant data xls.
   *
   * @param array $applicant_ids
   *   The applicant identifiers.
   * @param \Drupal\block_content\Entity\BlockContent $event_form
   *   The event form.
   *
   * @return string
   *   Absolute path to file.
   */
  public function getApplicantDataXls(array $applicant_ids, BlockContent $event_form) {
    $columns = range('A', 'Z');
    $column_count = 0;
    $dummy_applicant = EventApplicant::create();

    $additional_data = $event_form->get('field_additional_fields')->referencedEntities();
    $additional_fields = [];

    foreach ($additional_data as $data) {
      $additional_fields[] = $data->get('field_label')->value;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header row values.
    foreach (self::DEFAULT_FIELDS as $field) {
      $sheet->setCellValue($columns[$column_count] . '1', $dummy_applicant->get($field)->getFieldDefinition()->getLabel());
      $column_count++;
    }
    foreach ($additional_fields as $field) {
      $sheet->setCellValue($columns[$column_count] . '1', $field);
      $column_count++;
    }
    $sheet->setCellValue($columns[$column_count] . '1', 'Newsletter');
    $column_count++;
    $sheet->setCellValue($columns[$column_count] . '1', 'Created');
    $column_count++;

    // Apply header style.
    $header_style = [
      'font' => [
        'bold' => TRUE,
      ],
    ];
    $sheet->getStyle('A1:' . $columns[$column_count - 1] . '1')->applyFromArray($header_style);

    // Insert applicant data.
    $applicants = EventApplicant::loadMultiple($applicant_ids);
    $row = 2;

    foreach ($applicants as $applicant) {
      $column = 0;

      // Add default field values.
      foreach (self::DEFAULT_FIELDS as $field) {
        $sheet->setCellValue($columns[$column] . $row, $applicant->get($field)->value);
        $column++;
      }

      // Get additional data paragraphs.
      $additional_data = $applicant->get('field_additional_form_data')->referencedEntities();
      $additional = [];

      // Format additional data paragraphs to match additional fields column names.
      foreach ($additional_data as $field) {
        $additional[$field->get('field_field_name')->value] = $field->get('field_field_value')->value;
      }

      // Add additional field values.
      foreach ($additional_fields as $field) {
        if (isset($additional[$field])) {
          $sheet->setCellValue($columns[$column] . $row, $additional[$field]);
        }
        $column++;
      }

      $newsletter = $this->subscriptionManager->isSubscribed(
        $applicant->get('field_email')->value, SubscriptionForm::NEWSLETTER_DEFAULT
      ) ? 'Yes' : 'No';
      $sheet->setCellValue($columns[$column] . $row, $newsletter);
      $column++;
      $sheet->setCellValue($columns[$column] . $row, date('d.m.Y H:i', $applicant->created->value));
      $column++;

      $row++;
    }

    // Resize columns to auto-width.
    for ($i = 0; $i < $column_count; $i++) {
      $sheet->getColumnDimension($columns[$i])->setAutoSize(TRUE);
    }

    // Write to file, modify permissions to 777.
    $file_uri = 'public://applicants.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($this->fileSystem->realpath($file_uri));
    chmod($this->fileSystem->realpath($file_uri), 0777);

    return $this->fileSystem->realpath($file_uri);
  }

  /**
   * Validates default fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateDefaultFields(FormStateInterface $form_state) {
    foreach (self::DEFAULT_FIELDS as $field) {
      $field_name = str_replace('field_', '', $field);
      if ($form_state->getValue($field_name)) {
        $form_state->setValue($field_name, strip_tags($form_state->getValue($field_name)));
      }
    }
  }

}
