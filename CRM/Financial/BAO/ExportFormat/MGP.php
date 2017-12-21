<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * @link http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+Specifications+-++Batches#CiviAccountsSpecifications-Batches-%C2%A0Overviewofimplementation
 */
/**
 * This files supports the export of financial batches to Microsoft Great Plains in the format it expects.
 */
class CRM_Financial_BAO_ExportFormat_MGP extends CRM_Financial_BAO_ExportFormat {

  /**
   * For this phase, we always output these records too so that there isn't data
   * referenced in the journal entries that isn't defined anywhere.
   *
   * Possibly in the future this could be selected by the user.
   */
  public static $complementaryTables = array(
    'ACCNT',
    'CUST',
  );

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * @param array $exportParams
   */
  public function export($exportParams) {
    $export = parent::export($exportParams);

    // Save the file in the public directory.
    $fileName = self::putFile($export);

    foreach (self::$complementaryTables as $rct) {
      $func = "export{$rct}";
      $this->$func();
    }

    $this->output($fileName);
  }

  /**
   * @param int $batchId
   *
   * @return Object
   */
  public function generateExportQuery($batchId) {
    $sql = "SELECT batch_id, trxn_date, amount, account_code, contact_name FROM
(
      SELECT
      ft.id as ft_id,
      -1 as fi_id,
      'D' as credit_or_debit,
      eb.batch_id as batch_id,
      ft.trxn_date as trxn_date,
      eft.amount AS amount,
      IF(fa_to.id IS NULL, fa_to_li.accounting_code, fa_to.accounting_code) AS account_code,
      IF(fa_to.id IS NULL, contact_to_fi.display_name, contact_to_fa.display_name) AS contact_name
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_financial_account fa_to ON fa_to.id = ft.to_financial_account_id
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cc ON (eft.entity_id = cc.id)
      LEFT JOIN civicrm_contact contact_to ON contact_to.id = fa_to.contact_id
      LEFT JOIN civicrm_entity_financial_trxn efti ON (efti.financial_trxn_id  = ft.id AND efti.entity_table = 'civicrm_financial_item')
      LEFT JOIN civicrm_financial_item fi ON fi.id = efti.entity_id
      LEFT JOIN civicrm_financial_account fa_to_li ON fa_to_li.id = fi.financial_account_id
      LEFT JOIN civicrm_contact contact_to_fa ON contact_to_fa.id=fa_to.contact_id
      LEFT JOIN civicrm_contact contact_to_fi ON contact_to_fi.id=fi.contact_id
      WHERE eb.batch_id = ( %1 )
UNION
      SELECT
      ft.id as ft_id,
      IF(fa_from.id IS NULL, fi.id, 0) fi_id,
      'C' as credit_or_debit,
      eb.batch_id as batch_id,
      ft.trxn_date as trxn_date,
      IF(fa_from.id IS NULL, -efti.amount, -eft.amount) AS amount,
      IF(fa_from.id IS NULL, fa_from_li.accounting_code, fa_from.accounting_code) AS account_code,
      IF(fa_from.id IS NULL, contact_from_contrib.display_name, contact_from_fa.display_name) AS contact_name
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_financial_account fa_from ON fa_from.id = ft.from_financial_account_id
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cc ON (eft.entity_id = cc.id)
      LEFT JOIN civicrm_contact contact_from_contrib ON contact_from_contrib.id = cc.contact_id
      LEFT JOIN civicrm_entity_financial_trxn efti ON (efti.financial_trxn_id  = ft.id AND efti.entity_table = 'civicrm_financial_item')
      LEFT JOIN civicrm_financial_item fi ON fi.id = efti.entity_id
      LEFT JOIN civicrm_financial_account fa_from_li ON fa_from_li.id = fi.financial_account_id
      LEFT JOIN civicrm_contact contact_from_fa ON contact_from_fa.id=fa_from.contact_id
      WHERE eb.batch_id = ( %1 )
) as S1
ORDER BY batch_id, ft_id, fi_id, credit_or_debit DESC;";

    CRM_Utils_Hook::batchQuery($sql);

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    return $dao;
  }

