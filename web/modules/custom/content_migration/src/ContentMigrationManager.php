<?php

namespace Drupal\content_migration;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Database\Database;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

/**
 * Class ContentMigrationManager.
 */
class ContentMigrationManager {

  /**
   * Owner ID of all migrated content nodes.
   *
   * @var int
   */
  const CONTENT_OWNER_ID = 1;

  /**
   * Content Type ID for basic content pages.
   *
   * @var string
   */
  const CONTENT_TYPE_PAGE = 'content_page';

  /**
   * Content Type ID for category pages.
   *
   * @var string
   */
  const CONTENT_TYPE_CATEGORY_PAGE = 'category_page';

  /**
   * Content Type for skipped pages.
   *
   * @var string
   */
  const CONTENT_TYPE_SKIP = 'skip';

  /**
   * Content Type ID for staff pages.
   *
   * @var string
   */
  const CONTENT_TYPE_STAFF = 'staff';

  /**
   * Content Type ID for news list pages.
   *
   * @var string
   */
  const CONTENT_TYPE_NEWS_LIST = 'news_list';

  /**
   * Content Type ID for news pages.
   *
   * @var string
   */
  const CONTENT_TYPE_NEWS = 'news';

  /**
   * Content Type ID for event list pages.
   *
   * @var string
   */
  const CONTENT_TYPE_EVENT_LIST = 'event_list';

  /**
   * Content Type ID for event pages.
   *
   * @var string
   */
  const CONTENT_TYPE_EVENT = 'event';

  /**
   * Content Type ID for printed matter.
   *
   * @var string
   */
  const CONTENT_TYPE_PRINTED_MATTER = 'printed_matter';

  /**
   * Content Type ID for printed matter.
   *
   * @var string
   */
  const CONTENT_TYPE_RESEARCH = 'research';

  /**
   * Content Type ID for blog.
   *
   * @var string
   */
  const CONTENT_TYPE_BLOG = 'blog';

  /**
   * Content Type ID for blog list.
   *
   * @var string
   */
  const CONTENT_TYPE_BLOG_LIST = 'blog_list';

  /**
   * External db ID. Set in settings.local.php.
   *
   * @var string
   */
  const DATABASE_EXTERNAL = 'external';

  /**
   * Default db ID. Set in settings.local.php.
   *
   * @var string
   */
  const DATABASE_DEFAULT = 'default';

  /**
   * Overview source content object type - taken from a news object.
   *
   * @var string
   */
  const OVERVIEW_TYPE_NEWS = 'news';

  /**
   * Overview source content object type - taken from a landing page block.
   *
   * @var string
   */
  const OVERVIEW_TYPE_LANDING_BLOCK = 'landing';

  /**
   * Vocabulary machine name for staff departments.
   *
   * @var string
   */
  const VOCABULARY_DEPARTMENTS = 'departments';

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Menu\MenuLinkManagerInterface definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Drupal\pathauto\PathautoGeneratorInterface definition.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGenerator;

  /**
   * Drupal\Core\Path\AliasStorageInterface definition.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Page array.
   *
   * @var array
   */
  private $pages = [];

  /**
   * Template array.
   *
   * @var array
   */
  private $templates = [];

  /**
   * Root menu item weight.
   *
   * @var int
   */
  private $rootMenuItemWeight = 0;

  /**
   * Constructs a new ContentMigrationManager object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MenuLinkManagerInterface $plugin_manager_menu_link,
    PathautoGeneratorInterface $pathauto_generator,
    AliasStorageInterface $alias_storage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $plugin_manager_menu_link;
    $this->pathautoGenerator = $pathauto_generator;
    $this->aliasStorage = $alias_storage;
  }

  /**
   * Performs initializing functions.
   *
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The i/o.
   * @param int $page_id
   *   The page identifier.
   *
   * @throws \Exception
   *   Exception.
   */
  public function initialize(DrupalStyle $io, $page_id) {
    // Load all page ids and their parent ids.
    $this->loadPageStructure($page_id);
    if (!isset($this->pages[$page_id])) {
      throw new \Exception('No page with ID = ' . $page_id . ' found');
    }

    // Load all template names.
    $this->loadTemplates();

    // Set root item menu entry weight.
    $this->rootMenuItemWeight = $io->choiceNoList('Root menu item weight:', range(0, 9));

    $boolean_choice = ['no', 'yes'];
    $delete = $io->choiceNoList('Delete all current content?', $boolean_choice);
    if ($delete == 'no') {
      return;
    }

    $delete = $io->choiceNoList('Really delete all current content?', $boolean_choice);
    if ($delete == 'no') {
      return;
    }

    $delete = $io->choiceNoList('Really, really delete all current content?', $boolean_choice);
    if ($delete == 'no') {
      return;
    }

    // Delete all nodes with any of the following content types:
    // CONTENT_TYPE_PAGE
    // CONTENT_TYPE_CATEGORY_PAGE
    // CONTENT_TYPE_STAFF
    // CONTENT_TYPE_NEWS_LIST
    // CONTENT_TYPE_NEWS
    // CONTENT_TYPE_EVENT_LIST
    // CONTENT_TYPE_EVENT
    // CONTENT_TYPE_PRINTED_MATTER
    // CONTENT_TYPE_RESEARCH
    $this->deleteAllNodes($io);
  }

  /**
   * Deletes all nodes with predefined content types.
   *
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The i/o.
   */
  public function deleteAllNodes(DrupalStyle $io) {
    $this->switchDatabase();

    $node_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $deletable_types = [
      self::CONTENT_TYPE_PAGE,
      self::CONTENT_TYPE_CATEGORY_PAGE,
      self::CONTENT_TYPE_STAFF,
      self::CONTENT_TYPE_NEWS_LIST,
      self::CONTENT_TYPE_NEWS,
      self::CONTENT_TYPE_EVENT_LIST,
      self::CONTENT_TYPE_EVENT,
      self::CONTENT_TYPE_PRINTED_MATTER,
      self::CONTENT_TYPE_RESEARCH,
    ];

    $node_ids = $node_query
      ->condition('type', $deletable_types, 'IN')
      ->execute();

    $boolean_choice = ['no', 'yes'];
    $delete = $io->choiceNoList(count($node_ids) . ' nodes will be deleted. Proceed?', $boolean_choice);
    if ($delete == 'no') {
      return;
    }

    foreach ($node_ids as $node_id) {
      $node = Node::load($node_id);
      $node->delete();
      dump($node->get('title')->value . ' - deleted');
    }
  }

