<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Reports_Form_Report_Teams',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Teams - export to Roparun Live',
      'description' => 'Report with the team information and the teamcaptain. Used as export from CiviCRM to Roparun live.',
      'class_name' => 'CRM_Reports_Form_Report_Teams',
      'report_url' => 'nl.roparun.reports/teams',
      'component' => '',
    ),
  ),
);
