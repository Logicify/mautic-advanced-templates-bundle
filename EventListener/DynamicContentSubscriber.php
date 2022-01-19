<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\FormSubmission;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DynamicContentSubscriber
 */
class DynamicContentSubscriber implements EventSubscriberInterface
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
     * EmailSubscriber constructor.
     *
     * @param TemplateProcessor $templateProcessor
     * @param Logger $logger
     */
    public function __construct(TemplateProcessor $templateProcessor, Logger $logger, FormSubmission $formSubmissionHelper)
    {
        $this->templateProcessor = $templateProcessor;
        $this->logger = $logger;
        $this->formSubmissionHelper = $formSubmissionHelper;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            DynamicContentEvents::TOKEN_REPLACEMENT    => array('onTokenReplacement', 0)
        );
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

    /**
     * @param TokenReplacementEvent $event
     */
    public function onTokenReplacement(TokenReplacementEvent $event)
    {

        /** @var Lead $lead */
        $content      = $event->getContent();

        if (!$content) {
            return;
        }

        $lead         = $event->getLead();
        $leadCredentials = $lead->getProfileFields();
        $formData = $this->getFormData($leadCredentials['id']);

        //we want this variables in the twig engine
        $templateVars = array(
            'lead' => $leadCredentials,
            'form' => $formData
        );

        $content = $this->templateProcessor->processTemplate($content, $templateVars);        

        $event->setContent($content);
    }
}
