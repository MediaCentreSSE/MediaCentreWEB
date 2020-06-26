<?php

namespace Drupal\content_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\content_search\Form\ContentSearchForm;
use Drupal\content_page\ContentPageManager;

/**
 * Provides a 'HeaderSearchBlock' block.
 *
 * @Block(
 *   id = "header_search_block",
 *   admin_label = @Translation("Header search block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class HeaderSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new HeaderSearchBlock object.
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

    $search_form = ContentSearchForm::create(\Drupal::getContainer());
    $search_form = $this->formBuilder->getForm($search_form);
    $search_form['open'] = $node->getType() == ContentPageManager::CONTENT_TYPE_SEARCH_RESULTS;

    return [
      '#theme' => 'header_search_block',
      '#form' => $search_form,
      '#cache' => [
        'max-age' => 0
      ]
    ];
  }

}
