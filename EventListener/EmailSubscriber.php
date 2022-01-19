<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber implements EventSubscriberInterface
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
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailGenerate', 300],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
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

    /**
     * Search and replace tokens with content
     *
     * @param Events\EmailSendEvent $event
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Syntax
     */
    public function onEmailGenerate(Events\EmailSendEvent $event)
    {
        $this->logger->info('onEmailGenerate MauticAdvancedTemplatesBundle\EmailSubscriber');

        if ($event->getEmail()) {
            $subject = $event->getEmail()->getSubject();
            $content = $event->getEmail()->getCustomHtml();
        }else{
            $subject = $event->getSubject();
            $content = $event->getContent();
        }

        $lead = $event->getLead();
        $leadCredentials = $lead->getProfileFields();
        $formData = $this->getFormData($leadCredentials['id']);

        //we want this variables in the twig engine
        $templateVars = array(
            'lead' => $lead,
            'form' => $formData
        );        
       
        $subject = $this->templateProcessor->processTemplate($subject,  $templateVars);
        $event->setSubject($subject);

        $content = $this->templateProcessor->processTemplate($content,  $templateVars);
        $event->setContent($content, false);

        // Always generate Plaintext Version 
        $event->setPlainText( (new PlainTextHelper($content))->getText() );
    }
}
