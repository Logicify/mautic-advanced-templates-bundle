<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

class FormSubmission
{

    /**
     * FormSubmission constructor.
     *
     */
    public function __construct($dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * Try to retrieve the current form values of the active lead 
     * 
     * @param integer $leadId  
     * @param integer $emailId
     */
    public function getFormData($leadId)
    {

        $formData = array();

        $connection = $this->dbal;
        $stmt       =  $connection->executeQuery(
            "SELECT * from form_submissions fs
             WHERE
                fs.lead_id = $leadId
                order by date_submitted desc"
        );
        $stmt->execute();

        $formSubmissions = $stmt->fetchAll();
        if (!$formSubmissions) {
            return array();
        }

        //search form submissions
        $formId = false;
        foreach ($formSubmissions as $submission) 
        {
            $formId = (int) $submission['form_id'];
            $formSubmissionEntry = $submission;
            break;
        }

        if (!$formId) {
            return array();
        }

        // build name for form result table
        $stmt       =  $connection->executeQuery(
            'SELECT f.alias from forms f where f.`id` =' . $formId
        );
        $stmt->execute();

        $formRecord = $stmt->fetchAll();
        if (!$formRecord) {
            return array();
        }

        //try to fetch the form data
        $tableName = 'form_results_' . $formId . '_' . $formRecord[0]['alias'];

        $stmt       =  $connection->executeQuery(
            'select * from ' . $tableName . ' where submission_id = ' . $formSubmissionEntry['id']
        );
        $stmt->execute();

        $formData = $stmt->fetchAll();
        if (is_array($formData) && count($formData) > 0) {
            return $formData[0];
        }

        return array();
    }    

}
