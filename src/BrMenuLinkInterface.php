<?php

namespace Drupal\br_menu;

/**
 * Interface for BrMenuLink class.
 *
 * @package Drupal\br_menu
 */
interface BrMenuLinkInterface {

  /**
   * Gets the title of an entity's menu link.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get the menu title from. Must be provided in the right
   *   language.
   *
   * @return string
   *   Menu link title. Falls back to the entity title if no menu link is
   *   available.
   */
  public static function getTitle(\Drupal\Core\Entity\ContentEntityInterface $entity);
}
