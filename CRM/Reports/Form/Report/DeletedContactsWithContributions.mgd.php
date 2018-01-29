<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Reports_Form_Report_DeletedContactWithContributions',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Bijdragen op naam van van een verwijderd Team of een verwijderd teamlid',
      'description' => 'Rapport met de bijdragen op naam van een verwijderd team of een verwijderd teamlid.',
      'class_name' => 'CRM_Reports_Form_Report_DeletedContactWithContributions',
      'report_url' => 'nl.roparun.reports/deletedcontactswithcontributions',
      'component' => '',
    ),
  ),
);
