<?php
use CRM_Reports_ExtensionUtil as E;

class CRM_Reports_Form_Report_DeletedContactWithContributions extends CRM_Report_Form {
	
	protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE; 
	protected $_noFields = TRUE;
  
  function __construct() {		
		$config = CRM_Generic_Config::singleton();
		
  	$this->_columns = array(
  		'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
        	'display_name' => array(
        		'title' => E::ts('Donateur'),
        		'required' => TRUE,
        		'default' => TRUE,
      		),
      		'id' => array(
        		'no_display' => TRUE,
        		'required' => TRUE,
      		),
      		'is_deleted' => array(
        		'no_display' => TRUE,
        		'required' => TRUE,
      		),
				),
        'grouping' => 'contact-fields',
      ),
      'towards_team' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
        	'team_name' => array(
        		'name' => 'display_name',
        		'title' => E::ts('Team'),
        		'required' => TRUE,
        		'default' => TRUE,
      		),
      		'id' => array(
        		'no_display' => TRUE,
        		'required' => TRUE,
      		),
      		'is_deleted' => array(
        		'no_display' => TRUE,
        		'required' => TRUE,
      		),
				),
        'grouping' => 'contact-fields',
      ),
      'towards_team_member' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
        	'teamlid_name' => array(
        		'name' => 'display_name',
        		'title' => E::ts('Teamlid'),
        		'required' => TRUE,
        		'default' => TRUE,
      		),
      		'id' => array(
        		'no_display' => TRUE,
        		'required' => TRUE,
      		),
      		'is_deleted' => array(
        		'no_display' => TRUE,
        		'required' => TRUE,
      		),
				),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' => array(
      	'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
        	'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'default' => TRUE,
          ),
          'payment_instrument_id' => array(
            'title' => ts('Payment Instrument'),
          ),
        	'receive_date' => array(
        		'default' => TRUE,
        		'type' => CRM_Utils_Type::T_DATE,
					),
					'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'total_amount' => array(
            'title' => ts('Amount'),
            'required' => TRUE,
            'statistics' => array('sum' => ts('Amount')),
          ),
        ),
        'filters' => array (
        	'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'payment_instrument_id' => array(
            'title' => ts('Payment Instrument'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
            'type' => CRM_Utils_Type::T_INT,
          ),
				),
				'order_bys' => array(
          'financial_type_id' => array(
          	'title' => ts('Financial Type'),
						'default_is_section' => true,
						'default' => true,
						'default_weight' => 2,
					),
          'contribution_status_id' => array('title' => ts('Contribution Status')),
          'payment_instrument_id' => array('title' => ts('Payment Method')),
          'receive_date' => array('title' => ts('Date Received')),
        ),
			),
  	);
		
		$this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
		$this->_exposeContactID = FALSE;
		$this->_add2groupSupported = FALSE;
		
		$this->_team = CRM_Utils_Request::retrieve('team', 'Integer');
		
		parent::__construct();
	}
	
	public function select() {
		parent::select();
		//total_amount was affected by sum as it is considered as one of the stat field
    //so it is been replaced with correct alias, CRM-13833
    $this->_select = str_replace("sum({$this->_aliases['civicrm_contribution']}.total_amount)", "{$this->_aliases['civicrm_contribution']}.total_amount", $this->_select);
    $this->_selectClauses = str_replace("sum({$this->_aliases['civicrm_contribution']}.total_amount)", "{$this->_aliases['civicrm_contribution']}.total_amount", $this->_selectClauses);
	}

	function from() {
		$config = CRM_Generic_Config::singleton();
    $this->_from = "
         FROM civicrm_contribution {$this->_aliases['civicrm_contribution']}
         INNER JOIN {$config->getDonatedTowardsCustomGroupTableName()} donated_towards ON donated_towards.entity_id = {$this->_aliases['civicrm_contribution']}.id
         LEFT JOIN civicrm_contact {$this->_aliases['towards_team_member']} ON {$this->_aliases['towards_team_member']}.id = donated_towards.{$config->getTowardsTeamMemberCustomFieldColumnName()}
         LEFT JOIN civicrm_contact {$this->_aliases['towards_team']} ON {$this->_aliases['towards_team']}.id = donated_towards.{$config->getTowardsTeamCustomFieldColumnName()} 
         INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON {$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact']}.id    
				 ";
  }
  
  /**
   * Build where clause.
   */
  public function where() {
    $this->storeWhereHavingClauseArray();
    $config = CRM_Generic_Config::singleton();
    $this->_whereClauses[] = "{$this->_aliases['civicrm_contribution']}.is_test = 0";
    $this->_whereClauses[] = "{$this->_aliases['civicrm_contribution']}.contribution_status_id = '".$config->getCompletedContributionStatusId()."'";
    $this->_whereClauses[] = "({$this->_aliases['towards_team']}.is_deleted = 1 OR {$this->_aliases['towards_team_member']}.is_deleted = 1)";

    if (empty($this->_whereClauses)) {
      $this->_where = "WHERE ( 1 ) ";
      $this->_having = "";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($this->_havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $this->_havingClauses);
    }
  }
  
  public function sectionTotals() {

    // Reports using order_bys with sections must populate $this->_selectAliases in select() method.
    if (empty($this->_selectAliases)) {
      return;
    }

    if (!empty($this->_sections)) {
      // build the query with no LIMIT clause
      $select = str_ireplace('SELECT SQL_CALC_FOUND_ROWS ', 'SELECT ', $this->_select);
      $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";

      // pull section aliases out of $this->_sections
      $ifnulls = array();
			$sectionAliases = array();
			foreach($this->_sections as $section) {
				$ifnulls[] = "IFNULL({$section['dbAlias']}, '') AS {$section['tplField']}";
				$sectionAliases[] = $section['tplField'];
			}
			
      $this->_select.= ", " . implode(", ", $ifnulls);

      /* Group (un-limited) report by all aliases and get counts. This might
       * be done more efficiently when the contents of $sql are known, ie. by
       * overriding this method in the report class.
       */

      $addtotals = '';

      if (array_search("civicrm_contribution_total_amount_sum", $this->_selectAliases) !==
        FALSE
      ) {
        $addtotals = ", sum({$this->_aliases['civicrm_contribution']}.total_amount) as sumcontribs";
        $showsumcontribs = TRUE;
      }

      $query = $this->_select .
        "$addtotals, count(*) as ct {$this->_from} {$this->_where} group by " .
        implode(", ", $sectionAliases);
      // initialize array of total counts
      $sumcontribs = $totals = array();
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {

        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = array();
        $i = 1;
        $aliasCount = count($sectionAliases);
        foreach ($sectionAliases as $alias) {
          $values[] = $row[$alias];
          $key = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values);
          if ($i == $aliasCount) {
            // the last alias is the lowest-level section header; use count as-is
            $totals[$key] = $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = $dao->sumcontribs;
            }
          }
          else {
            // other aliases are higher level; roll count into their total
            $totals[$key] = (array_key_exists($key, $totals)) ? $totals[$key] + $dao->ct : $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = array_key_exists($key, $sumcontribs) ? $sumcontribs[$key] + $dao->sumcontribs : $dao->sumcontribs;
            }
          }
        }
      }
      if ($showsumcontribs) {
        $totalandsum = array();
        // ts exception to avoid having ts("%1 %2: %3")
				$title = '%1 contributions: %2';
        foreach ($totals as $key => $total) {
          $totalandsum[$key] = ts($title, array(
            1 => $total,
            2 => CRM_Utils_Money::format($sumcontribs[$key]),
          ));
        }
        $this->assign('sectionTotals', $totalandsum);
      }
      else {
        $this->assign('sectionTotals', $totals);
      }
    }
  }

	/**
   * Modify column headers.
   */
  public function modifyColumnHeaders() {
    $this->_columnHeaders['team_id']['no_display'] = true;
  }

	/**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $checkList = array();
    $entryFound = FALSE;
    $display_flag = $prev_cid = $cid = 0;
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage();
    $batches = CRM_Batch_BAO_Batch::getBatches();
    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                // Hide repeats in no-repeat columns, but not if the field's a section header
                if (in_array($colName, $this->_noRepeats) &&
                  !array_key_exists($colName, $this->_sections)
                ) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contribution/detail', ts('View Contribution Details')) ? TRUE : $entryFound;
      // convert donor sort name to link
      if (array_key_exists('civicrm_contact_display_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_display_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
				if (isset($row['civicrm_contact_is_deleted']) && !empty($row['civicrm_contact_is_deleted'])) {
					$rows[$rowNum]['civicrm_contact_display_name'] = '<del>'.$rows[$rowNum]['civicrm_contact_display_name'].'</del>';	
				}
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact Summary for this Contact.");
      }
			
			if (array_key_exists('towards_team_team_name', $row) &&
        !empty($rows[$rowNum]['towards_team_team_name']) &&
        array_key_exists('towards_team_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['towards_team_id'],
          $this->_absoluteUrl
        );
				if (isset($row['towards_team_is_deleted']) && !empty($row['towards_team_is_deleted'])) {
					$rows[$rowNum]['towards_team_team_name'] = '<del>'.$rows[$rowNum]['towards_team_team_name'].'</del>';	
				}
        $rows[$rowNum]['towards_team_team_name_link'] = $url;
        $rows[$rowNum]['towards_team_team_name_hover'] = ts("View Contact Summary for this Contact.");
      }

			if (array_key_exists('towards_team_member_teamlid_name', $row) &&
        !empty($rows[$rowNum]['towards_team_member_teamlid_name']) &&
        array_key_exists('towards_team_member_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['towards_team_member_id'],
          $this->_absoluteUrl
        );
				if (isset($row['towards_team_member_is_deleted']) && !empty($row['towards_team_member_is_deleted'])) {
					$rows[$rowNum]['towards_team_member_teamlid_name'] = '<del>'.$rows[$rowNum]['towards_team_member_teamlid_name'].'</del>';	
				}
        $rows[$rowNum]['towards_team_member_teamlid_name_link'] = $url;
        $rows[$rowNum]['towards_team_member_teamlid_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }
	
}