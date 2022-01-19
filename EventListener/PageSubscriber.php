<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;

use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\LeadBundle\Tracker\ContactTracker;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use Monolog\Logger;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\FormSubmission;

class PageSubscriber implements EventSubscriberInterface
{
    /**
     * @var TemplateProcessor $templateProcessor ;
     */
    protected $templateProcessor;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var FormSubmission
     */
    protected $formSubmissionHelper;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * EmailSubscriber constructor.
     *
     * @param TemplateProcessor $templateProcessor
     * @param Logger $logger
     */
    public function __construct(TemplateProcessor $templateProcessor, Logger $logger, FormSubmission $formSubmissionHelper, ContactTracker $contactTracker)
    {
        $this->templateProcessor = $templateProcessor;
        $this->logger = $logger;
        $this->formSubmissionHelper = $formSubmissionHelper;
        $this->contactTracker       = $contactTracker;
    }    

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY   => ['onPageDisplay', 0],
        ];
    }

    /**
     * Try to retrieve the current form values of the active lead 
     * 
     * @param integer $leadId  
     * @param integer $emailId
     */
    private function getFormData($leadId)
    {
        return $this->formSubmissionHelper->getFormData($leadId);
    }    


    public function onPageDisplay(PageDisplayEvent $event)
    {
        $content = $event->getContent();
        if (empty($content)) {
            return;
        }
        
        $formData = [];
        $lead = $this->contactTracker->getContact();        
        if($lead)
        {
            $leadCredentials = $lead->getProfileFields();
            $formData = $this->getFormData($leadCredentials['id']);
        }

        //we want this variables in the twig engine
        $templateVars = array(
            'lead' => $lead,
            'form' => $formData
        );

        $content = $this->templateProcessor->processTemplate($content, $templateVars);        

        $event->setContent($content);
    }
}