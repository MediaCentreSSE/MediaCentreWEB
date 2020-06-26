<?php

namespace Drupal\content_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\content_page\Form\SubscriptionForm;

/**
 * Provides a 'SubscriptionFormBlock' block.
 *
 * @Block(
 *   id = "subscription_form_block",
 *   admin_label = @Translation("Subscription form block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class SubscriptionFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new SubscriptionFormBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilder $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    // If page doesn't require a subscription form, return empty build array.
    if (!$node->field_show_newsletter_form->value) {
      return $this->getEmptyBuildArray();
    }

    $form = $this->formBuilder->getForm(SubscriptionForm::class);
    return [
      '#theme' => 'subscription_form_block',
      '#form' => $form,
      '#attached' => [
        'library' => [
          'content_page/subscription_form'
        ]
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Gets an empty build array.
   *
   * @return array
   *   An empty build array.
   */
  private function getEmptyBuildArray() {
    return [
      '#markup' => '',
    ];
  }

}
