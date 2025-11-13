<?php

/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-21 at 11:32:17
 */

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnused */

namespace Drupal\bm_dashboard\BMDashboardPagebuilder;

use Drupal;
use Drupal\node\Entity\Node;

/**
 * support for gva_row in gavias_pagebuilder.
 * use rows as templates and create / alter rows
 * for dynamic pages.
 */
class BMDashboardPagebuilder {
  // hard coded, must become a config item @mit todo
  const string CANVASS_PAGEBUILDER_TEMPLATE = 'template';

  public function __construct() {

  }

  /**
   * cached
   * [$nid=>['updated'=>updated],$nid=>['updated'=>updated, ..
   * ['names'=>['bm_dashboard_$nid_$n'=>row_name,'bm_dashboard_$nid_$n'=>row_name, ..
   * ['content'=>['bm_dashboard_$nid_$n'=>json content,'bm_dashboard_$nid_$n'=>json content, ..
   *
   * @return array
   */
  public static function rebuild_pagebuilder_templates_cache():array{

    // get the cached template info
    $data = Drupal::cache('bluemarloc')->get('bm_dashboard_pagebuilder');
    if($data)$data= $data->data;
    else $data=[];

    // get the dashboard templates
    $query = Drupal::entityQuery('node')
      ->condition('type', 'dashboard') // The content type of the node.
      ->condition('field_dashboard_type', self::CANVASS_PAGEBUILDER_TEMPLATE);
    $nids = $query->execute();

    if (!empty($nids)) {
      foreach($nids as $nid) {
        // Load the node object using the first node ID.
        $node = Node::load($nid);

        // changes?
        if (!isset($data[$nid]['updated']) || $data[$nid]['updated'] <> $node->getChangedTime()) {
          // reexamine the templates

          if ($node->hasField('gva_pagebuilder_content')) {
            $content = $node->get('gva_pagebuilder_content')->getValue();
            $content = json_decode($content[0]['value'], TRUE);
            // locate elements template_handler and note the class_css (id)
            // perhaps class_css needs updating when duplicating it.. @mit todo
            if (!empty($content)) {
              // cache row_names as template_id's
              foreach ($content as $k => $row) {
                if($row) {
                  $data['names']['bm_dashboard__' . $nid . '_' . $k] = $row['row_name'];
                  $data['content']['bm_dashboard__' . $nid . '_' . $k] = json_encode($row);
                }
              }
            }
          }
          $data[$nid]['updated'] = $node->getChangedTime();
          Drupal::cache('bluemarloc')->set('bm_dashboard_pagebuilder', $data);
          Drupal::messenger()->addStatus('Page builder info cached for later use');
        }
      }
    }
    //dsm($data);
    return $data;
  }
  /**
   * on save of canvass template node, we save the key values to cache.
   * by getting it, it checks existence and rebuilds when needed
   */
  protected static function get_cached_pagebuilder_template_name_and_content(): array {
    $data = Drupal::cache('bluemarloc')->get('bm_dashboard_pagebuilder');
    if($data){
      $data= $data->data;
      //return ['names'=>[],'content'=>[],'updated'=>0,'nid'=>0];
    }
    else {  // cache has been cleared, needs rebuild
      $data = self::rebuild_pagebuilder_templates_cache();
    }
    if(!isset($data['names'])){
      $data['names']=[];
      $data['content']=[];
    }
    return $data;
  }
  /**
   * implements: hook_gva_meta_component
   *
   * the meta component is in gva, gva_meta_component.php
   * Its is a component which can 'draw' in other components
   * which can be more complex. In the case of BMCanvass it
   * enables creating elements for multiple canvasses on the
   * dashboard page. Which makes it possible to dynamically
   * display the progress counters of canvasses available to
   * a current user to be displayed on that user's dashboard.
   *
   * meta_component_info, deliver the names (select options) of 'elements' available
   * for a user to select dashboard components to place on the user dashboard
   *
   * hook_gva_meta_component_info see MP-A-199 Templates extension
   */
  public static function gva_meta_component_info(&$templates): void {
    $data = self::get_cached_pagebuilder_template_name_and_content();
    $templates = array_merge($templates, $data['names']);
  }
  /**
   * Deliver the json element content from cache for a specific template_id (row_name)
   * The info function was used on a pagebuilder page, and a template_id has been set
   * on displaying the dashboard page, it now requests the corresponding setting information
   * to display this element.
   *
   * The element json is taken from the canvass default dashboard content node
   * identified by the row name. On save of the canvass default dashboard node,
   * the template_id's and json are stored into cache and retrieved here.
   *
   * @param $template_id
   *
   * @return string
   */
  public static function gva_meta_component_content($template_id): string {
    $data = self::get_cached_pagebuilder_template_name_and_content();
    if (isset($data[$template_id])) {
      return $data[$template_id]['content'];
    }
    return '';
  }
  /**
   * look through the user dashboard node
   * and create update the template elements indicated by template_handler field
   * which is a key into the template rows at the canvass template page.
   *
   * @param $params
   *
   * @return void
   */
  public static function gva_pagebuilder_content_alter(&$params): void {
//bm_d('',$params,'gva_pagebuilder_content_alter','dsm');
    if (empty($params)) {
      return;
    }

    $hasChanges=false;
    if(str_contains($params,'template_handler')){
     //bm_d('',$params,'contains template_handler gva_pagebuilder_content_alter','dsm');
      // @mit todo at the moment it replaces only top level rows, later add replacing lower levels
      $content = json_decode($params, TRUE);
      if (!empty($content)) {
        $newrows = [];
        foreach ($content as $row) {
          //dsm($row['row_name'],'user dashboard row name');

          // identify our content components (it has template_enabled)
          if (isset($row['columns'][0]['elements'][0]['settings']['template_enabled'])) {

            $element = $row['columns'][0]['elements'][0];
            if (isset($element['settings']['template_enabled']) && $element['settings']['template_enabled'] == "1") {
              $template_id = $element['settings']['template_handler'] ?? '';
              // the template_id (row_name) and row data
              // from the 'template dashboards'
              // are stored in cache and can be retrieved by `template_handler` as key
              $data = self::get_cached_pagebuilder_template_name_and_content();
              if(isset($data['content'][$template_id])) {

                // replace the actual row (in the user's dashboard)
                // with this 'stored' row data from the template dashboard node
                //
                $name = $element['settings']['template_name'] ?? '';
                $overrides = $element['settings']['template_overrides'] ?? '';
                //
                // Blue Marloc, preprocess pagebuilder content row, enable modules to alter the row
                // currently BMCanvassPagebuilder
                $trow = json_decode($data['content'][$template_id], TRUE);

                // dsm($trow);
                //  dsm('Processing row met name:'.$trow['row_name']);
                $params=[
                  'name'=>$name,
                  'overrides'=>$overrides,
                  'template_id'=>$template_id,
                  'row'=>[$trow],
                  'json'=>$data['content'][$template_id]
                ];
                // see BMCanvassPagebuilder for example
                Drupal::moduleHandler()->invokeAll('gva_pagebuilder_content_row_alter',[&$params]);
                // row is updated, additional rows could be present as well

                $newrows =array_merge($newrows, $params['row']);
                // dsm($params['row']);
                $hasChanges = TRUE;
              }
            }
          }else{
            $newrows[] = $row;

          }
         // $newrows[] = $row;
        }
        if($hasChanges)$params = json_encode($newrows);
      }
    }
  }

}
