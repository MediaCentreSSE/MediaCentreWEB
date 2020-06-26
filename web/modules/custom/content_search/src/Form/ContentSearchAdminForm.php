<?php

namespace Drupal\content_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\content_search\ContentSearchAdminManager;
use Drupal\content_search\ContentSearchManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ContentSearchAdminForm.
 */
class ContentSearchAdminForm extends FormBase {

  /**
   * Content search admin manager.
   *
   * @var \Drupal\content_search\ContentSearchAdminManager
   */
  protected $contentSearchAdminManager;

  /**
   * Content search manager.
   *
   * @var \Drupal\content_search\ContentSearchManager
   */
  protected $contentSearchManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ContentSearchAdminForm object.
   *
   * @param \Drupal\content_search\ContentSearchAdminManager $content_search_admin_manager
   *   The content search admin manager.
   * @param \Drupal\content_search\ContentSearchManager $content_search_manager
   *   The content search manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    ContentSearchAdminManager $content_search_admin_manager,
    ContentSearchManager $content_search_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->contentSearchAdminManager = $content_search_admin_manager;
    $this->contentSearchManager = $content_search_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_search.admin_manager'),
      $container->get('content_search.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_search_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'content_search/admin';

    $indices = $this->contentSearchAdminManager->getIndices();
    if (!in_array(ContentSearchManager::INDEX_CONTENT, $indices)) {
      $form['create_index_description'] = [
        '#markup' => '<p>No page index found.</p>'
      ];
      $form['create_index'] = [
        '#type' => 'submit',
        '#value' => 'Create index',
        '#button_type' => 'primary',
        '#submit' => ['::createIndex']
      ];
      return $form;
    }

    $index_description = 'Currently <span id="currently-indexed">' . $this->contentSearchAdminManager->getDocumentCount() . '</span> / ';
    $index_description .= count($this->contentSearchAdminManager->getNodes()) . ' pages indexed.';
    $form['index_description'] = [
      '#markup' => '<p>' . $index_description . '</p>'
    ];

    $form['start'] = [
      '#type' => 'hidden',
      '#default_value' => 0
    ];
    $form['reindex'] = [
      '#type' => 'submit',
      '#value' => 'Reindex all pages',
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'reindexNodes'],
        'event' => 'send',
        'progress' => [
          'type' => '',
          'message' => ''
        ],
      ],
      '#suffix' => '<br/><br/>Please keep this window open while reindexing.',
    ];
    $form['reindex_progress'] = [
      '#markup' => '<div class="form-item hidden" data-field="progress"><div class="progress" data-drupal-progress>
        <div class="progress__track"><div class="progress__bar" style="width: 0%"></div></div>
        <div class="progress__percentage"></div>
        <div class="progress__description"></div>
      </div></div>'
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => 'Settings'
    ];
    $form['settings']['search_result_page_size'] = [
      '#type' => 'number',
      '#title' => 'Search result page size',
      '#min' => 1,
      '#default_value' => $this->configFactory->get('content_search.settings')->get('search_result_page_size') ?: 5,
    ];
    $form['settings']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save settings',
      '#button_type' => 'primary',
      '#submit' => ['::saveSettings'],
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => 'Advanced'
    ];
    $form['advanced']['index_description'] = [
      '#markup' => '<p>Deletes the current node index.</p>'
    ];
    $form['advanced']['delete_index'] = [
      '#type' => 'submit',
      '#value' => 'Delete index',
      '#button_type' => 'primary',
      '#submit' => ['::deleteIndex']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Saves settings.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function saveSettings(array &$form, FormStateInterface $form_state) {
    $this->configFactory
      ->getEditable('content_search.settings')
      ->set('search_result_page_size', $form_state->getValue('search_result_page_size'))
      ->save();
    drupal_set_message('Settings saved successfully!');
  }

  /**
   * Requires contentSearchAdminManager to create an index.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function createIndex(array &$form, FormStateInterface $form_state) {
    $response = $this->contentSearchAdminManager->createIndex();
    if (count($response)) {
      drupal_set_message('Page index created successfully!');
    }
  }

  /**
   * Requires contentSearchAdminManager to reindex all nodes.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function reindexNodes(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $start = $form_state->getValue('start');
    $limit = 50;
    $total = count($this->contentSearchAdminManager->getNodes());

    $count = $this->contentSearchAdminManager->reindexNodes($start, $limit);
    if (($start + $limit) >= $total) {
      drupal_set_message('Pages reindexed successfully! Currently indexed page count may not be accurate immediately after reindexing.');
      return $response
        ->addCommand(new CssCommand('.progress__bar', ['width' => '100%']))
        ->addCommand(new InvokeCommand('#edit-reindex', 'trigger', ['disable']))
        ->addCommand(new RedirectCommand(Url::fromRoute('content_search.admin_form')->toString()));
    }
    else {
      return $response
        ->addCommand(new InvokeCommand('input[name="start"]', 'val', [$start + $limit]))
        ->addCommand(new CssCommand('.progress__bar', ['width' => (((($start + $count) / $total) * 100) . '%')]))
        ->addCommand(new InvokeCommand('#edit-reindex', 'trigger', ['continue']))
        ->addCommand(new InvokeCommand('#currently-indexed', 'html', [$start + $count]));
    }
  }

  /**
   * Delete the node index.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function deleteIndex(array &$form, FormStateInterface $form_state) {
    $this->contentSearchAdminManager->deleteIndex();
  }

}
