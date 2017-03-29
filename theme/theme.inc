<?php

function so_forms_preprocess_so_form(&$vars) {

    $form = $vars['element'];

    $vars['form_is_populated'] = $form['#form_is_populated'];
    $vars['advanced_form_is_populated'] = $form['#advanced_form_is_populated'];
    $vars['form_link'] = $form['#form_link'];
    $vars['sfkey'] = $form['#sfkey'];

    $vars['form_title'] = $form['#public_infos']['title'];
    $vars['form_name'] = $form['#public_infos']['name'];
    $vars['secured_sfid'] = $form['#public_infos']['secured_sfid'];

    $vars['normal_fields'] = $form['normal'];
    $vars['advanced_fields'] = $form['advanced'];
    unset($form['normal']);
    unset($form['advanced']);

    $vars['submit'] = render($form['submit']);
    $vars['reset'] = render($form['reset']);
    unset($form['submit']);
    unset($form['reset']);

    $vars['form_params'] = drupal_render_children($form);
}