<?php

namespace Drupal\br_menu;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\domain_menu\DomainMenuParentFormSelector;

class BrMenuParentFormSelector extends DomainMenuParentFormSelector {

  public function getParentSelectOptions($id = '', array $menus = NULL, CacheableMetadata &$cacheability = NULL) {
    $accessible_menus = [];
    if (!isset($menus)) {
      $menus = $this->getMenuOptions();
    }

    // If only one menu is given, we assume we are on a node add/edit page.
    // Using the given menu, (typically the a menu of the global domain) we
    // provide all domain sibling menus. For example Main navigation Global
    // ('main-aa'), the domain siblings are 'main-aa', 'main-nl', 'main-us',
    // etc.
    // We rely on the menu access check to filter only the allowed menus.
    if (count($menus) == 1) {
      $menu_id = key($menus);
      if (preg_match('/-[a-z]{2}$/', $menu_id)) {
        $menus = $this->domainSiblings($menu_id);
      }
    }

    $entities = $this->entityManager->getStorage('menu')->loadMultiple(array_keys($menus));
    foreach ($menus as $menu_id => $menu_name) {
      if ($entities[$menu_id]->access('update')) {
        $accessible_menus[$menu_id] = $menu_name;
      }
    }

    return parent::getParentSelectOptions($id, $accessible_menus, $cacheability);
  }

  /**
   * Provides all domain siblings of a given menu.
   *
   * Example: given 'main-aa', the domain siblings are 'main-aa', 'main-nl',
   * 'main-us', etc.
   *
   * @param $menu_id
   *   Given menu id.
   *
   * @return array
   *   Array of menu ID of domain siblings.
   */
  protected function domainSiblings($menu_id) {

    // Menu ID has format: "[base]-[domain code]".
    $menu_id_base = substr($menu_id, 0, strlen($menu_id) - 3); // e.g. "main"

    // Domain ID has the format: "country_[domain code]".
    // New menu ID has the format: "[base]-[domain code]
    $available_domains = \Drupal::service('domain.loader')->loadOptionsList();
    $provided_menu_ids = [];
    foreach (array_keys($available_domains) as $domain_id) {
      $suffix = substr($domain_id, strlen($domain_id) - 2, 2);
      $provided_menu_ids[] = "$menu_id_base-$suffix";
    }

    return $this->getMenuOptions($provided_menu_ids);
  }
}
