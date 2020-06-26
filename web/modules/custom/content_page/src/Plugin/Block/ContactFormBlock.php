<?php

namespace Drupal\content_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\content_page\Form\CustomContactForm;

/**
 * Provides a 'ContactFormBlock' block.
 *
 * @Block(
 *   id = "contact_form_block",
 *   admin_label = @Translation("Contact form block")
 * )
 */
class ContactFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new ContactFormBlock object.
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
    $config = $this->getConfiguration();
    $node = $config['node'] ?? NULL;
    if (!$node || !$node->hasField('field_contact_form') || !$node->get('field_contact_form')->target_id) {
      return [
        '#markup' => '',
      ];
    }

    $custom_block = $node->get('field_contact_form')->entity;
    $form = CustomContactForm::create(\Drupal::getContainer());
    $form->setCustomBlock($custom_block);
    $form = $this->formBuilder->getForm($form);

    return [
      '#theme' => 'contact_form_block',
      '#form' => $form,
      '#content' => $custom_block,
      '#attached' => [
        'library' => [
          'content_page/custom_contact_form'
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