  /**
   * Switches current database.
   *
   * @param string $database
   *   The database.
   */
  private function switchDatabase($database = NULL) {
    Database::setActiveConnection($database ?: self::DATABASE_DEFAULT);
  }

  /**
   * Loads the page structure.
   */
  public function loadPageStructure() {
    $this->switchDatabase(self::DATABASE_EXTERNAL);
    $connection = Database::getConnection();

    // Get all page ids with their parent ids.
    $query = $connection->select('ad_content', 'c');
    $query->fields('c', ['id', 'parent_id']);
    $query->orderBy('sort');

    $this->pages = $query->execute()->fetchAllAssoc('id');
  }

  /**
   * Loads the page templates.
   */
  public function loadTemplates() {
    $this->switchDatabase(self::DATABASE_EXTERNAL);
    $connection = Database::getConnection();

    // Get all page ids with their parent ids.
    $query = $connection->select('ad_templates', 't');
    $query->fields('t', ['id', 'filename']);
    $query->orderBy('id');

    $templates = $query->execute()->fetchAllAssoc('id');
    foreach ($templates as $template) {
      $this->templates[$template->id] = $template->filename;
    }
  }

  /**
   * Deletes all main menu items.
   */
  private function deleteMenuItems() {
    $this->switchDatabase();

    $menu_storage = $this->entityTypeManager->getStorage('menu_link_content');
    $menu_query = $menu_storage->getQuery();
    $menu_query->condition('menu_name', 'main');

    $menu_items = $menu_query->execute();
    $menu_items = $menu_storage->loadMultiple($menu_items);

    foreach ($menu_items as $menu_item) {
      $menu_item->delete();
    }
  }

  /**
   * Migrates the requested page and all its contents.
   *
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The i/o.
   * @param int $page_id
   *   The page identifier.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $parent_menu_item
   *   The parent menu item.
   */
  public function migratePage(DrupalStyle $io, $page_id, MenuLinkContent $parent_menu_item = NULL) {
    // Load page content and display content overview.
    $page_content = $this->loadPageContent($page_id);

    // Automatically assign/request a content type.
    $content_type = $this->getAutoContentType($page_content) ?: $io->choiceNoList(
      'Choose target Content Type (choose "Skip" to skip this page)',
      [
        self::CONTENT_TYPE_PAGE,
        self::CONTENT_TYPE_CATEGORY_PAGE,
        self::CONTENT_TYPE_NEWS_LIST,
        self::CONTENT_TYPE_EVENT_LIST,
        self::CONTENT_TYPE_SKIP,
        self::CONTENT_TYPE_PRINTED_MATTER,
        self::CONTENT_TYPE_RESEARCH,
      ]
    );

    // Process desired content type.
    switch ($content_type) {
      case self::CONTENT_TYPE_SKIP:
        $io->info('Skipping page ID = ' . $page_id . ' and all its child pages');
        return;

      case self::CONTENT_TYPE_PAGE:
        if (!count($page_content['news'])) {
          $io->comment('Content page has no news items, page content will be empty');
        }

        $menu_item = $this->createContentPage($page_content, $parent_menu_item);
        break;

      case self::CONTENT_TYPE_NEWS_LIST:
        $menu_item = $this->createNewsListPage($page_content, $parent_menu_item);
        break;

      case self::CONTENT_TYPE_EVENT_LIST:
        $menu_item = $this->createEventListPage($page_content, $parent_menu_item);
        break;

      case self::CONTENT_TYPE_CATEGORY_PAGE:
        $menu_item = $this->createCategoryPage($page_content, $parent_menu_item);
        break;

      case self::CONTENT_TYPE_PRINTED_MATTER:
        $menu_item = $this->createPrintedMatterPage($page_content, $parent_menu_item);
        break;

      case self::CONTENT_TYPE_RESEARCH:
        $menu_item = $this->createResearchPage($page_content, $parent_menu_item);
        break;
    }

    // Process child pages.
    foreach ($this->getChildPages($page_id) as $child_page_id) {
      $this->migratePage($io, $child_page_id, $menu_item);
    }
  }

  /**
   * Loads the page with its content.
   *
   * @param int $page_id
   *   The page identifier.
   *
   * @return array
   *   Page content array.
   */
  private function loadPageContent($page_id) {
    $this->switchDatabase(self::DATABASE_EXTERNAL);
    $content = [];
    $connection = Database::getConnection();

    // Get all fields.
    $query = $connection->select('ad_content', 'c');
    $query->condition('c.id', $page_id);
    $query->fields('c', [
      'id', 'url', 'lang', 'country', 'mirror_id', 'parent_id', 'type',
      'target', 'template', 'page_title', 'title', 'content', 'image',
      'image_alt', 'enable', 'active', 'sort', 'sitemap', 'cache', 'keywords',
      'description', 'created_user', 'created_date', 'edit_user', 'edit_date'
    ]);
    $content['page'] = $query->execute()->fetch();
    $content['page']->depth = $this->getDepth($content['page']->id);

    // Get all news associated with this page.
    $query = $connection->select('mod_news', 'n');
    $query->condition('n.content_id', $page_id);
    $query->fields('n', [
      'id', 'content_id', 'sort', 'created', 'updated', 'owner', 'enable',
      'title', 'date', 'show_date_public', 'lead', 'lead_image', 'text',
      'text_image', 'links', 'files', 'page_title', 'page_keywords',
      'page_description', 'page_url', 'widgets', 'shadow_form',
      'shadow_form_title', 'shadow_form_image', 'addTitle', 'addContent',
      'short_url'
    ]);
    $query->orderBy('created', 'DESC');
    $content['news'] = $query->execute()->fetchAllAssoc('id');

    // Get all news associated with this page.
    $query = $connection->select('mod_events', 'e');
    $query->condition('e.content_id', $page_id);
    $query->fields('e', [
      'id', 'content_id', 'copy_content_id', 'sort', 'date_from', 'date_to',
      'created', 'updated', 'owner', 'enable', 'title', 'date', 'lead',
      'lead_image', 'text', 'text_image', 'infobox', 'location', 'links',
      'files', 'page_title', 'page_keywords', 'page_description', 'page_url',
      'enable_form', 'free_places', 'widgets', 'time_from', 'time_to',
      'lead_image_alt', 'text_image_alt', 'addTitle', 'addContent',
      'form_email', 'reminder', 'additional_fields', 'short_url'
    ]);
    $content['events'] = $query->execute()->fetchAllAssoc('id');

    // Get all printed matter associated with this page.
    $query = $connection->select('mod_printedmatter', 'p');
    $query->fields('p', [
      'id', 'title_en', 'lead_en', 'lead_image', 'file', 'date', 'sort'
    ]);
    $query->orderBy('sort', 'DESC');
    $content['printed_matter'] = $query->execute()->fetchAllAssoc('id');

    // Get all research papers associated with this page.
    $query = $connection->select('mod_research_papers', 'r');
    $query->fields('r', [
      'id', 'title_en', 'lead_en', 'author', 'year', 'month', 'file_full',
      'date', 'sort',
    ]);
    $query->orderBy('year', 'DESC');
    $query->orderBy('month', 'DESC');
    $query->orderBy('date', 'ASC');

    $content['research'] = $query->execute()->fetchAllAssoc('id');

    $this->outputPageInfo($content);

    return $content;
  }

