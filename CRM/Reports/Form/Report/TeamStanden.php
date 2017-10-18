<?php
use CRM_Reports_ExtensionUtil as E;

class CRM_Reports_Form_Report_TeamStanden extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE; 
	protected $_noFields = TRUE;
	
	private $_teamDataCustomGroupTableName;
	private $_teamDataCustomGroupId;
	private $_teamNrCustomFieldColumnName;
	private $_teamNrCustomFieldId;
	private $_teamNameCustomFieldColumnName;
	private $_teamNameCustomFieldId;
	private $_donatedTowardsCustomGroupId;
	private $_donatedTowardsCustomGroupTableName;
	private $_towardsTeamCustomFieldId;
	private $_towardsTeamCustomFieldColumnName;
	private $_towardsTeamMemberCustomFieldId;
	private $_towardsTeamMemberCustomFieldColumnName;
	private $_completedContributionStatusId;
	private $_teamParticipantRoleId;
	private $_donatieFinancialTypeId;
	private $_collecteFinancialTypeId;
	private $_loterijFinancialTypeId;
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
		
		try {
			$_teamDataCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'team_data'));
			$this->_teamDataCustomGroupId = $_teamDataCustomGroup['id'];
			$this->_teamDataCustomGroupTableName = $_teamDataCustomGroup['table_name'];
		} catch (Exception $ex) {
			throw new Exception('Could not find custom group for Team data');
		}
		try {
			$_teamNrCustomField = civicrm_api3('CustomField', 'getsingle', array('name' => 'team_nr', 'custom_group_id' => $this->_teamDataCustomGroupId));
			$this->_teamNrCustomFieldColumnName = $_teamNrCustomField['column_name'];
			$this->_teamNrCustomFieldId = $_teamNrCustomField['id'];
		} catch (Exception $ex) {
			throw new Exception('Could not find custom field Team NR');
		}
		try {
			$_teamNameCustomField = civicrm_api3('CustomField', 'getsingle', array('name' => 'team_name', 'custom_group_id' => $this->_teamDataCustomGroupId));
			$this->_teamNameCustomFieldColumnName = $_teamNameCustomField['column_name'];
			$this->_teamNameCustomFieldId = $_teamNameCustomField['id'];
		} catch (Exception $ex) {
			throw new Exception('Could not find custom field Team Name');
		}
		
		try {
			$this->_donatieFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', array(
				'name' => 'Donatie',
				'return' => 'id',
			));
		} catch (Exception $e) {
			throw new Exception('Could not retrieve financial type Donatie');
		}
		try {
			$this->_collecteFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', array(
				'name' => 'Opbrengst collecte',
				'return' => 'id',
			));
		} catch (Exception $e) {
			throw new Exception('Could not retrieve financial type Opbrengst collecte');
		}
		try {
			$this->_loterijFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', array(
				'name' => 'Opbrengst lotterij',
				'return' => 'id',
			));
		} catch (Exception $e) {
			throw new Exception('Could not retrieve financial type Opbrengst lotterij');
		}
		try {
			$this->_teamParticipantRoleId = civicrm_api3('OptionValue', 'getvalue', array(
				'return' => 'value',
				'name' => 'Team',
				'option_group_id' => 'participant_role',
			));
		} catch (Exception $ex) {
			throw new Exception ('Could not retrieve the Team participant role');
		}
		try {
			$this->_completedContributionStatusId = civicrm_api3('OptionValue', 'getvalue', array(
				'return' => 'value',
				'name' => 'Completed',
				'option_group_id' => 'contribution_status',
			));
		} catch (Exception $ex) {
			throw new Exception ('Could not retrieve the Contribution status completed');
		}
		try {
			$_donatedTowardsCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'donated_towards'));
			$this->_donatedTowardsCustomGroupId = $_donatedTowardsCustomGroup['id'];
			$this->_donatedTowardsCustomGroupTableName = $_donatedTowardsCustomGroup['table_name'];
		} catch (Exception $ex) {
			throw new Exception('Could not find custom group for Donated Towards');
		}
		try {
			$_towardsTeamCustomField = civicrm_api3('CustomField', 'getsingle', array('name' => 'towards_team', 'custom_group_id' => $this->_donatedTowardsCustomGroupId));
			$this->_towardsTeamCustomFieldColumnName = $_towardsTeamCustomField['column_name'];
			$this->_towardsTeamCustomFieldId = $_towardsTeamCustomField['id'];
		} catch (Exception $ex) {
			throw new Exception('Could not find custom field Towards Team');
		}
		try {
			$_towardsTeamMemberCustomField = civicrm_api3('CustomField', 'getsingle', array('name' => 'towards_team_member', 'custom_group_id' => $this->_donatedTowardsCustomGroupId));
			$this->_towardsTeamMemberCustomFieldColumnName = $_towardsTeamMemberCustomField['column_name'];
			$this->_towardsTeamMemberCustomFieldId = $_towardsTeamMemberCustomField['id'];
		} catch (Exception $ex) {
			throw new Exception('Could not find custom field Towards Team Member');
		}
		
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
    $this->_select = "SELECT {$this->_aliases['civicrm_participant']}.contact_id as contact_id, {$this->_teamNrCustomFieldColumnName} as team_nr, {$this->_teamNameCustomFieldColumnName} as team_name "; 
	}

	function from() {
    $this->_from = "
         FROM civicrm_participant {$this->_aliases['civicrm_participant']}
         INNER JOIN civicrm_event {$this->_aliases['civicrm_event']} ON {$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id
         INNER JOIN {$this->_teamDataCustomGroupTableName} ON {$this->_teamDataCustomGroupTableName}.entity_id = {$this->_aliases['civicrm_participant']}.id  
				 ";
  }
  
  public function orderBy() {
  	$this->_orderBy = "ORDER BY {$this->_teamNrCustomFieldColumnName}, {$this->_teamNameCustomFieldColumnName}";
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
    $this->_whereClauses[] = "{$this->_aliases['civicrm_participant']}.is_test = 0";
    $this->_whereClauses[] = "{$this->_aliases['civicrm_participant']}.role_id = ".$this->_teamParticipantRoleId;

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
		
		$roparunRow = array(
			'contact_id' => false,
			'team_nr' => '',
			'team_name' => 'Roparun',
			'loterij' => '',
			'collecte' => '',
			'donaties_team' => CRM_Generic_Teamstanden::getTotalAmountDonatedForTeams($campaign_id),
			'donaties_teamleden' => '',
			'donaties_roparun' => CRM_Generic_Teamstanden::getTotalAmountDonatedForRoparun($campaign_id),
			'totaal' => CRM_Generic_Teamstanden::getTotalAmountDonated($campaign_id),
		);
		$rows[] = $roparunRow;

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

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

	/**
	 * Returns the total amount donated for a team and a campaign.
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForTeam($team_id, $campaign_id) {
		$financialTypeIds[] = $this->_donatieFinancialTypeId;
		$financialTypeIds[] = $this->_collecteFinancialTypeId;
		$financialTypeIds[] = $this->_loterijFinancialTypeId;
	
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			INNER JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` = %1
			AND civicrm_contribution.campaign_id = %2
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %3";
		$params[1] = array($team_id, 'Integer');
		$params[2] = array($campaign_id, 'Integer');
		$params[3] = array($this->_completedContributionStatusId, 'Integer');
		
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
	
	/**
	 * Returns the total amount donated for a team and a campaign.
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForTeam_OnlyTeam($team_id, $campaign_id) {		
		$financialTypeIds[] = $this->_donatieFinancialTypeId;
	
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			INNER JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` = %1
			AND donated_towards.{$this->_towardsTeamMemberCustomFieldColumnName} IS NULL
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.campaign_id = %2
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %3
			";
		$params[1] = array($team_id, 'Integer');
		$params[2] = array($campaign_id, 'Integer');
		$params[3] = array($this->_completedContributionStatusId, 'Integer');
		
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
	
	/**
	 * Returns the total amount donated for a team and a campaign.
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForTeam_TeamMembers($team_id, $campaign_id) {
		$financialTypeIds[] = $this->_donatieFinancialTypeId;		
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			INNER JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` = %1
			AND donated_towards.{$this->_towardsTeamMemberCustomFieldColumnName} IS NOT NULL
			AND civicrm_contribution.campaign_id = %2
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %3
			";
		$params[1] = array($team_id, 'Integer');
		$params[2] = array($campaign_id, 'Integer');
		$params[3] = array($this->_completedContributionStatusId, 'Integer');
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
	
	/**
	 * Returns the total amount donated for a team and a campaign.
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForTeam_Collecte($team_id, $campaign_id) {		
		$financialTypeIds[] = $this->_collecteFinancialTypeId;
	
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			INNER JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` = %1
			AND civicrm_contribution.campaign_id = %2
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %3";
		$params[1] = array($team_id, 'Integer');
		$params[2] = array($campaign_id, 'Integer');
		$params[3] = array($this->_completedContributionStatusId, 'Integer');
		
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
	
	/**
	 * Returns the total amount donated for a team and a campaign.
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForTeam_Loterij($team_id, $campaign_id) {		
		$financialTypeIds[] = $this->_loterijFinancialTypeId;
	
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			INNER JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` = %1
			AND civicrm_contribution.campaign_id = %2
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %3";
		$params[1] = array($team_id, 'Integer');
		$params[2] = array($campaign_id, 'Integer');
		$params[3] = array($this->_completedContributionStatusId, 'Integer');
		
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
	
	/**
	 * Returns the total amount donated for a campaign
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForRoparun($campaign_id) {		
		$financialTypeIds[] = $this->_donatieFinancialTypeId;
		$financialTypeIds[] = $this->_collecteFinancialTypeId;
		$financialTypeIds[] = $this->_loterijFinancialTypeId;
	
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			LEFT JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE (donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` IS NULL OR donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` = 0)
			AND civicrm_contribution.campaign_id = %1
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %2";
		$params[1] = array($campaign_id, 'Integer');
		$params[2] = array($this->_completedContributionStatusId, 'Integer');
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
	
	/**
	 * Returns the total amount donated for a campaign
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonated($campaign_id) {		
		$financialTypeIds[] = $this->_donatieFinancialTypeId;
		$financialTypeIds[] = $this->_collecteFinancialTypeId;
		$financialTypeIds[] = $this->_loterijFinancialTypeId;
	
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			WHERE civicrm_contribution.campaign_id = %1
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %2";
		$params[1] = array($campaign_id, 'Integer');
		$params[2] = array($this->_completedContributionStatusId, 'Integer');
		
		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}

	/**
	 * Returns the total amount donated for a campaign
	 * 
	 * @param int $team_id
	 * 	The contact id of the team
	 * @param int $campaign_id
	 * 	The ID of the campaign.
	 * @return float
	 */
	protected function getTotalAmountDonatedForTeams($campaign_id) {
		$financialTypeIds[] = $this->_donatieFinancialTypeId;
		$financialTypeIds[] = $this->_collecteFinancialTypeId;
		$financialTypeIds[] = $this->_loterijFinancialTypeId;
		
		$sql = "
			SELECT SUM(total_amount) 
			FROM civicrm_contribution
			LEFT JOIN `{$this->_donatedTowardsCustomGroupTableName}` donated_towards ON donated_towards.entity_id = civicrm_contribution.id
			WHERE (donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` IS NOT NULL AND donated_towards.`{$this->_towardsTeamCustomFieldColumnName}` != 0)
			AND civicrm_contribution.campaign_id = %1
			AND civicrm_contribution.is_test = 0
			AND civicrm_contribution.financial_type_id IN (" . implode(",", $financialTypeIds) . ")
			AND civicrm_contribution.contribution_status_id = %2";
		$params[1] = array($campaign_id, 'Integer');
		$params[2] = array($this->_completedContributionStatusId, 'Integer');

		return (float) CRM_Core_DAO::singleValueQuery($sql, $params);
	}
}	