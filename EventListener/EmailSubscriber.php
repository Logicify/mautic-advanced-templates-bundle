<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;

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
    public function __construct(TemplateProcessor $templateProcessor)
    {
        $this->templateProcessor = $templateProcessor;
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

        $subject = $this->templateProcessor->processTemplate($subject,  $event);
        $event->setSubject($subject);

        $content = $this->templateProcessor->processTemplate($content,  $event);
        $event->setContent($content);


        if ( empty( trim($event->getPlainText()) ) ) {
            $event->setPlainText( (new PlainTextHelper($content))->getText() );
        }
    }
}