  /**
   * Gets the child pages.
   *
   * @param int $page_id
   *   The page identifier.
   *
   * @return array
   *   The child pages.
   */
  private function getChildPages($page_id) {
    $child_pages = [];
    foreach ($this->pages as $page) {
      if ($page->parent_id == $page_id) {
        $child_pages[] = (int) $page->id;
      }
    }

    return $child_pages;
  }

  /**
   * Gets the depth.
   *
   * @param int $page_id
   *   The page identifier.
   *
   * @return int
   *   The depth.
   */
  private function getDepth($page_id) {
    // Account for root page.
    $depth = -1;

    while ($page_id) {
      $page = $this->pages[$page_id];
      $depth++;

      $page_id = $page->parent_id;
    }

    return $depth;
  }

  /**
   * Dumps the page info.
   *
   * @param array $content
   *   The content.
   */
  private function outputPageInfo(array $content) {
    dump([
      'Page title' => $content['page']->title,
      'Depth' => $content['page']->depth,
      'URL' => $content['page']->url,
      'Language' => $content['page']->lang,
      'Template' => $content['page']->template ? $this->templates[$content['page']->template] : '- none -',
      'Active' => $content['page']->active,
      'Enabled' => $content['page']->enable,
      'Redirecting' => $content['page']->type == 'r' ? TRUE : FALSE,
      'Created' => date('d.m.Y H:i:s', $content['page']->created_date),
      'News count' => count($content['news']),
      'Event count' => count($content['events']),
      'Child page count' => count($this->getChildPages($content['page']->id)),
    ]);
  }

  /**
   * Creates a category page.
   *
   * @param array $content
   *   The content.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Newly ceated menu item.
   */
  private function createCategoryPage(array $content, MenuLinkContent $parent_menu_item = NULL) {
    $overview_content = NULL;
    $overview_node = NULL;
    $content_template = $content['page']->template ? $this->templates[$content['page']->template] : '';

    // Overview pages are being skipped for now.
    // if ($content_template == 'landing_page') {
    // Load overview content from an external landing page block.
    // $overview_content = $this->loadCategoryPageOverviewLanding($content['page']->id);
    // $overview_content->type = self::OVERVIEW_TYPE_LANDING_BLOCK;

    // Create overview page.
    // $overview_node = $this->createCategoryOverviewPageLanding($overview_content);
    // }
    // elseif ($content_template == 'page' && count($content['news'])) {
    // Load overview content from page's first news item.
    // $overview_content = reset($content['news']);
    // $overview_content->type = self::OVERVIEW_TYPE_NEWS;

    // Create overview page.
    // $overview_node = $this->createCategoryOverviewPageNews($overview_content);
    // }

    // Create category page node.
    $node = $this->createCategoryPageNode($content, $overview_content, $overview_node);

    // Migrate node alias.
    $this->migrateNodeAlias($node, $content);

    // Create a main menu entry.
    $menu_item = $this->createMenuItem($node, $content, $parent_menu_item);

    return $menu_item;
  }

  /**
   * Creates a category page node.
   *
   * @param array $content
   *   The content.
   * @param object|null $overview_content
   *   The overview content.
   * @param \Drupal\node\Entity\Node $overview_node
   *   The overview node.
   *
   * @return \Drupal\node\Entity\Node
   *   Category page node.
   */
  private function createCategoryPageNode(array $content, $overview_content, Node $overview_node = NULL) {
    $this->switchDatabase();

    $about = ($overview_content && $overview_content->type == self::OVERVIEW_TYPE_LANDING_BLOCK) ? $overview_content->lead_en : '';
    $node = Node::create([
      'type' => self::CONTENT_TYPE_CATEGORY_PAGE,
      'title' => $content['page']->title,
      'field_about' => $this->migrateTextData($about),
      'field_overview_page' => $overview_node ? $overview_node->id() : NULL,
      'field_inherit_widgets' => TRUE,
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $content['page']->enable,
      'created' => $content['page']->created_date,
    ]);
    $node->path->pathauto = 0;
    $node->save();

    return $node;
  }

  /**
   * Loads the category page overview content from a landing block.
   *
   * @param int $page_id
   *   The page identifier.
   *
   * @return array
   *   Category page overview content.
   */
  private function loadCategoryPageOverviewLanding($page_id) {
    $this->switchDatabase(self::DATABASE_EXTERNAL);
    $connection = Database::getConnection();

    // Get all fields.
    $query = $connection->select('mod_landing_ptblock', 'b');
    $query->condition('b.lpage_id', $page_id);
    $query->fields('b', [
      'id', 'lpage_id', 'title_lv', 'title_ru', 'title_en', 'lead_lv',
      'lead_ru', 'lead_en', 'lead_sub_lv', 'lead_sub_ru', 'lead_sub_en'
    ]);
    $content = $query->execute()->fetch();

    return $content;
  }

