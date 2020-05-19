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
    public function __construct(TemplateProcessor $templateProcessor, Logger $logger)
    {
        $this->templateProcessor = $templateProcessor;
        $this->logger = $logger;
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

        if ($event->getEmail()) {
            $subject = $event->getEmail()->getSubject();
            $content = $event->getEmail()->getCustomHtml();
        }else{
            $subject = $event->getSubject();
            $content = $event->getContent();
        }

        $subject = $this->templateProcessor->processTemplate($subject,  $event->getLead());
        $event->setSubject($subject);

        $content = $this->templateProcessor->processTemplate($content,  $event->getLead());
        $event->setContent($content);


        if ( empty( trim($event->getPlainText()) ) ) {
            $event->setPlainText( (new PlainTextHelper($content))->getText() );
        }
    }
}