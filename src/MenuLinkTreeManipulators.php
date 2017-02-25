<?php

namespace Drupal\br_menu;

/**
 * Provides menu link tree manipulators for Menu Block tasks. Manipulators
 * modify the menu tree to for a specific and limited purpose.
 */
class MenuLinkTreeManipulators {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Remove the top level from a menu tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function chopTreeLevel(array $tree) {

    if (count($tree) == 1 && !empty($tree)) {
      $first = reset($tree);
      if ($first) {
        $tree = $first->subtree;
      }
    }

    return $tree;
  }

  /**
   * Cut branches and only leave the default branch.
   *
   * The default branch is the branch of the current industry. If not set, it
   * is the first branch.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function pruneDefaultBranch(array $tree) {
    $result = [];
    $industryNode = \Drupal::service('br_ips.industry_manager')->getCurrentNode();

    if ($industryNode) {
      $industryMenuLink = $industryNode->get('br_menu_link');
      if ($industryMenuLink) {

        $menuLinkUuid = $industryMenuLink->entity->uuid();
        foreach ($tree as $key => $treeElement) {
          if (strpos($key, $menuLinkUuid) !== FALSE) {
            $result = [$treeElement];
            // Drupal 8 does not have a way to force set the active trail. The
            // active trail is set in the theme.
            // @see br_global_preprocess_menu__main__level1().
            break;
          }
        }

      }
    }

    // Fallback to the first branch.
    if (empty($result)) {
      $result = [reset($tree)];
    }

    return $result;
  }

}
