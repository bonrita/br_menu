<?php

namespace Drupal\br_menu;

/**
 * Provides helpers for menu links.
 *
 * @package Drupal\br_menu
 */
class BrMenuLink implements BrMenuLinkInterface {

  /**
   * {@inheritdoc}
   */
  public static function getTitle(\Drupal\Core\Entity\ContentEntityInterface $entity) {
    $title = '';

    if ($entity->hasField('br_menu_link')) {
      $linkField = $entity->get('br_menu_link');

      /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menuLink */
      $menuLink = $linkField->entity;

      if ($menuLink) {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if ($menuLink->hasTranslation($langcode)) {
          $menuLink = $menuLink->getTranslation($langcode);
        }
        $title = $menuLink->label();
      }
    }

    if (empty($title)) {
      $title = $entity->label();
    }

    return $title;
  }
}
