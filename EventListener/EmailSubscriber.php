<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\CoreBundle\Exception as MauticException;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use Psr\Log\LoggerInterface;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber extends CommonSubscriber
{
    /**
     * @var TokenHelper $tokenHelper ;
     */
    protected $templateProcessor;


    /**
     * EmailSubscriber constructor.
     *
     * @param TokenHelper $tokenHelper
     */
    public function __construct(TemplateProcessor $templateProcessor, ModelFactory $modelFactory)
    {
        $this->templateProcessor = $templateProcessor;
        $this->modelFactory = $modelFactory;
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

        $lead = $event->getLead();
        $model = $this->modelFactory->getModel('lead');
        $leadmodel = $model->getEntity($lead['id']);
        $lead['tags'] = [];

        if ($leadmodel && count($leadmodel->getTags()) > 0) {
            foreach ($leadmodel->getTags() as $tag) {
                $lead['tags'][] = $tag->getTag();
            }
        }

        if ($event->getEmail()) {
            $subject = $event->getEmail()->getSubject();
            $content = $event->getEmail()->getCustomHtml();
        }else{
            $subject = $event->getSubject();
            $content = $event->getContent();
        }

        $subject = $this->templateProcessor->processTemplate($subject,  $lead);
        $event->setSubject($subject);

        $content = $this->templateProcessor->processTemplate($content,  $lead);
        $event->setContent($content);


        if ( empty( trim($event->getPlainText()) ) ) {
            $event->setPlainText( (new PlainTextHelper($content))->getText() );
        }
    }
}