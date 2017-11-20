<?php
use CRM_Reports_ExtensionUtil as E;

class CRM_Reports_Form_Report_Teams extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Participant');
  protected $_customGroupGroupBy = FALSE; 
  
  function __construct() {
  	$teamCaptainRelationShipTypeId = false;
  	$relationshipTypeOptions = array();
  	$relationshipTypes = $allRelationshipType = CRM_Core_PseudoConstant::relationshipType('label');
		foreach($relationshipTypes as $type_id => $rel_type) {
			if ($rel_type['contact_sub_type_b'] == 'Team') {
				$relationshipTypeOptions[$type_id] = $rel_type['label_a_b'];
			}
		}
		
		$locationTypeOptions = array();
		$locationTypes = new CRM_Core_BAO_LocationType();
		$locationTypes->find();
		while($locationTypes->fetch()) {
			$locationTypeOptions[$locationTypes->id] = $locationTypes->name;
		}
		
    $this->_columns = array(
      'team' => array(
      	'alias' => 'team',
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'display_name' => array(
            'title' => E::ts('Team'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'team_id' => array(
          	'title' => E::ts('Team contactnr.'),
          	'name' => 'id',
            'required' => TRUE,
            'default' => TRUE,
          ),
        ),
        'filters' => array(
        ),
        'grouping' => 'contact-fields',
      ),
      'vestigingsadres' => array(
      	'alias' => 'vestigingsadres',
      	'dao' => 'CRM_Core_DAO_Address',
      	'fields' => array(
      		'vestigings_city' => array(
      			'name' => 'city',
      			'title' => E::ts('Vestingsplaats'),
					),
					'vestigings_postalcode' => array(
      			'name' => 'postal_code',
      			'title' => E::ts('Vestingsplaats postcode'),
					),
				),
				'filters' => array(
					'location_type_id' => array(
          	'name' => 'location_type_id',
          	'title' => ts('Vestigings Location type'),
          	'type' => CRM_Utils_Type::T_INT,
          	'operatorType' => CRM_Report_Form::OP_SELECT,
          	'options' => $locationTypeOptions,
          	'pseudofield' => true,
					),
				),
				'grouping' => 'contact-fields',
			),
			'vestigingsland' => array(
				'alias' => 'vestigingsland',
        'dao' => 'CRM_Core_DAO_Country',
        'fields' => array(
          'name' => array(
          'title' => ts('Vestigingsland'), 
          'default' => TRUE
					),
        ),
        'grouping' => 'contact-fields',
      ),
      'email' => array(
				'alias' => 'email',
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
          'title' => ts('Hoofd E-mail'), 
          'default' => TRUE
					),
        ),
        'grouping' => 'contact-fields',
      ),
      'address' => array(
      	'alias' => 'address',
      	'dao' => 'CRM_Core_DAO_Address',
      	'fields' => array(
      		'hoofd_straat' => array(
      			'name' => 'street_address',
      			'title' => E::ts('Hoofd- straat en huisnummer'),
					),
					'hoofd_postalcode' => array(
      			'name' => 'postal_code',
      			'title' => E::ts('Hoofd- postcode'),
					),
					'hoofd_city' => array(
      			'name' => 'city',
      			'title' => E::ts('Hoofd- plaats'),
					),
				),
			),
			'website' => array(
				'alias' => 'website',
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'website' => array(
          	'name' => 'url',
          	'title' => ts('Website'), 
          	'default' => TRUE
					),
        ),
        'grouping' => 'contact-fields',
      ),
      'facebook' => array(
				'alias' => 'facebook',
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'facebook' => array(
          	'name' => 'url',
          	'title' => ts('Facebook'), 
          	'default' => TRUE
					),
        ),
        'grouping' => 'contact-fields',
      ),
      'twitter' => array(
				'alias' => 'twitter',
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'twitter' => array(
          	'name' => 'url',
          	'title' => ts('Twitter'), 
          	'default' => TRUE
					),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_participant' => array(
        'dao' => 'CRM_Event_DAO_Participant',
        'filters' => array(
          'event_id' => array(
            'name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => array(
              'entity' => 'event',
              'select' => array('minimumInputLength' => 0),
            ),
          ),
          'sid' => array(
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ),
          'rid' => array(
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'grouping' => 'event-fields',
      ),
      'relationship' => array(
      	'dao' => 'CRM_Contact_DAO_Relationship',
      	'filters' => array(
      		'relationship_type_id' => array(
            'title' => ts('Relationship'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $relationshipTypeOptions,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
			),
      'captain' => array(
      	'alias' => 'captain',
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'first_name' => array(
            'title' => E::ts('First name'),
            'default' => TRUE,
            'name' => 'first_name'
          ),
          'middle_name' => array(
            'title' => E::ts('Middle name'),
            'default' => TRUE,
            'name' => 'middle_name'
          ),
          'last_name' => array(
            'title' => E::ts('Last name'),
            'default' => TRUE,
            'name' => 'last_name'
          ),
          'id' => array(
            'title' => E::ts('Captain contactnr.'),
            'required' => TRUE,
          ),
        ),
        'filters' => array(
        ),
        'grouping' => 'teamcaptain-fields',
      ),
      'phone' => array(
				'alias' => 'phone',
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => array(
          'title' => ts('Hoofd Telefoon'), 
          'default' => TRUE
					),
        ),
        'grouping' => 'teamcaptain-fields',
      ),
    );
    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
		$this->_exposeContactID = FALSE;
		$this->_add2groupSupported = FALSE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', E::ts('Teams - export roparun live'));
    parent::preProcess();
  }

  function from() {
    $this->_from = NULL;

		$vestigingsadres_location_type = $this->whereClause($this->_columns['vestigingsadres']['filters']['location_type_id'], $this->_params['location_type_id_op'], $this->_params['location_type_id_value'], 0, 0);
		if (strlen($vestigingsadres_location_type)) {
			$vestigingsadres_location_type = ' AND '.$vestigingsadres_location_type;
		}
		
		$websiteWebsiteTypeId = civicrm_api3('OptionValue', 'getvalue', array(
			'return' => 'value',
			'name' => 'Main',
			'option_group_id' => 'website_type',
		));
		$facebookWebsiteTypeId = civicrm_api3('OptionValue', 'getvalue', array(
			'return' => 'value',
			'name' => 'Facebook',
			'option_group_id' => 'website_type',
		));
		$twitterWebsiteTypeId = civicrm_api3('OptionValue', 'getvalue', array(
			'return' => 'value',
			'name' => 'Twitter',
			'option_group_id' => 'website_type',
		));

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['team']}
         INNER JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
                          ON {$this->_aliases['team']}.id =
                             {$this->_aliases['civicrm_participant']}.contact_id AND {$this->_aliases['civicrm_participant']}.is_test = 0
         INNER JOIN civicrm_relationship {$this->_aliases['relationship']} ON {$this->_aliases['team']}.id = {$this->_aliases['relationship']}.contact_id_b AND {$this->_aliases['relationship']}.is_active = 1
				 INNER JOIN civicrm_contact {$this->_aliases['captain']} ON {$this->_aliases['captain']}.id = {$this->_aliases['relationship']}.contact_id_a
				 LEFT JOIN civicrm_address {$this->_aliases['vestigingsadres']} ON {$this->_aliases['vestigingsadres']}.contact_id = {$this->_aliases['team']}.id {$vestigingsadres_location_type}
				 LEFT JOIN civicrm_country {$this->_aliases['vestigingsland']} ON {$this->_aliases['vestigingsadres']}.country_id = {$this->_aliases['vestigingsland']}.id
				 LEFT JOIN civicrm_email {$this->_aliases['email']} ON {$this->_aliases['email']}.contact_id = {$this->_aliases['captain']}.id AND {$this->_aliases['email']}.is_primary = 1
				 LEFT JOIN civicrm_address {$this->_aliases['address']} ON {$this->_aliases['address']}.contact_id = {$this->_aliases['team']}.id AND {$this->_aliases['address']}.is_primary = 1
				 LEFT JOIN civicrm_phone {$this->_aliases['phone']} ON {$this->_aliases['phone']}.contact_id = {$this->_aliases['captain']}.id AND {$this->_aliases['phone']}.is_primary = 1
				 LEFT JOIN civicrm_website {$this->_aliases['website']} ON {$this->_aliases['website']}.contact_id = {$this->_aliases['team']}.id AND {$this->_aliases['website']}.website_type_id = {$websiteWebsiteTypeId}
				 LEFT JOIN civicrm_website {$this->_aliases['facebook']} ON {$this->_aliases['facebook']}.contact_id = {$this->_aliases['team']}.id AND {$this->_aliases['facebook']}.website_type_id = {$facebookWebsiteTypeId}
				 LEFT JOIN civicrm_website {$this->_aliases['twitter']} ON {$this->_aliases['twitter']}.contact_id = {$this->_aliases['team']}.id AND {$this->_aliases['twitter']}.website_type_id = {$twitterWebsiteTypeId}
				 ";
  }  

  /*function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }*/

  function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
    	if (!empty($row['vestigingsland_name'])) {
    		$rows[$rowNum]['vestigingsland_name'] = ts($row['vestigingsland_name'], array(
          'context' => 'country',
        ));
    	}
    }
  }

}
