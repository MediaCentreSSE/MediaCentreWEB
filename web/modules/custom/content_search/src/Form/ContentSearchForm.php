<?php

namespace Drupal\content_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentSearchForm.
 */
class ContentSearchForm extends FormBase {

  /**
   * Contains current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new ContentSearchForm object.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['query'] = [
      '#type' => 'textfield',
      '#theme_wrappers' => [],
      '#default_value' => $this->request->query->get('query'),
      '#placeholder' => $this->t('Enter phrase', [], ['context' => 'search'])
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search', [], ['context' => 'search']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'content_search.search',
      [
        'query' => $form_state->getValue('query')
      ]
    );
  }

}
