<?php

namespace Drupal\content_page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Form\FormStateInterface;
use Datetime;
use Exception;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Class CustomFormManager.
 */
class CustomFormManager {

  use StringTranslationTrait;

  /**
   * Date field value format.
   *
   * @var string
   */
  const DATE_FORMAT = 'd.m.Y';

  /**
   * Contains current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Current form public form id.
   *
   * @var string
   */
  private $formId;

  /**
   * Constructs a new CustomFormManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    RequestStack $request_stack
  ) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Sets the public form identifier attribute.
   *
   * @param string $form_id
   *   The form identifier.
   */
  public function setFormId($form_id) {
    $this->formId = '#' . str_replace('_', '-', $form_id);
  }

  /**
   * Validates custom fields.
   *
   * @param array $fields
   *   The fields.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Exception
   *   Date creation exception.
   */
  public function validateCustomFields(array $fields, FormStateInterface $form_state) {
    foreach ($fields as $index => $field) {
      if ($form_state->getValue('field_' . $index)) {
        $form_state->setValue('field_' . $index, strip_tags($form_state->getValue('field_' . $index)));
      }

      if ($field->get('field_type')->value == 'date') {
        $date_field_value = $form_state->getValue('field_' . $index);

        // Allow for empty date value, if field is not required.
        if (!$field->get('field_required')->value && $date_field_value === '') {
          continue;
        }

        try {
          $date = DateTime::createFromFormat(self::DATE_FORMAT, $date_field_value);
          $errors = DateTime::getLastErrors();

          if (
            !$date
            || $errors['warning_count']
            || $errors['error_count']
            || ($date < new DateTime('1900-01-01'))
            || ($date > new \DateTime('2100-01-01'))
          ) {
            throw new Exception();
          }
        }
        catch (Exception $e) {
          $form_state->setErrorByName('field_' . $index, $this->t('Date invalid', [], ['context' => 'custom contact form']));
        }
      }
    }
  }

  /**
   * Builds custom fields.
   *
   * @param array $fields
   *   The fields.
   * @param array $form
   *   The form.
   *
   * @return array
   *   The form with custom fields.
   */
  public function buildCustomFields(array $fields, array $form) {
    foreach ($fields as $index => $field) {
      $form['field_' . $index] = [
        '#title' => $field->get('field_label')->value,
        '#title_display' => '',
        '#theme_wrappers' => [],
        '#required' => (bool) $field->get('field_required')->value,
        '#default_value' => '',
      ];

      if ($field->get('field_type')->value == 'date') {
        $form['field_' . $index]['#type'] = 'textfield';
        $form['field_' . $index]['#attributes'] = [
          'class' => [
            'datefield'
          ],
        ];
      }
      else {
        $form['field_' . $index]['#type'] = $field->get('field_type')->value;
      }
    }

    return $form;
  }

  /**
   * Gets the client ip.
   *
   * @return string
   *   The client ip.
   */
  public function getClientIp() {
    return $this->request->getClientIp();
  }

  /**
   * Gets the form response base.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The form response base.
   */
  private function getFormBaseResponse() {
    $response = new AjaxResponse();
    $response
      ->addCommand(new InvokeCommand($this->formId . ' [data-field="submit"]', 'removeClass', ['loading']))
      ->addCommand(new InvokeCommand($this->formId . ' .invalid', 'removeClass', ['invalid']))
      ->addCommand(new InvokeCommand($this->formId . ' .inputblock-message', 'remove', []));

    return $response;
  }

  /**
   * Gets the form error response.
   *
   * @param array $errors
   *   The errors.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The form error response.
   */
  public function getFormErrorResponse(array $errors) {
    $response = $this->getFormBaseResponse();

    foreach ($errors as $field_name => $error) {
      if ($field_name == 'submit') {
        $response->addCommand(new InvokeCommand(
          $this->formId,
          'append',
          ['<div class="inputblock-message error">' . $error->render() . '</div>']
        ));
      }
      else {
        $response->addCommand(new InvokeCommand(
          $this->formId . ' [data-field="' . $field_name . '"]',
          'addClass',
          ['invalid']
        ));
        $response->addCommand(new InvokeCommand(
          $this->formId . ' [data-field="' . $field_name . '"] .inputblock-input',
          'append',
          ['<div class="inputblock-message error">' . $error->render() . '</div>']
        ));
      }
    }

    return $response;
  }

  /**
   * Gets the form success response.
   *
   * @param string $message
   *   The message.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The form success response.
   */
  public function getFormSuccessResponse($message) {
    $response = $this->getFormBaseResponse();
    $response->addCommand(new InvokeCommand(
      $this->formId . ' .bottom',
      'append',
      ['<div class="inputblock-message success">' . $message . '</div>']
    ));
    $response->addCommand(new InvokeCommand(
      $this->formId . ' .button-wrapper',
      'append',
      ['<div class="thank-you">Thank you!</div>']
    ));
    $response->addCommand(new InvokeCommand(
      $this->formId . ' .inputblock-input input',
      'val',
      ['']
    ));

    return $response;
  }

  /**
   * Gets the applicant email - first field with a type "email" and a value.
   *
   * @param array $fields
   *   The fields.
   * @param array $values
   *   The values.
   *
   * @return string|null
   *   The applicant email if one found.
   */
  public function getApplicantEmail(array $fields, array $values) {
    foreach ($fields as $index => $field) {
      if ($field->get('field_type')->value == 'email' && $values['field_' . $index]) {
        return $values['field_' . $index];
      }
    }

    return NULL;
  }

}
