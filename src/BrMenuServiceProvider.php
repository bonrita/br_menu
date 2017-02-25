<?php

namespace Drupal\br_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class BrMenuServiceProvider
 *
 * Change the class for menu.parent_form_selector service.
 *
 * @package Drupal\mm_domain_role
 */
class BrMenuServiceProvider extends ServiceProviderBase {
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('menu.parent_form_selector')->setClass('\Drupal\br_menu\BrMenuParentFormSelector');
  }
}
