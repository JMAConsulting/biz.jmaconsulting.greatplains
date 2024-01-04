<?php

require_once 'greatplains.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function greatplains_civicrm_config(&$config) {
  _greatplains_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function greatplains_civicrm_install() {
  _greatplains_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function greatplains_civicrm_enable() {
  _greatplains_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function greatplains_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'biz.jmaconsulting.greatplains',
    'name' => 'greatplains',
    'update' => 'never',
    'entity' => 'OptionGroup',
    'params' => array(
      'title' => 'Financial Batch Export Format',
      'name' => 'financial_batch_export_format',
      'description' => 'Financial Batch Export Format',
      'is_active' => 1,
      'is_reserved' => 1,
      'version' => 3,
      'sequential' => 1,
      'api.OptionValue.create' => array(
        'label' => 'Export to Microsoft Great Plains',
        'value' => 'MGP',
        'is_default' => 1,
        'is_active' => 1,
      ),
    ),
  );
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * Set a default value for an event price set field.
 *
 */
function greatplains_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Financial_Form_Search') {
    $exportTypes = $form->get_template_vars('exportTypes');
    $exportTypes = greatplains_get_export_types($exportTypes);
    $form->assign('exportTypes', $exportTypes);
  }
  if ($formName == 'CRM_Financial_Form_Export') {
    $optionTypes = greatplains_get_export_types();
    $form->addRadio('export_format', NULL, $optionTypes, NULL, '<br/>', TRUE);
  }
}

/**
 * build export types
 */
function greatplains_get_export_types($optionTypes = NULL) {
  if (!$optionTypes) {
    $optionTypes = array(
      'IIF' => ts('Export to IIF'),
      'CSV' => ts('Export to CSV'),
    );
  }
  return array_merge(
    $optionTypes,
    CRM_Core_OptionGroup::values('financial_batch_export_format')
  );
}

/**
 * Implements hook_civicrm_postProcess().
 *
 */
function greatplains_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Financial_Form_Export') {
    $exportFormat = $form->getVar('_exportFormat');
    if (!in_array($exportFormat, array('IIF', 'CSV'))) {
      $exporterClass = "CRM_Financial_BAO_ExportFormat_" . $exportFormat;
      $exporter = new $exporterClass();
      $batchIds = explode(',', $form->getVar('_batchIds'));
      foreach ($batchIds as $batchId) {
        $export[$batchId] = $exporter->generateExportQuery($batchId);
      }
      $exporter->makeCSV($export);
    }
  }
}
