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
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function greatplains_civicrm_xmlMenu(&$files) {
  _greatplains_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function greatplains_civicrm_uninstall() {
  _greatplains_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function greatplains_civicrm_disable() {
  _greatplains_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function greatplains_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _greatplains_civix_civicrm_upgrade($op, $queue);
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
  _greatplains_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function greatplains_civicrm_caseTypes(&$caseTypes) {
  _greatplains_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function greatplains_civicrm_angularModules(&$angularModules) {
_greatplains_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function greatplains_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _greatplains_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * Set a default value for an event price set field.
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function greatplains_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Financial_Form_Search') {
    $optionTypes = greatplains_get_export_types();
    $htmlOptions = '';
    foreach ($optionTypes as $key => $optionType) {
      $htmlOptions .= "<option value='{$key}'>{$optionType}</option>"; 
    }
    $form->assign('htmlOptions', $htmlOptions);
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'FinancialSearch.tpl',
    ));
  }
  if ($formName == 'CRM_Financial_Form_Export') {
    $optionTypes = greatplains_get_export_types();
    $form->addRadio('export_format', NULL, $optionTypes, NULL, '<br/>', TRUE);
  }
}

/**
 * build export types
 */
function greatplains_get_export_types() {
  $optionTypes = array(
    'IIF' => ts('Export to IIF'),
    'CSV' => ts('Export to CSV'),
  );
  return array_merge(
    $optionTypes,
    CRM_Core_OptionGroup::values('financial_batch_export_format')
  );
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
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
