<?php
use CRM_Reports_ExtensionUtil as E;

class CRM_Reports_Form_Report_TeamStanden extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE; 
	protected $_noFields = TRUE;
	private $activeCampaigns;
  
  function __construct() {
  	
		$getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
    $this->activeCampaigns = $getCampaigns['campaigns'];
    asort($this->activeCampaigns);
		
  	$this->_columns = array(
  		'civicrm_participant' => array(
        'dao' => 'CRM_Event_DAO_Participant',
       ),
       'civicrm_event' => array(
        'filters' => array(
          'campaign_id' => array(
            'title' => ts('Campaign'),
        		'operatorType' => CRM_Report_Form::OP_SELECT,
        		'options' => $this->activeCampaigns,
        		'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'grouping' => 'event-fields',
      )
  	);
		
		$this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
		$this->_exposeContactID = FALSE;
		$this->_add2groupSupported = FALSE;
		
		parent::__construct();
	}

	function preProcess() {
    $this->assign('reportTitle', E::ts('Teamstanden'));
    parent::preProcess();
  }

	public function select() {
		$config = CRM_Generic_Config::singleton();
    $this->_select = "SELECT {$this->_aliases['civicrm_participant']}.contact_id as contact_id, {$config->getTeamNrCustomFieldColumnName()} as team_nr, {$config->getTeamNameCustomFieldColumnName()} as team_name "; 
	}

	function from() {
		$config = CRM_Generic_Config::singleton();
    $this->_from = "
         FROM civicrm_participant {$this->_aliases['civicrm_participant']}
         INNER JOIN civicrm_event {$this->_aliases['civicrm_event']} ON {$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id
         INNER JOIN {$config->getTeamDataCustomGroupTableName()} team_data ON team_data.entity_id = {$this->_aliases['civicrm_participant']}.id  
				 ";
  }
  
  public function orderBy() {
  	$config = CRM_Generic_Config::singleton();
  	$this->_orderBy = "ORDER BY {$config->getTeamNrCustomFieldColumnName()}, {$config->getTeamNameCustomFieldColumnName()}";
  }
  
  /**
   * Set limit.
   *
   * @param int $rowCount
   *
   * @return array
   */
  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    $this->_limit = "";	
  }
  
  /**
   * Build where clause.
   */
  public function where() {
    $this->storeWhereHavingClauseArray();
    $config = CRM_Generic_Config::singleton();
    $this->_whereClauses[] = "{$this->_aliases['civicrm_participant']}.is_test = 0";
    $this->_whereClauses[] = "{$this->_aliases['civicrm_participant']}.role_id = ".$config->getTeamParticipantRoleId();

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
  
  /**
   * Modify column headers.
   */
  public function modifyColumnHeaders() {
    $this->_columnHeaders['contact_id'] = array(
    	'no_display' => true,
    );
		$this->_columnHeaders['team_nr'] = array(
    	'title' => 'Nr.',
    	'type' => CRM_Utils_Type::T_INT,
    );
		$this->_columnHeaders['team_name'] = array(
    	'title' => 'Team',
    	'type' => CRM_Utils_Type::T_STRING,
    );
		$this->_columnHeaders['loterij'] = array(
    	'title' => 'Loterij',
    	'type' => CRM_Utils_Type::T_MONEY,
    );
		$this->_columnHeaders['collecte'] = array(
    	'title' => 'Collecte',
    	'type' => CRM_Utils_Type::T_MONEY,
    );
		$this->_columnHeaders['donaties_team'] = array(
    	'title' => 'Donaties team',
    	'type' => CRM_Utils_Type::T_MONEY,
    );
		$this->_columnHeaders['donaties_teamleden'] = array(
    	'title' => 'Donaties deelnemers',
    	'type' => CRM_Utils_Type::T_MONEY,
    );
		$this->_columnHeaders['donaties_roparun'] = array(
    	'title' => 'Donaties roparun',
    	'type' => CRM_Utils_Type::T_MONEY,
    );
		$this->_columnHeaders['totaal'] = array(
    	'title' => 'Totaal',
    	'type' => CRM_Utils_Type::T_MONEY,
    );
  }
	
	/**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
   */
  public function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (!is_array($rows)) {
      $rows = array();
    }

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    $unselectedSectionColumns = $this->unselectedSectionColumns();
		$campaign_id = $this->_params['campaign_id_value'];
		
		$totalRow = array(
			'contact_id' => false,
			'team_nr' => '',
			'team_name' => 'Totaal',
			'loterij' => CRM_Generic_Teamstanden::getTotalAmountLoterij($campaign_id),
			'collecte' => CRM_Generic_Teamstanden::getTotalAmountCollecte($campaign_id),
			'donaties_team' => CRM_Generic_Teamstanden::getTotalAmountDonatedForTeams($campaign_id, true),
			'donaties_teamleden' => '',
			'donaties_roparun' => CRM_Generic_Teamstanden::getTotalAmountDonatedForRoparun($campaign_id),
			'totaal' => CRM_Generic_Teamstanden::getTotalAmountDonated($campaign_id),
		);
		
		$roparunRow = array(
			'contact_id' => false,
			'team_nr' => '',
			'team_name' => 'Roparun',
			'loterij' => CRM_Generic_Teamstanden::getTotalAmountLoterijForRoparun($campaign_id),
			'collecte' => CRM_Generic_Teamstanden::getTotalAmountCollecteForRoparun($campaign_id),
			'donaties_team' => '',
			'donaties_teamleden' => '',
			'donaties_roparun' => CRM_Generic_Teamstanden::getTotalAmountDonatedForRoparun($campaign_id),
			'totaal' => CRM_Generic_Teamstanden::getTotalAmountForRoparun($campaign_id),
		);
		$rows[] = $totalRow;
		$rows[] = $roparunRow;

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }
			$url = CRM_Utils_System::url("civicrm/report/nl.roparun.reports/teamcontributions",
          'reset=1&force=1&team=' . $row['contact_id'].'&campaign_id_op='.$this->_params['campaign_id_op'].'&campaign_id_value='.$this->_params['campaign_id_value'], 
          $this->_absoluteUrl
        );
			$row['team_name_link'] = $url;
      $row['team_name_hover'] = ts("View Contact Summary for this Contact.");

      // section headers not selected for display need to be added to row
      foreach ($unselectedSectionColumns as $key => $values) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }
			
			$row['loterij'] = CRM_Generic_Teamstanden::getTotalAmountDonatedForTeam_Loterij($dao->contact_id, $campaign_id);
			$row['collecte'] = CRM_Generic_Teamstanden::getTotalAmountDonatedForTeam_Collecte($dao->contact_id, $campaign_id);
			$row['donaties_team'] = CRM_Generic_Teamstanden::getTotalAmountDonatedForTeam_OnlyTeam($dao->contact_id, $campaign_id);
			$row['donaties_teamleden'] = CRM_Generic_Teamstanden::getTotalAmountDonatedForTeam_TeamMembers($dao->contact_id, $campaign_id);
			$row['donaties_roparun'] = 0.00;
			$row['totaal'] = CRM_Generic_Teamstanden::getTotalAmountDonatedForTeam($dao->contact_id, $campaign_id);

      $rows[] = $row;
    }
  }
}	