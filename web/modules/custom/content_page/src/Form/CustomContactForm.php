<?php

namespace Drupal\content_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\content_page\CustomFormManager;

/**
 * Class CustomContactForm.
 */
class CustomContactForm extends FormBase {

  /**
   * Block content.
   *
   * @var \Drupal\block_content\Entity\BlockContent
   */
  protected $blockContent;

  /**
   * Custom form fields.
   *
   * @var array
   */
  protected $fields;

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $pluginManagerMail;

  /**
   * Drupal\content_page\CustomFormManager definition.
   *
   * @var \Drupal\content_page\CustomFormManager
   */
  protected $customFormManager;

  /**
   * Constructs a new CustomContactForm object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $plugin_manager_mail
   *   The plugin manager mail.
   * @param \Drupal\content_page\CustomFormManager $custom_form_manager
   *   The custom form manager.
   */
  public function __construct(
    MailManagerInterface $plugin_manager_mail,
    CustomFormManager $custom_form_manager
  ) {
    $this->pluginManagerMail = $plugin_manager_mail;
    $this->customFormManager = $custom_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('content_page.custom_form_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_contact_form';
  }

  /**
   * Sets the block content.
   *
   * @param \Drupal\block_content\Entity\BlockContent $block_content
   *   The block content.
   */
  public function setCustomBlock(BlockContent $block_content) {
    $this->blockContent = $block_content;
    $this->fields = $block_content->get('field_fields')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(FALSE);
    $form = $this->customFormManager->buildCustomFields($this->fields, $form);

    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit', [], ['context' => 'custom contact form']),
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
    $this->customFormManager->validateCustomFields($this->fields, $form_state);

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

    // Send emails containing form data.
    $values = $form_state->getValues();

    if ($applicant_email = $this->customFormManager->getApplicantEmail($this->fields, $values)) {
      $this->sendApplicantContactEmail($form, $values, $applicant_email);
    }
    $this->sendAdminContactEmail($form, $values);

    $success_message = $this->t('Form submitted successfully!', [], ['context' => 'custom contact form']);

    return $this->customFormManager->getFormSuccessResponse($success_message);
  }

  /**
   * Sends a contact email.
   *
   * @param array $form
   *   The form.
   * @param array $values
   *   The values.
   * @param string $applicant_email
   *   The applicant e-mail address.
   */
  private function sendApplicantContactEmail(array $form, array $values, $applicant_email) {
    $body = $this->t('Hello! This e-mail confirms that you have successfully submitted your form.', [], ['context' => 'custom contact form']) . '<br/><br/>';

    // Add field labels and values.
    foreach ($this->fields as $index => $field) {
      $field_name = 'field_' . $index;
      $body .= '<strong>' . $form[$field_name]['#title'] . ':</strong> ';
      $body .= $values[$field_name] . '<br/>';
    }

    // Add footer.
    $body .= '<br/>' . $this->t('Stockholm School of Economics in Riga', [], ['context' => 'custom contact form']);
    $body .= '<br/>' . $this->t('Strelnieku iela 4a, Riga LV 1010, Latvia', [], ['context' => 'custom contact form']);
    $body .= '<br/>' . $this->t('Phone: +371 67015800', [], ['context' => 'custom contact form']);
    $body .= '<br/>' . $this->t('office@sseriga.edu', [], ['context' => 'custom contact form']);

    $params['subject'] = $this->blockContent->get('field_subject')->value;
    $params['body'] = $body;

    $this->pluginManagerMail->mail(
      'content_page',
      'custom_contact_form',
      $applicant_email,
      'en',
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Sends a contact email.
   *
   * @param array $form
   *   The form.
   * @param array $values
   *   The values.
   */
  private function sendAdminContactEmail(array $form, array $values) {
    $body = '';

    foreach ($this->fields as $index => $field) {
      $field_name = 'field_' . $index;
      $body .= '<strong>' . $form[$field_name]['#title'] . ':</strong> ';
      $body .= $values[$field_name] . '<br/>';
    }

    $body .= '<br/><strong>' . $this->t('Sent at', [], ['context' => 'custom contact form']) . ':</strong> ' . date('d.m.Y H:i:s') . '<br/>';
    $body .= '<strong>' . $this->t('Sent from IP', [], ['context' => 'custom contact form']) . ':</strong> ' . $this->customFormManager->getClientIp();

    $params['subject'] = $this->blockContent->get('field_subject')->value;
    $params['body'] = $body;

    $recipients = explode("\n", str_replace("\r", '', $this->blockContent->get('field_recipients')->value));
    foreach ($recipients as $index => $recipient) {
      if (!$recipient) {
        unset($recipients[$index]);
      }
    }

    $recipients = implode(',', $recipients);

    $this->pluginManagerMail->mail(
      'content_page',
      'custom_contact_form',
      $recipients,
      'en',
      $params,
      NULL,
      TRUE
    );
  }

}
