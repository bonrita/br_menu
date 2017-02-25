<?php

namespace Drupal\br_menu;

use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorage;

/**
 * Class BrMenuIndustrySpecificBase
 *
 * @package Drupal\br_menu
 */
abstract class BrMenuIndustrySpecificBase extends BrMenuBlockBase {

  /**
   * Get the menu industry specific expected attribute value.
   *
   *  This value is always set on the immediate parent of the child links
   *  that are to be rendered.
   *
   * @return string
   *   The industry specific expected attribute value.
   */
  abstract protected function getIndustrySpecificExpectedAttributeValue();

  /**
   * {@inheritdoc}
   */
  public function build() {
    // The actual subtree to render.
    $tree_list = [];
    $industryManager = \Drupal::service('br_ips.industry_manager');
    $menu_name = $this->getMenuName();
    $treeStorage = new MenuTreeStorage(\Drupal::service('database'), \Drupal::service('cache.menu'), \Drupal::service('cache_tags.invalidator'), 'menu_tree');
    $parameters = new MenuTreeParameters();

    // Get the current industry entity.
    $industryNode = $industryManager->getCurrentNode();

    if ($industryNode) {
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $industry_menu_link_content */
      $industry_menu_link_content = $industryNode->get('br_menu_link')->entity;

      $plugin_id = $industry_menu_link_content->getPluginId();
      $parameters->setRoot($plugin_id);

      $full_tree = $treeStorage->loadTreeData($menu_name, $parameters);

      // Get the tree.
      foreach ($full_tree['tree'] as $parent_menu_link) {
        if (!empty($parent_menu_link['subtree'])) {
          foreach ($parent_menu_link['subtree'] as $item) {
            if (isset($item['definition']['options']['attributes']['industry_specific'])) {
              $menu_item_industry = $item['definition']['options']['attributes']['industry_specific'];

              if ($menu_item_industry == $this->getIndustrySpecificExpectedAttributeValue()) {
                $sub_tree = $treeStorage->loadSubtreeData($item['definition']['id']);
                $sub_tree_data = reset($sub_tree['tree']);

                if (!empty($sub_tree_data['subtree'])) {
                  $tree_list = $sub_tree_data['subtree'];
                  break;
                }
              }
            }
          }
        }
      }
    }

    if (empty($tree_list)) {
      return [];
    }

    $tree = $this->createInstances($tree_list);

    // Load the tree if we haven't already.
    if (!isset($tree)) {
      $tree = $this->menuTree->load($menu_name, $parameters);
    }
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );

    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);

    if (!empty($build['#theme'])) {
      // Add the configuration for use in menu_block_theme_suggestions_menu().
      $build['#menu_block_configuration'] = $this->configuration;
      // Remove the menu name-based suggestion so we can control its precedence
      // better in menu_block_theme_suggestions_menu().
      $build['#theme'] = 'menu';
    }

    return $build;
  }

  /**
   * Returns a tree containing of MenuLinkTreeElement based upon tree data.
   *
   * This method converts the tree representation as array coming from the tree
   * storage to a tree containing a list of MenuLinkTreeElement[].
   *
   * @param array $data_tree
   *   The tree data coming from the menu tree storage.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array containing the elements of a menu tree.
   */
  protected function createInstances(array $data_tree) {
    $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $tree = array();
    foreach ($data_tree as $key => $element) {
      $subtree = $this->createInstances($element['subtree']);
      // Build a MenuLinkTreeElement out of the menu tree link definition:
      // transform the tree link definition into a link definition and store
      // tree metadata.
      $tree[$key] = new MenuLinkTreeElement(
        $menuLinkManager->createInstance($element['definition']['id']),
        (bool) $element['has_children'],
        (int) $element['depth'],
        (bool) $element['in_active_trail'],
        $subtree
      );
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];
    // Applied contexts can affect the cache tags when this plugin is
    // involved in caching, collect and return them.
    foreach ($this->getContexts() as $context) {
      /** @var $context \Drupal\Core\Cache\CacheableDependencyInterface */
      if ($context instanceof CacheableDependencyInterface) {
        $tags = Cache::mergeTags($tags, $context->getCacheTags());
      }
    }

    $industryManager = \Drupal::service('br_ips.industry_manager');
    /** @var \Drupal\node\Entity\Node $industryNode */
    $industryNode = $industryManager->getCurrentNode();
    if ($industryNode) {
      $node_tags = $industryNode->getCacheTags();
      $tags[] = reset($node_tags);
    }

    $tags[] = 'config:system.menu.' . $this->getMenuName();
    return $tags;
  }

}