  /**
   * @param $export
   *
   * @return string
   */
  public function putFile($export) {
    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . 'Financial_Transactions_' . $this->_batchIds . '_' . date('YmdHis') . '.' . $this->getFileExtension();
    $this->_downloadFile[] = $config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName));
    $out = fopen($fileName, 'w');
    fputcsv($out, $export['headers']);
    unset($export['headers']);
    if (!empty($export)) {
      foreach ($export as $fields) {
        fputcsv($out, $fields);
      }
      fclose($out);
    }
    return $fileName;
  }

  /**
   * Format table headers.
   *
   * @param array $values
   * @return array
   */
  public function formatHeaders($values) {
    $arrayKeys = array_keys($values);
    $headers = '';
    if (!empty($arrayKeys)) {
      foreach ($values[$arrayKeys[0]] as $title => $value) {
        $headers[] = $title;
      }
    }
    return $headers;
  }

  /**
   * Generate CSV array for export.
   *
   * @param array $export
   */
  public function makeCSV($export) {

    foreach ($export as $batchId => $dao) {
      $financialItems = array();
      $this->_batchIds = $batchId;

      $batchItems = array();
      $queryResults = array();

      while ($dao->fetch()) {
        $financialItems[] = array(
          'Batch ID' => $dao->batch_id,
          'Date' => $this->format($dao->trxn_date, 'date'),
          'Reference' => $dao->contact_name,
          'Acct' => $dao->account_code,
          'Amount' => $this->format($dao->amount, 'money'),
        );

        end($financialItems);
        $batchItems[] = &$financialItems[key($financialItems)];
        $queryResults[] = get_object_vars($dao);
      }

      CRM_Utils_Hook::batchItems($queryResults, $batchItems);

      $financialItems['headers'] = self::formatHeaders($financialItems);
      self::export($financialItems);
    }
    parent::initiateDownload();
  }

  /**
   * @param string $s
   *   the input string
   * @param string $type
   *   type can be string, date, or notepad
   *
   * @return bool|mixed|string
   */
  public static function format($s, $type = 'string') {
    // If I remember right there's a couple things:
    // NOTEPAD field needs to be surrounded by quotes and then get rid of double quotes inside, also newlines should be literal \n, and ditch any ascii 0x0d's.
    // Date handling has changed over the years. It used to only understand mm/dd/yy but I think now it might depend on your OS settings. Sometimes mm/dd/yyyy works but sometimes it wants yyyy/mm/dd, at least where I had used it.
    // In all cases need to do something with tabs in the input.

    switch ($type) {
      case 'date':
        $dateFormat = Civi::settings()->get('dateformatFinancialBatch');
        $sout = CRM_Utils_Date::customFormat($s, $dateFormat);
        break;

      case 'money':
        $sout = CRM_Utils_Money::format($s, NULL, NULL, TRUE);
        break;

      case 'string':
      case 'notepad':
        $s2 = str_replace("\n", '\n', $s);
        $s3 = str_replace("\r", '', $s2);
        $s4 = str_replace('"', "'", $s3);
        if ($type == 'notepad') {
          $sout = '"' . $s4 . '"';
        }
        else {
          $sout = $s4;
        }
        break;
    }

    return $sout;
  }

  /**
   * @return string
   */
  public function getFileExtension() {
    return 'csv';
  }

  public function exportACCNT() {
  }

  public function exportCUST() {
  }

  public function exportTRANS() {
  }

  public function makeExport($export) {
    foreach ($export as $batchId => $dao) {
      $financialItems = array();
      $this->_batchIds = $batchId;

      $batchItems = array();
      $queryResults = array();

      while ($dao->fetch()) {
        $financialItems[] = array(
          'Batch ID' => $dao->batch_id,
          'Date' => $this->format($dao->trxn_date, 'date'),
          'Reference' => $dao->contact_name,
          'Acct' => $dao->account_code,
          'Amount' => $this->format($dao->amount, 'money'),
        );

        end($financialItems);
        $batchItems[] = &$financialItems[key($financialItems)];
        $queryResults[] = get_object_vars($dao);
      }

      CRM_Utils_Hook::batchItems($queryResults, $batchItems);

      $financialItems['headers'] = self::formatHeaders($financialItems);
      self::export($financialItems);
    }
    parent::initiateDownload();
  }

}
