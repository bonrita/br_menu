<?php

namespace Drupal\br_menu;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\menu_block\Plugin\Block\MenuBlock;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Class BrMenuBlockBase
 *   Base class for all br's dynamic menus.
 *
 * @package Drupal\br_menu
 */
abstract class BrMenuBlockBase extends MenuBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    $form['menu_levels'] = array(
      '#type' => 'details',
      '#title' => $this->t('Menu levels'),
      // Open if not set to defaults.
      '#open' => $defaults['level'] !== $config['level'] || $defaults['depth'] !== $config['depth'],
      '#process' => [[get_class(), 'processMenuLevelParents']],
    );

    $options = range(0, $this->menuTree->maxDepth());
    unset($options[0]);

    $form['menu_levels']['level'] = array(
      '#type' => 'select',
      '#title' => $this->t('Initial menu level'),
      '#default_value' => $config['level'],
      '#options' => $options,
      '#description' => $this->t('The menu will only be visible if the menu item for the current page is at or below the selected starting level. Select level 1 to always keep this menu visible.'),
      '#required' => TRUE,
    );

    $options[0] = $this->t('Unlimited');

    $form['menu_levels']['depth'] = array(
      '#type' => 'select',
      '#title' => $this->t('Maximum number of menu levels to display'),
      '#default_value' => $config['depth'],
      '#options' => $options,
      '#description' => $this->t('The maximum number of menu levels to show, starting from the initial menu level. For example: with an initial level 2 and a maximum number of 3, menu levels 2, 3 and 4 can be displayed.'),
      '#required' => TRUE,
    );

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu options'),
      '#open' => TRUE,
      '#process' => [[get_class(), 'processMenuBlockFieldSets']],
    ];

    $menu_name = $this->getMenuName();
    $menus = Menu::loadMultiple(array($menu_name));
    $menus[$menu_name] = $menus[$menu_name]->label();

    /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector */
    $menu_parent_selector = \Drupal::service('menu.parent_form_selector');
    $form['advanced']['parent'] = $menu_parent_selector->parentSelectElement($config['parent'], '', $menus);
    $form['advanced']['parent'] += [
      '#title' => $this->t('Dynamic parent menu'),
      '#description' => $this->t('The block will contain item of domain siblings of the selected menu. For example: Select Main navigation Global to get the Main navigation NL if you are on the Dutch site.'),
    ];

    $form['style'] = [
      '#type' => 'details',
      '#title' => $this->t('HTML and style options'),
      '#open' => FALSE,
      '#process' => [[get_class(), 'processMenuBlockFieldSets']],
    ];

    $form['style']['suggestion'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Theme hook suggestion'),
      '#default_value' => $config['suggestion'],
      '#field_prefix' => '<code>menu__</code>',
      '#description' => $this->t('A theme hook suggestion can be used to override the default HTML and CSS classes for menus found in <code>menu.html.twig</code>.'),
      '#machine_name' => [
        'error' => $this->t('The theme hook suggestion must contain only lowercase letters, numbers, and underscores.'),
      ],
    ];

    // Open the details field sets if their config is not set to defaults.
    foreach (['menu_levels', 'advanced', 'style'] as $fieldSet) {
      foreach (array_keys($form[$fieldSet]) as $field) {
        if (isset($defaults[$field]) && $defaults[$field] !== $config[$field]) {
          $form[$fieldSet]['#open'] = TRUE;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['depth'] = $form_state->getValue('depth');
    $this->configuration['parent'] = $form_state->getValue('parent');
    $this->configuration['suggestion'] = $form_state->getValue('suggestion');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getMenuName();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];

    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // Load the tree if we haven't already.
    if (!isset($tree)) {
      $tree = $this->menuTree->load($menu_name, $parameters);
    }
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    if ($level > 1) {
      $manipulators[] = array('callable' => 'menu_block.tree_manipulators:pruneActiveTree');
    }
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
   * Get the machine menu name using menu parent and domain code.
   *
   * @return string
   *   The menu name. Format: [base name]-[domain code]
   */
  protected function getMenuName() {

    $parent = rtrim($this->configuration['parent'], ':');
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
   */
  public function defaultConfiguration() {
    return [
      'level' => 1,
      'depth' => 0,
      'expand' => 0,
      'parent' => $this->getMenuName() . ':',
      'suggestion' => strtr($this->getMenuName(), '-', '_'),
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