  /**
   * Creates the category overview page from landing block content.
   *
   * @param object $content
   *   The content.
   *
   * @return \Drupal\node\Entity\Node
   *   Category overview page.
   */
  private function createCategoryOverviewPageLanding($content) {
    $this->switchDatabase();
    $node = Node::create([
      'type' => self::CONTENT_TYPE_PAGE,
      'title' => $content->title_en,
      'field_lead_text' => '<p>' . $this->migrateTextData($content->lead_sub_en) . '</p>',
      'field_body' => '',
      'field_inherit_widgets' => TRUE,
      'uid' => self::CONTENT_OWNER_ID,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Creates the category overview page from a news object.
   *
   * @param object $news
   *   The news.
   *
   * @return \Drupal\node\Entity\Node
   *   Category overview page.
   */
  private function createCategoryOverviewPageNews($news) {
    $this->switchDatabase();

    $link_text = $this->parseNewsLinks($news->links);
    $news->text .= $link_text;

    $node = Node::create([
      'type' => self::CONTENT_TYPE_PAGE,
      'title' => $news->title,
      'field_lead_text' => '<p>' . $this->migrateTextData($news->lead) . '</p>',
      'field_body' => $this->migrateTextData($news->text),
      'field_inherit_widgets' => TRUE,
      'uid' => self::CONTENT_OWNER_ID,
    ]);
    $node->save();

    $this->migrateFiles($node, $news->files);
    $this->migrateImage($node, $news->text_image);

    return $node;
  }

  /**
   * Creates a menu item.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The content.
   * @param array $content
   *   The content.
   * @param Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Menu item.
   */
  private function createMenuItem(Node $node, array $content, MenuLinkContent $parent_menu_item = NULL) {
    // Enabled only if page is enabled and visible ("active").
    $enabled = $content['page']->enable && $content['page']->active;

    $menu_item = MenuLinkContent::create([
      'title' => $node->title->value,
      'link' => [
        'uri' => 'internal:/node/' . $node->id(),
      ],
      'menu_name' => 'main',
      'parent' => $parent_menu_item ? $parent_menu_item->getPluginId() : '',
      'weight' => $parent_menu_item ? $this->getMenuItemWeight($parent_menu_item) : $this->rootMenuItemWeight,
      'expanded' => TRUE,
      'enabled' => $enabled,
    ]);
    $menu_item->save();

    return $menu_item;
  }

  /**
   * Creates a content page.
   *
   * @param array $content
   *   The content.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Newly created menu item.
   */
  private function createContentPage(array $content, MenuLinkContent $parent_menu_item = NULL) {
    // Create content page node.
    $node = $this->createContentPageNode($content);

    // Migrate node alias.
    $this->migrateNodeAlias($node, $content);

    // Create a main menu entry.
    $menu_item = $this->createMenuItem($node, $content, $parent_menu_item);

    return $menu_item;
  }

  /**
   * Creates a content page node.
   *
   * @param array $content
   *   The content.
   *
   * @return \Drupal\node\Entity\Node
   *   Content page node.
   */
  private function createContentPageNode(array $content) {
    $this->switchDatabase();
    $lead_text = '';
    $body = '';
    $private_widget_title = '';
    $private_widget_text = '';

    // Use the first (usually only) news item associated with this page.
    $news = count($content['news']) ? reset($content['news']) : NULL;

    if ($news) {
      // If news object has any links, append them to the body.
      $link_text = $this->parseNewsLinks($news->links);
      $news->text .= $link_text;

      $lead_text = $this->migrateTextData($news->lead);
      $body = $this->migrateTextData($news->text);

      $private_widget_title = $this->migrateTextData($news->addTitle);
      $private_widget_text = $this->migrateTextData($news->addContent);
    }

    // Content for 360 tour page, to skip special conditions.
    if ($content['page']->title == '360° Tours') {
      $body = '<iframe border="0" height="500" src="http://skatskat.lv/virtuala-ture/stockholm_school_of_economics_in_riga/index/tour.html" width="90%"></iframe><div class="text date">Thursday, 28/01/2016</div><h2><a href="http://skatskat.lv/virtuala-ture/stockholm_school_of_economics_in_riga/index/tour.html" target="_blank" title="SSE Riga 360° Virtual Tour">SSE Riga 360° Virtual Tour </a></h2><div class="text"><p>Take a look inside SSE Riga Premises, Auditoriums and Dormitories</p><p>Separate tours are available here:</p><ul><li><a href="http://skatskat.lv/virtuala-ture/stockholm_school_of_economics_in_riga/web/sse-riga/tour.html" target="_blank">SSE Riga Premises </a></li><li><a href="http://skatskat.lv/virtuala-ture/stockholm_school_of_economics_in_riga/web/auditoriums/tour.html" target="_blank">SSE Riga Auditoriums </a></li><li><a href="http://skatskat.lv/virtuala-ture/stockholm_school_of_economics_in_riga/web/dormitories/tour.html" target="_blank">SSE Riga Dormitories</a></li></ul></div>';
    }

    // Create content page node.
    $node = Node::create([
      'type' => self::CONTENT_TYPE_PAGE,
      'title' => $content['page']->title,
      'field_lead_text' => $lead_text,
      'field_body' => $body,
      'field_private_widget_title' => $private_widget_title,
      'field_private_widget_text' => $private_widget_text,
      'field_private_widget_background' => 'default',
      'field_inherit_widgets' => TRUE,
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $content['page']->enable,
      'created' => $content['page']->created_date,
    ]);
    $node->path->pathauto = 0;

    // Check what value content menu should have.
    if ($content['page']->depth > 1 && count($this->getChildPages($content['page']->id)) > 0) {
      $node->set('field_content_menu', 'child');
    }
    elseif ($content['page']->depth > 2) {
      $node->set('field_content_menu', 'sibling');
    }

    $node->save();

    // Migrate file blocks/image to paragraphs.
    if ($news) {
      $this->migrateFiles($node, $news->files);
      $this->migrateImage($node, $news->text_image);
    }

    return $node;
  }

  /**
   * Updates default path alias to match imported path.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   * @param array $content
   *   The content.
   *
   * @return string
   *   Resulting alias.
   */
  private function migrateNodeAlias(Node $node, array $content) {
    $alias_source = '/node/' . $node->id();
    $alias_language = $node->language()->getId();

    // Create a path alias from old url.
    $alias = '/' . rtrim(preg_replace('#^en\/#', '', $content['page']->url), '/');
    $suffix = '';
    $suffix_counter = 0;

    // Check alias availability.
    while ($this->aliasStorage->aliasExists($alias . $suffix, $alias_language)) {
      $suffix = '-' . $suffix_counter;
      $suffix_counter++;
    }

    // Save resulting alias.
    $this->aliasStorage->save($alias_source, $alias . $suffix, $alias_language);

    return $alias . $suffix;
  }

  /**
   * Modifies import text data.
   *
   * Strips slashes before ' and ".
   * Replaces image/file paths to match new file structure.
   * Removes domain and extension from local path.
   * Removes inline attributes from tables.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The modified text.
   */
  private function migrateTextData($text) {
    $text = stripslashes($text);

    $text = str_replace('/files/content', '/sites/default/files/content', $text);

    // Remove ".html" from paths in domain "sseriga.edu" ending with ".html".
    $matches = [];
    preg_match_all('/http:\/\/(?:www\.(.*))?sseriga.edu\/(?:en\/(.*))?.html/', $text, $matches);
    if (isset($matches[0]) && count($matches[0])) {
      foreach ($matches[0] as $full_match) {
        $text = str_replace($full_match, rtrim($full_match, '.html'), $text);
      }
    }

    $text = str_replace('http://www.sseriga.edu/en/', '/', $text);
    $text = str_replace('http://sseriga.edu/en/', '/', $text);
    $text = str_replace('http://www.sseriga.edu/', '/', $text);
    $text = str_replace('http://sseriga.edu/', '/', $text);

    $text = preg_replace('/<(table)[^>]*?(\/?)>/i', '<$1$2>', $text);

    return $text;
  }

  /**
   * Parses serialized link data, and returns html.
   *
   * @param string $links
   *   Serialized link data.
   *
   * @return string
   *   Parsed html.
   */
  private function parseNewsLinks($links) {
    $link_text = '';
    $links = $links ? unserialize($links) : [];
    if (count($links)) {
      $link_text .= '<p>';
      foreach ($links as $index => $link) {
        $link_text .= '<a target="' . $link['linkTarget'] . '" href="' . $link['linkUrl'] . '">' . $link['linkTitle'] . '</a>';
        if (count($links) - $index > 1) {
          $link_text .= '<br/>';
        }
      }
      $link_text .= '</p>';
    }

    return $link_text;
  }

  /**
   * Creates file paragraphs and links them to the content node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   * @param string $files
   *   The files.
   * @param string $folder
   *   The folder.
   */
  private function migrateFiles(Node $node, $files, $folder = 'news') {
    $files = $files ? unserialize($files) : [];
    foreach ($files as $file) {
      $file_entity = File::create([
        'uri' => 'public://' . $folder . '/' . $file['fileName'],
      ]);
      $file_entity->setPermanent();
      $file_entity->save();

      $file_attachement = Paragraph::create([
        'type' => 'file_attachment',
        'field_file' => [
          'target_id' => $file_entity->id(),
          'description' => $file['fileTitle'],
        ],
      ]);
      $file_attachement->save();

      $node->get('field_file_attachments')->appendItem([
        'target_id' => $file_attachement->id(),
        'target_revision_id' => $file_attachement->getRevisionId()
      ]);
      $node->save();
    }
  }

  /**
   * Creates gallery image paragraph and links it to the content node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   * @param string $image
   *   The image.
   * @param string $folder
   *   The folder.
   */
  private function migrateImage(Node $node, $image, $folder = 'news') {
    if (!$image) {
      return;
    }

    $file_entity = File::create([
      'uri' => 'public://' . $folder . '/' . $image,
    ]);
    $file_entity->setPermanent();
    $file_entity->save();

    $gallery_image = Paragraph::create([
      'type' => 'gallery_image',
      'field_image' => [
        'target_id' => $file_entity->id(),
      ],
    ]);
    $gallery_image->save();

    $node->get('field_gallery_images')->appendItem([
      'target_id' => $gallery_image->id(),
      'target_revision_id' => $gallery_image->getRevisionId()
    ]);
    $node->save();
  }

  /**
   * Migrates staff entities.
   */
  public function migrateStaff() {
    // Load departments.
    $departments = $this->loadDepartments();

    // Vocabulary "Departments" - delete existing content & migrate old content.
    // Returns array with department keys - old_id => new_id.
    $departments = $this->migrateDepartments($departments);

    // Load staff.
    $staff = $this->loadStaff();

    // Migrates staff members.
    $this->migrateStaffMembers($staff, $departments);
  }

  /**
   * Loads departments.
   *
   * @return array
   *   The departments.
   */
  private function loadDepartments() {
    $this->switchDatabase(self::DATABASE_EXTERNAL);
    $connection = Database::getConnection();

    // Get all fields.
    $query = $connection->select('mod_staff_department', 'd');
    $query->fields('d', ['id', 'name']);
    $departments = $query->execute()->fetchAllAssoc('id');

    $content = [];
    foreach ($departments as $department) {
      $content[$department->id] = $department->name;
    }

    return $content;
  }

  /**
   * Migrates departments.
   *
   * @param array $departments
   *   The departments.
   *
   * @return array
   *   Department migration associations.
   */
  private function migrateDepartments(array $departments) {
    $this->switchDatabase();

    // Empty department vocabulary.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $existing_terms = $term_storage
      ->getQuery()
      ->condition('vid', self::VOCABULARY_DEPARTMENTS)
      ->execute();

    $existing_terms = $term_storage->loadMultiple($existing_terms);
    $term_storage->delete($existing_terms);

    // Migrate departments.
    foreach ($departments as $old_id => $department) {
      $term = Term::create([
        'vid' => self::VOCABULARY_DEPARTMENTS,
        'name' => $department,
      ]);
      $term->save();

      $departments[$old_id] = $term->id();
    }

    return $departments;
  }

  /**
   * Loads staff members.
   *
   * @return array
   *   Staff members.
   */
  private function loadStaff() {
    $this->switchDatabase(self::DATABASE_EXTERNAL);
    $connection = Database::getConnection();

    // Get all fields.
    $query = $connection->select('mod_staff', 's');
    $query->fields('s', [
      'id', 'name', 'uri', 'email', 'administration', 'faculty',
      'department_id', 'position', 'lead_image', 'tought', 'description',
      'www', 'owner', 'updated', 'created', 'page_description', 'page_title',
      'phone'
    ]);
    $staff = $query->execute()->fetchAllAssoc('id');

    return $staff;
  }

  /**
   * Migrates staff members.
   *
   * @param array $staff
   *   The staff.
   * @param array $departments
   *   The departments.
   */
  private function migrateStaffMembers(array $staff, array $departments) {
    $this->switchDatabase();

    foreach ($staff as $member) {
      $image = NULL;
      if ($member->lead_image) {
        $image = File::create([
          'uri' => 'public://staff/' . $member->lead_image,
        ]);
        $image->setPermanent();
        $image->save();
      }

      $node = Node::create([
        'type' => self::CONTENT_TYPE_STAFF,
        'title' => $member->name,
        'field_email' => $member->email,
        'field_administration' => $member->administration,
        'field_faculty' => $member->faculty,
        'field_department' => $member->department_id ? $departments[$member->department_id] : NULL,
        'field_position' => strip_tags($member->position),
        'field_image' => $image ? $image->id() : NULL,
        'field_body' => $this->migrateTextData($member->description),
        'field_website' => $member->www,
        'field_phone' => $member->phone,
        'field_inherit_widgets' => TRUE,
        'uid' => self::CONTENT_OWNER_ID,
      ]);
      $node->save();
    }
  }

  /**
   * Creates a news list page and migrates all its news items.
   *
   * @param array $content
   *   The content.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Newly ceated menu item.
   */
  private function createNewsListPage(array $content, MenuLinkContent $parent_menu_item = NULL) {
    // Create news list node.
    $node = $this->createNewsListPageNode($content);

    // Migrate node alias.
    $news_list_alias = $this->migrateNodeAlias($node, $content);

    // Migrate news items.
    foreach ($content['news'] as $news) {
      $news_node = $this->createNewsPageNode($news, $node);
      $news_alias = $news_list_alias . '/' . $news->page_url;
      $this->migrateSubcontentAlias($news_node, $news_alias);
      dump($news->title . ' - migrated');
    }

    // Create a main menu entry.
    $menu_item = $this->createMenuItem($node, $content, $parent_menu_item);

    return $menu_item;
  }

  /**
   * Creates a news list node.
   *
   * @param array $content
   *   The content.
   *
   * @return \Drupal\node\Entity\Node
   *   News list page node.
   */
  private function createNewsListPageNode(array $content) {
    $this->switchDatabase();

    $node = Node::create([
      'type' => self::CONTENT_TYPE_NEWS_LIST,
      'title' => $content['page']->title,
      'field_inherit_widgets' => TRUE,
      'field_content_menu' => 'sibling',
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $content['page']->enable,
      'created' => $content['page']->created_date,
    ]);
    $node->path->pathauto = 0;
    $node->save();

    return $node;
  }

  /**
   * Creates a news page node.
   *
   * @param object $news
   *   The news.
   * @param \Drupal\node\Entity\Node $news_list
   *   The news list.
   *
   * @return \Drupal\node\Entity\Node
   *   News page node.
   */
  private function createNewsPageNode($news, Node $news_list) {
    $this->switchDatabase();

    // Append links to end of body.
    $link_text = $this->parseNewsLinks($news->links);
    $news->text .= $link_text;

    // Create node, replacing paths.
    $node = Node::create([
      'type' => self::CONTENT_TYPE_NEWS,
      'title' => $news->title,
      'field_news_list' => $news_list->id(),
      'field_lead_text' => $this->migrateTextData($news->lead),
      'field_body' => $this->migrateTextData($news->text),
      'field_private_widget_title' => $this->migrateTextData($news->addTitle),
      'field_private_widget_text' => $this->migrateTextData($news->addContent),
      'field_private_widget_background' => 'default',
      'field_inherit_widgets' => TRUE,
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $news->enable,
      'created' => $news->date,
    ]);
    $node->path->pathauto = 0;
    $node->save();

    // Migrate files.
    $this->migrateFiles($node, $news->files);

    // Migrate image.
    $this->migrateImage($node, $news->text_image);

    return $node;
  }

  /**
   * Migrates path alias for news/event items.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   * @param string $alias
   *   The alias.
   *
   * @return string
   *   Resulting alias.
   */
  private function migrateSubcontentAlias(Node $node, $alias) {
    $alias_source = '/node/' . $node->id();
    $alias_language = $node->language()->getId();
    $suffix = '';
    $suffix_counter = 0;

    // Check alias availability.
    while ($this->aliasStorage->aliasExists($alias . $suffix, $alias_language)) {
      $suffix = '-' . $suffix_counter;
      $suffix_counter++;
    }

    // Save resulting alias.
    $this->aliasStorage->save($alias_source, $alias . $suffix, $alias_language);

    return $alias . $suffix;
  }

  /**
   * Creates an event list page and migrates all its event items.
   *
   * @param array $content
   *   The content.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Newly ceated menu item.
   */
  private function createEventListPage(array $content, MenuLinkContent $parent_menu_item = NULL) {
    // Create event list node.
    $node = $this->createEventListPageNode($content);

    // Migrate node alias.
    $event_list_alias = $this->migrateNodeAlias($node, $content);

    // Migrate event items.
    foreach ($content['events'] as $event) {
      $event_node = $this->createEventPageNode($event, $node);
      $event_alias = $event_list_alias . '/' . $event->page_url;
      $this->migrateSubcontentAlias($event_node, $event_alias);
      dump($event->title . ' - migrated');
    }

    // Create a main menu entry.
    $menu_item = $this->createMenuItem($node, $content, $parent_menu_item);

    return $menu_item;
  }

  /**
   * Creates a news list node.
   *
   * @param array $content
   *   The content.
   *
   * @return \Drupal\node\Entity\Node
   *   News list page node.
   */
  private function createEventListPageNode(array $content) {
    $this->switchDatabase();

    $node = Node::create([
      'type' => self::CONTENT_TYPE_EVENT_LIST,
      'title' => $content['page']->title,
      'field_inherit_widgets' => TRUE,
      'field_content_menu' => 'sibling',
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $content['page']->enable,
      'created' => $content['page']->created_date,
    ]);
    $node->path->pathauto = 0;
    $node->save();

    return $node;
  }

  /**
   * Creates an event page node.
   *
   * @param object $event
   *   The event.
   * @param \Drupal\node\Entity\Node $event_list
   *   The event list.
   *
   * @return \Drupal\node\Entity\Node
   *   Event page node.
   */
  private function createEventPageNode($event, Node $event_list) {
    $this->switchDatabase();

    // Append links to end of body.
    $link_text = $this->parseNewsLinks($event->links);
    $event->text .= $link_text;

    // Create node, replacing paths.
    $node = Node::create([
      'type' => self::CONTENT_TYPE_EVENT,
      'title' => $event->title,
      'field_event_list' => $event_list->id(),
      'field_lead_text' => $this->migrateTextData($event->lead),
      'field_body' => $this->migrateTextData($event->text),
      'field_summary' => $this->migrateTextData($event->infobox),
      'field_location' => $event->location,
      'field_date_from' => $event->date_from ? date('Y-m-d', $event->date_from) : '',
      'field_date_to' => $event->date_to ? date('Y-m-d', $event->date_to) : '',
      'field_time_from' => $event->time_from,
      'field_time_to' => $event->time_to,
      'field_private_widget_title' => $this->migrateTextData($event->addTitle),
      'field_private_widget_text' => $this->migrateTextData($event->addContent),
      'field_private_widget_background' => 'default',
      'field_inherit_widgets' => TRUE,
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $event->enable,
      'created' => $event->created,
    ]);
    $node->path->pathauto = 0;
    $node->save();

    // Migrate files.
    $this->migrateFiles($node, $event->files, 'events');

    // Migrate image.
    $this->migrateImage($node, $event->text_image, 'events');

    return $node;
  }

  /**
   * Attempts to assign a content type automatically.
   *
   * @param array $content
   *   The content.
   *
   * @return string|void
   *   The content type.
   */
  private function getAutoContentType(array $content) {
    // If first level page, assign category page type.
    if ($content['page']->depth == 1) {
      return self::CONTENT_TYPE_CATEGORY_PAGE;
    }

    // If printedmatter template, it's probably printed matter.
    if (($this->templates[$content['page']->template] ?? '') == 'printedmatter') {
      return self::CONTENT_TYPE_PRINTED_MATTER;
    }

    // If researchPapers template, it's probably research papers.
    if (($this->templates[$content['page']->template] ?? '') == 'researchPapers') {
      return self::CONTENT_TYPE_RESEARCH;
    }

    // If page has more than 5 news entities assigned, assign news list type.
    if (count($content['news']) > 5) {
      return self::CONTENT_TYPE_NEWS_LIST;
    }

    // If page has more than 5 event entities assigned, assign event list type.
    if (count($content['events']) > 5) {
      return self::CONTENT_TYPE_EVENT_LIST;
    }

    return self::CONTENT_TYPE_PAGE;
  }

  /**
   * Gets the menu item weight.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link
   *   The menu link.
   *
   * @return int
   *   The menu item weight.
   */
  private function getMenuItemWeight(MenuLinkContent $menu_link) {
    $menu_storage = $this->entityTypeManager->getStorage('menu_link_content');
    $menu_item_ids = $menu_storage->getQuery()
      ->condition('parent', $menu_link->getPluginId())
      ->execute();

    return count($menu_item_ids);
  }

  /**
   * Sets the root menu item weight.
   *
   * @param int $weight
   *   The weight.
   */
  public function setRootMenuItemWeight($weight) {
    $this->rootMenuItemWeight = $weight;
  }

  /**
   * Creates a printed matter page and migrates all its printed matter.
   *
   * @param array $content
   *   The content.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Newly ceated menu item.
   */
  private function createPrintedMatterPage(array $content, MenuLinkContent $parent_menu_item = NULL) {
    // Create content containing node.
    $node = $this->createPrintedMatterPageNode($content);

    // Migrate node alias.
    $this->migrateNodeAlias($node, $content);

    // Migrate printed matter records.
    foreach ($content['printed_matter'] as $printed_matter) {
      $this->createPrintedMatterParagraph($printed_matter, $node);
    }

    // Create a main menu entry.
    $menu_item = $this->createMenuItem($node, $content, $parent_menu_item);

    return $menu_item;
  }

  /**
   * Creates a printed matter node.
   *
   * @param array $content
   *   The content.
   *
   * @return \Drupal\node\Entity\Node
   *   Printed matter page node.
   */
  private function createPrintedMatterPageNode(array $content) {
    $this->switchDatabase();

    $node = Node::create([
      'type' => self::CONTENT_TYPE_PRINTED_MATTER,
      'title' => $content['page']->title,
      'field_inherit_widgets' => TRUE,
      'field_content_menu' => 'sibling',
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $content['page']->enable,
      'created' => $content['page']->created_date,
      'field_lead_text' => '<p>Download our brochures as PDF files.<br>If you prefer hard copies, please email to <a href="mailto:office@sseriga.edu">office@sseriga.edu</a></p>',
    ]);
    $node->path->pathauto = 0;
    $node->save();

    return $node;
  }

  /**
   * Creates a printed matter paragraph.
   *
   * @param object $content
   *   The content.
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   */
  private function createPrintedMatterParagraph($content, Node $node) {
    $file_data = unserialize($content->file);

    // Create file entity.
    $file_entity = File::create([
      'uri' => 'public://printedmatter/' . $file_data['name'] . '.' . $file_data['ext'],
    ]);
    $file_entity->setPermanent();
    $file_entity->save();

    // Create image entity.
    $image_entity = File::create([
      'uri' => 'public://printedmatter/' . $content->lead_image,
    ]);
    $image_entity->setPermanent();
    $image_entity->save();

    // If description has no <p> tags, wrap it.
    if (strpos($content->lead_en, '<p>') === FALSE) {
      $content->lead_en = '<p>' . $content->lead_en . '</p>';
    }

    // Create paragraph entity.
    $paragraph = Paragraph::create([
      'type' => 'printed_matter',
      'field_title' => $content->title_en,
      'field_text' => $this->migrateTextData($content->lead_en),
      'field_image' => [
        'target_id' => $image_entity->id(),
      ],
      'field_file' => [
        'target_id' => $file_entity->id(),
      ],
    ]);
    $paragraph->save();

    // Append paragraphs.
    $node->get('field_printed_matter')->appendItem([
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId()
    ]);
    $node->save();

    dump($content->title_en . ' - migrated');
  }

  /**
   * Creates a research and migrates all its research papers.
   *
   * @param array $content
   *   The content.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $parent_menu_item
   *   The parent menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Newly ceated menu item.
   */
  private function createResearchPage(array $content, MenuLinkContent $parent_menu_item = NULL) {
    // Create content containing node.
    $node = $this->createResearchPageNode($content);

    // Migrate node alias.
    $this->migrateNodeAlias($node, $content);

    // Reverse research pages for easier administration.
    $content['research'] = array_reverse($content['research']);

    // Migrate research papers.
    foreach ($content['research'] as $research) {
      $this->createResearchParagraph($research, $node);
    }

    // Create a main menu entry.
    $menu_item = $this->createMenuItem($node, $content, $parent_menu_item);

    return $menu_item;
  }

  /**
   * Creates a research node.
   *
   * @param array $content
   *   The content.
   *
   * @return \Drupal\node\Entity\Node
   *   Research page node.
   */
  private function createResearchPageNode(array $content) {
    $this->switchDatabase();

    $node = Node::create([
      'type' => self::CONTENT_TYPE_RESEARCH,
      'title' => $content['page']->title,
      'field_inherit_widgets' => TRUE,
      'field_content_menu' => 'sibling',
      'uid' => self::CONTENT_OWNER_ID,
      'status' => $content['page']->enable,
      'created' => $content['page']->created_date,
    ]);
    $node->path->pathauto = 0;
    $node->save();

    return $node;
  }

  /**
   * Creates a research paragraph.
   *
   * @param object $content
   *   The content.
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   */
  private function createResearchParagraph($content, Node $node) {
    $file_data = unserialize($content->file_full);

    // Create file entity.
    $file_entity = File::create([
      'uri' => 'public://researchPapers/' . $file_data['name'] . '.' . $file_data['ext'],
    ]);
    $file_entity->setPermanent();
    $file_entity->save();

    // If description has no <p> tags, wrap it.
    if (strpos($content->lead_en, '<p>') === FALSE) {
      $content->lead_en = '<p>' . $content->lead_en . '</p>';
    }

    if (strlen($content->month) === 1) {
      $content->month = '0' . $content->month;
    }

    // Create paragraph entity.
    $paragraph = Paragraph::create([
      'type' => 'research_paper',
      'field_title' => $content->title_en,
      'field_text' => $this->migrateTextData($content->lead_en),
      'field_author' => $content->author,
      'field_date' => $content->year . '-' . $content->month . '-01',
      'field_file' => [
        'target_id' => $file_entity->id(),
      ],
    ]);
    $paragraph->save();

    // Append paragraphs.
    $node->get('field_research_papers')->appendItem([
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId()
    ]);
    $node->save();

    dump($content->title_en . ' - migrated');
  }

  /**
   * Migrates existing blog items to nodes with content type "Blog".
   *
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The i/o.
   */
  public function migrateBlogListItems(DrupalStyle $io) {
    $node_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $node_ids = $node_query
      ->condition('type', self::CONTENT_TYPE_NEWS_LIST)
      ->condition('title', 'Blogs')
      ->execute();

    $blog_page_id = array_shift($node_ids);
    if (!$blog_page_id) {
      $io->error('Existing blog list page not found!');
      return;
    }

    $node_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $node_ids = $node_query
      ->condition('type', self::CONTENT_TYPE_BLOG_LIST)
      ->execute();

    $blog_list_page_id = array_shift($node_ids);
    if (!$blog_list_page_id) {
      $io->error('New blog list page not found!');
      return;
    }

    $node_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $node_ids = $node_query
      ->condition('type', self::CONTENT_TYPE_NEWS)
      ->condition('field_news_list', $blog_page_id)
      ->execute();

    $blog_news = Node::loadMultiple($node_ids);
    foreach ($blog_news as $blog_news_item) {
      $node = Node::create([
        'type' => self::CONTENT_TYPE_BLOG,
        'title' => $blog_news_item->get('title')->value,
        'field_blog_list' => $blog_list_page_id,
        'field_lead_text' => $blog_news_item->get('field_lead_text')->value,
        'field_body' => $blog_news_item->get('field_body')->value,
        'field_private_widget_background' => $blog_news_item->get('field_private_widget_background')->value,
        'field_private_widget_title' => $blog_news_item->get('field_private_widget_title')->value,
        'field_private_widget_text' => $blog_news_item->get('field_private_widget_text')->value,
        'field_inherit_widgets' => TRUE,
        'uid' => self::CONTENT_OWNER_ID,
        'status' => $blog_news_item->get('status')->value,
        'created' => $blog_news_item->get('created')->value,
        'field_show_share_button' => 1,
      ]);
      $node->path->pathauto = 0;
      $node->save();

      foreach ($blog_news_item->get('field_gallery_images')->referencedEntities() as $image) {
        $image->set('parent_id', $node->id());
        $image->set('parent_type', 'node');
        $image->set('parent_field_name', 'field_gallery_images');
        $image->save();

        $node->get('field_gallery_images')->appendItem([
          'target_id' => $image->id(),
          'target_revision_id' => $image->getRevisionId()
        ]);
        $node->save();
      }

      $alias_source = '/node/' . $blog_news_item->id();
      $alias_language = $blog_news_item->language()->getId();
      $alias = $this->aliasStorage->lookupPathAlias($alias_source, $alias_language);

      $blog_news_item->delete();
      $this->aliasStorage->save('/node/' . $node->id(), $alias, $alias_language);
    }
  }

}
