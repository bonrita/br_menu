<?php

namespace Drupal\br_menu;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides services for the summary information in the main navigation menu.
 *
 * @package Drupal\br_menu
 */
class SummaryBlockService implements SummaryBlockServiceInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheCollector;

  /**
   * @var string
   */
  protected $currentLanguageCode;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager, LanguageManagerInterface $languageManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentLanguageCode = $languageManager->getCurrentLanguage()
      ->getId();
  }

  /**
   * Get summary block content for the menu items.
   *
   * @param array $items
   *   The menu items.
   */
  function getSummaryBlockContent(&$items, $items_below = FALSE) {

    foreach ($items as &$data) {
      if (!empty($data['below'])) {
        foreach ($data['below'] as $menu_link_content => $menu_item) {
          // Add summary block at the second level of the menu items.
          $data['below'][$menu_link_content]['summary_block'] = $this->getSummaryBlock($menu_item);

          if (!empty($data['below'][$menu_link_content]['below'])) {
            $this->getSummaryBlockContent($data['below'][$menu_link_content]['below'], TRUE);
          }
        }
      }
      elseif ($items_below) {
        foreach ($items as $menu_link_content => $menu_item) {
          // Add summary block to the 3rd level of menu items.
          $items[$menu_link_content]['summary_block'] = $this->getSummaryBlock($menu_item);
        }
      }
    }
  }

  /**
   * Gets the summary content of the menu item.
   *
   * @param string $menu_item
   *
   * @return array
   *  Array of collected menu summary data.
   */
  protected function getSummaryBlock($menu_item) {
    $data = [];

    /** @var \Drupal\Core\Url $url */
    $url = $menu_item['url'];
    $route_parameters = $url->getRouteParameters();

    if ($url->getRouteName() == 'entity.node.canonical') {
      $this->cacheCollector = new CacheableMetadata();
      /* @var \Drupal\node\NodeInterface $node */
      $node = $this->entityTypeManager
        ->getStorage('node')
        ->load($route_parameters['node']);
      if ($node) {
        if ($node->hasTranslation($this->currentLanguageCode)) {
          $node = $node->getTranslation($this->currentLanguageCode);
        }
        $this->cacheCollector->addCacheableDependency($node);
        $this->getContentTypeSpecificElements($data, $node);
        $this->getMainContent($data, $node);
      }
    }

    return $data;
  }

  /**
   * Returns the URL of a styled image.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field
   *   The image field.
   *
   * @param string $style
   *   The image style
   *
   * @return string
   *   The URL of the styled image. Or the URL of the original image if the
   *   style is unknown. This will generate the requested styled image.
   */
  protected function getStyledImageUrl($field, $style) {
    $url = '';

    /** @var \Drupal\file\Entity\File $file */
    $referenced_entities = $field->referencedEntities();

    if (!empty($referenced_entities)) {
      $file = reset($referenced_entities);
      $original_uri = $file->getFileUri();

      /** @var \Drupal\image\Entity\ImageStyle $img_style */
      $img_style = ImageStyle::load($style);
      if ($img_style) {
        $style_config = $this->entityTypeManager
          ->getStorage('image_style')
          ->load($style);
        $this->cacheCollector->addCacheableDependency($style_config);
        $url = $img_style->buildUrl($original_uri);
      }
      else {
        $url = file_create_url($original_uri);
      }
    }

    return $url;
  }

  /**
   * Get summary block content.
   *
   * @param array $data
   *   A list of data to be rendered.
   * @param \Drupal\node\NodeInterface $node
   *  The node housing the summary block content.
   */
  // @todo Can this be replaced by a view mode?
  protected function getMainContent(&$data, $node) {

    // Get the field collection data.
    if ($node->hasField($this::SUMMARY_FIELD_COLLECTION)) {
      /** @var \Drupal\field_collection\Plugin\Field\FieldType\FieldCollection $field_collection */
      $field_collection = $node->{$this::SUMMARY_FIELD_COLLECTION}->first();
      if ($field_collection) {
        /** @var \Drupal\field_collection\Entity\FieldCollectionItem $field_collection_item */
        $field_collection_item = $field_collection->getFieldCollectionItem();

        if ($field_collection_item) {
          $title = $field_collection_item->{$this::SUMMARY_TITLE_FIELD}->value;
          $text = $field_collection_item->{$this::SUMMARY_TEXT_FIELD}->value;

          // Limit the length of the content.
          // @todo Replace by max field input length. See ticket #99.
          $text = (strlen($text) > 100) ? substr($text, 0, 100) . ' ...' : $text;

          $data['title'] = ['#markup' => $title];
          $data['text'] = $text;

          $this->cacheCollector->addCacheableDependency($field_collection_item);
          $this->cacheCollector->applyTo($data['title']);
        }
      }
    }
  }

  /**
   * Get content type specific extra data elements.
   *
   * @param array $data
   *   The list of data to be rendered.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node housing the summary block content.
   */
  protected function getContentTypeSpecificElements(&$data, $node) {

    // If there is data, initialize the URL and CLASS keys.
    if(!empty($data)) {
      $data['url'] = '';
      $data['class'] = '';
    }

    switch ($node->getType()){

      // Get image URL.
      case 'platform':            // 0.4 Category Platform generic
        /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $image_item */
        $image_item = $node->{$this::SUMMARY_IMAGE_FIELD};

        if ($image_item) {
          $file_entity = $node->{$this::SUMMARY_IMAGE_FIELD}->entity;
          $this->cacheCollector->addCacheableDependency($file_entity);
          $data['url'] = $this->getStyledImageUrl($node->{$this::SUMMARY_IMAGE_FIELD}, 'menu_summary_block');
        }
        break;

      // Get CSS class for category icon.
      case 'platforms_overview':  // 0.2 Category Platforms overview (field__term_category:Solution Category)
      case 'category':            // 0.5 Category solutions generic (field__term_category:Solution Category)
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $term_reference */
        $term_reference = $node->{$this::TERM_CATEGORY_FIELD};

        if ($term_reference) {
          /** @var \Drupal\taxonomy\Entity\Term[] $terms */
          $terms = $term_reference->referencedEntities();
          $term = reset($terms);
          $class = $term->{$this::TERM_CATEGORY_CLASS_FIELD}->value;
          $data['class'] = "category-$class";

          // Collect the term cache tags and contexts.
          $this->cacheCollector->addCacheableDependency($term);
        }
        break;

      // Get CSS class for solution icon.
      case 'platform_specifics':  // 0.6 Category Platform generic (field__term_solution:Solution)
      case 'solution_specific':   // 0.7 Detail specific solution (field__term_solution:Solution)
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $term_reference */
        $term_reference = $node->{$this::TERM_SOLUTION_FIELD};

        if ($term_reference) {
          /** @var \Drupal\taxonomy\Entity\Term[] $terms */
          $terms = $term_reference->referencedEntities();

          if (!empty($terms)) {
            $term = reset($terms);
            $class = $term->{$this::TERM_SOLUTION_CLASS_FIELD}->value;
            $data['class'] = "solution-$class";

            // Collect the term cache tags and contexts.
            $this->cacheCollector->addCacheableDependency($term);
          }
        }
        break;
    }
  }

}
