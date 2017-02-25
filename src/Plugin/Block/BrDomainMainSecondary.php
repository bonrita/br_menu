<?php

namespace Drupal\br_menu\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_block\Plugin\Block\MenuBlock;

/**
 * Menu block for the secondary navigation menu.
 *
 * @Block(
 *   id = "br_menu_domain_main_secondary",
 *   admin_label = @Translation("Main Navigation - Secondary"),
 *   category = @Translation("Br Menu"),
 * )
 */
class BrDomainMainSecondary extends MenuBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $menu_name = $this->getMenuName();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $configuration = $this->defaultConfiguration();

    // Force to always expand the whole tree, and not only the active tree.
    $parameters->expandedParents = array();

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $configuration['level'];
    $depth = $configuration['depth'];

    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    if ($tree) {
      $manipulators = array(
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $this->menuTree->transform($tree, $manipulators);
      $indexedTree = $tree;

      // If the current page does not have a menu items (e.g. 404 page), the
      // tree will be empty after pruneActiveTree. In that case we prune the tree
      // to show the default tree.
      $manipulators = array(
        array('callable' => 'menu_block.tree_manipulators:pruneActiveTree'),
      );
      $tree = $this->menuTree->transform($indexedTree, $manipulators);
      if (empty($tree)) {
        $manipulators = array(
          array('callable' => 'br_menu.tree_manipulators:pruneDefaultBranch'),
        );
        $tree = $this->menuTree->transform($indexedTree, $manipulators);
      }

      $manipulators = array(
        array('callable' => 'br_menu.tree_manipulators:chopTreeLevel'),
      );
      $tree = $this->menuTree->transform($tree, $manipulators);
      $build = $this->menuTree->build($tree);

      if (!empty($build['#theme'])) {
        // Add the configuration for use in menu_block_theme_suggestions_menu().
        $build['#menu_block_configuration'] = $configuration;
        // Remove the menu name-based suggestion so we can control its precedence
        // better in menu_block_theme_suggestions_menu().
        $build['#theme'] = 'menu';
      }
    }

    return $build;
  }

  /**
   * Get the machine menu name using menu parent and domain code.
   *
   * @return string
   *   The menu name. Format: [base name]-[domain code]
   */
  protected function getMenuName() {
    $configuration = $this->defaultConfiguration();
    $parent = $configuration['parent'];
    $matches = [];

    // The selected parent menu is use to determine the menu base name. The
    // domain code of the selected menu is ignored and replaced by the
    // domain code of the current domain.
    // If the parent menu is not a domain menu name, it is used without change.
    if (preg_match('/(.*)-[a-z]{2}$/', $parent, $matches)) {
      $base_name = $matches[1];

      $country_service = \Drupal::service('br_country.current');
      $domain_code = strtolower($country_service->getSlug());

      $menu_name = "$base_name-$domain_code";
    }
    else {
      $menu_name = $parent;
    }

    return $menu_name;
  }

  /**
   * {@inheritdoc}
   *
   * This block has no form settings. All configuration is set in this method.
   */
  public function defaultConfiguration() {
    return [
      'level' => 1, // One level higher that we display. This is needed to show all children if only the parent is active.
      'depth' => 4,
      'expand' => 1,
      'parent' => 'main-aa',
      'suggestion' => 'main__level2',
    ];
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

    $tags[] = 'config:system.menu.' . $this->getMenuName();
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];
    // Applied contexts can affect the cache contexts when this plugin is
    // involved in caching, collect and return them.
    foreach ($this->getContexts() as $context) {
      /** @var $context \Drupal\Core\Cache\CacheableDependencyInterface */
      if ($context instanceof CacheableDependencyInterface) {
        $cache_contexts = Cache::mergeContexts($cache_contexts, $context->getCacheContexts());
      }
    }

    $menu_name = $this->getMenuName();
    return Cache::mergeContexts($cache_contexts, ['route.menu_active_trails:' . $menu_name]);
  }

}
