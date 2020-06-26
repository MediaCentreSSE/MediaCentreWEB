<?php

namespace Drupal\content_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simplenews\Subscription\SubscriptionManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Class SubscriptionForm.
 */
class SubscriptionForm extends FormBase {

  /**
   * Default newsletter.
   *
   * @var string
   */
  const NEWSLETTER_DEFAULT = 'default';

  /**
   * Drupal\simplenews\Subscription\SubscriptionManager definition.
   *
   * @var \Drupal\simplenews\Subscription\SubscriptionManager
   */
  protected $subscriptionManager;

  /**
   * Constructs a new SubscriptionForm object.
   *
   * @param \Drupal\simplenews\Subscription\SubscriptionManager $simplenews_subscription_manager
   *   The simplenews subscription manager.
   */
  public function __construct(
    SubscriptionManager $simplenews_subscription_manager
  ) {
    $this->subscriptionManager = $simplenews_subscription_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simplenews.subscription_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail', [], ['context' => 'subscription']),
      '#title_display' => '',
      '#theme_wrappers' => [],
      '#required' => TRUE,
    ];

    $form['subscribe'] = [
      '#type' => 'button',
      '#value' => '',
      '#attributes' => [
        'style' => 'display: none !important;'
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

    // Clear validation messages.
    drupal_get_messages();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response
      ->addCommand(new InvokeCommand('[data-field="subscribe"]', 'removeClass', ['loading']))
      ->addCommand(new InvokeCommand('[data-field="subscribe"]', 'removeAttr', ['disabled']))
      ->addCommand(new InvokeCommand('#subscription-form .invalid', 'removeClass', ['invalid']))
      ->addCommand(new InvokeCommand('#subscription-form .inputblock-message', 'remove', []));

    $errors = $form_state->getErrors();
    $values = $form_state->getValues();

    // Clear validation messages.
    drupal_get_messages();

    // Create error response.
    if (count($errors)) {
      foreach ($errors as $field_name => $error) {
        $response->addCommand(new InvokeCommand('[data-field="' . $field_name . '"]', 'addClass', ['invalid']));
        $response->addCommand(new InvokeCommand(
          '[data-field="' . $field_name . '"] .inputblock-input',
          'append',
          ['<div class="inputblock-message error">' . $error->render() . '</div>']
        ));
      }

      return $response;
    }

    // Add to newsletter subscribers if not subscribed. No error should be shown
    // if already subscribed for privacy reasons.
    if (!$this->subscriptionManager->isSubscribed($values['email'], self::NEWSLETTER_DEFAULT)) {
      $this->subscriptionManager->subscribe($values['email'], self::NEWSLETTER_DEFAULT);
    }

    // Create a success response command.
    $success_message = $this->t('Subscription approved!', [], ['context' => 'subscription']);
    $response->addCommand(new InvokeCommand(
      '[data-field="email"] .inputblock-input',
      'append',
      ['<div class="inputblock-message">' . $success_message . '</div>']
    ));

    return $response;
  }

}
