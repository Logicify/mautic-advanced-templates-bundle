<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;
use Mautic\CampaignBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\SmsBundle\SmsEvents;
use Mautic\SmsBundle\Event as Events;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\CoreBundle\Exception as MauticException;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

/**
 * Class EmailSubscriber.
 */
class SmsSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var TokenHelper $tokenHelper ;
     */
    protected $templateProcessor;

    /**
     * @var LoggerInterface $logger ;
     */
    protected $logger;

    /**
     * EmailSubscriber constructor.
     *
     * @param TokenHelper $tokenHelper
     */
    public function __construct(TemplateProcessor $templateProcessor, LeadModel $leadModel, Logger $logger)
    {
        $this->templateProcessor = $templateProcessor;
        $this->leadModel = $leadModel;
        $this->logger = $logger;
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SmsEvents::SMS_ON_SEND => ['onSmsGenerate', 300],
            // I dont know how to do this without editing core. 
            // since there does not seem to be a simular way to call it yet.            
            // SmsEvents::SMS_ON_DISPLAY => ['onSmsGenerate', 0],
        ];
    }

    /**
     * Search and replace tokens with content
     *
     * @param Events\SmsSendEvent $event
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Syntax
     */
    public function onSmsGenerate(Events\SmsSendEvent $event)
    {
        $this->logger->info('onSmsGenerate MauticAdvancedTemplatesBundle\SmsSubscriber');

        $content = $event->getContent();
        
        $lead = $event->getLead();
        $leadmodel = $this->leadModel->getEntity($lead['id']);
        $lead['tags'] = [];
        if ($leadmodel && count($leadmodel->getTags()) > 0) {
            foreach ($leadmodel->getTags() as $tag) {
                $lead['tags'][] = $tag->getTag();
            }
        } 

        $content = $this->templateProcessor->processTemplate($content, $lead);
        $event->setContent($content);
    }
} 