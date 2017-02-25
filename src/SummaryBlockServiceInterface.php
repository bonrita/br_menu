<?php

namespace Drupal\br_menu;

/**
 * Interface SummaryBlockServiceInterfcae
 * @package Drupal\br_menu
 */
interface SummaryBlockServiceInterface {

  /**
   * The name of the field that contains the menu summary field collection.
   */
  const SUMMARY_FIELD_COLLECTION = 'field_menu_summary_block';

  /**
   * The name of the field that contains the menu summary title.
   */
  const SUMMARY_TITLE_FIELD = 'field__title';

  /**
   * The name of the field that contains the menu summary text.
   */
  const SUMMARY_TEXT_FIELD = 'field_menu_summary_block_text';

  /**
   * The name of the field that contains the menu summary image.
   */
  const SUMMARY_IMAGE_FIELD = 'field_menu_summary_block_image';

  /**
   * The name of the field that contains the taxonomy term for the category icon.
   */
  const TERM_CATEGORY_FIELD = 'field__term_category';

  /**
   * The name of the field that contains the category CSS class name.
   */
  const TERM_CATEGORY_CLASS_FIELD = 'field_solution_category_class';

  /**
   * The name of the field that contains the taxonomy term for the solution icon.
   */
  const TERM_SOLUTION_FIELD = 'field__term_solution';

  /**
   * The name of the field that contains the category CSS class name.
   */
  const TERM_SOLUTION_CLASS_FIELD = 'field_solution_class';

  /**
   * Get summary block content for the menu items.
   *
   * @param array $items
   *   The menu items.
   * @param bool $items_below
   *   Flag to process child items.
   */
  public function getSummaryBlockContent(&$items, $items_below = FALSE);

}
