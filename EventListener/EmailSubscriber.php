<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;
use Mautic\CampaignBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\CoreBundle\Exception as MauticException;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

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
     * EmailSubscriber constructor.
     *
     * @param TokenHelper $tokenHelper
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

    private function getProperties(Events\EmailSendEvent $event) {
        $tokens = [];

        if (!$event->getEmail()) {
            return [
                'subject' => $event->getSubject(),
                'content' => $event->getContent(),
                'tokens' => $tokens,
            ];
        }

        $email = $event->getEmail();

        $subject = $email->getSubject();
        $content = $email->getCustomHtml();
        $dynamic = $email->getDynamicContent();

        foreach ($dynamic as $prop) {
            $tokens[$prop['tokenName']] = $prop['content'];
        }

        return [
            'subject' => $subject,
            'content' => $content,
            'tokens' => $tokens,
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
        if ($event->isDynamicContentParsing()) {
            return;
        }

        $props = $this->getProperties($event);

        $subject = $this->templateProcessor->processTemplate($props['subject'],  $event->getLead());
        $event->setSubject($subject);

        $content = $this->templateProcessor->processTemplate($props['content'],  $event->getLead(), $props['tokens']);
        $content = $this->templateProcessor->addTrackingPixel($content);
        $event->setContent($content);

        if ( empty( trim($event->getPlainText()) ) ) {
            $event->setPlainText( (new PlainTextHelper($content))->getText() );
        }
    }
}
