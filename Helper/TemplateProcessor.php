<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticAdvancedTemplatesBundle\Feed\FeedFactory;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;
use Psr\Log\LoggerInterface;

class TemplateProcessor
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Twig_Environment
     */
    private $twigEnv;
    private $twigDynamicContentLoader;

    private static $matchTwigBlockRegex = '/{%\s?TWIG_BLOCK\s?%}(.*?){%\s?END_TWIG_BLOCK\s?%}/ism';

    /** @var  array */
    private $lead;

    /**
     * @var FeedFactory
     */
    private $feedFactory;

    /**
     * TemplateProcessor constructor.
     *
     * @param LoggerInterface            $logger
     * @param Twig_Loader_DynamicContent $twigDynamicContentLoader
     * @param FeedFactory                $feedFactory
     */
    public function __construct(LoggerInterface $logger, Twig_Loader_DynamicContent $twigDynamicContentLoader, FeedFactory $feedFactory)
    {
        $this->logger = $logger;
        $this->twigDynamicContentLoader = $twigDynamicContentLoader;
        $logger->debug('TemplateProcessor: created $twigDynamicContentLoader');
        $this->twigEnv = new \Twig_Environment(new \Twig_Loader_Chain([
            $twigDynamicContentLoader, new \Twig_Loader_Array([])
        ]));
        $this->configureTwig($this->twigEnv);
        $this->feedFactory = $feedFactory;
    }


    /**
     * @param string                               $content
     * @param CommonEvent|EmailSendEvent $event If you send EmailSendEvent, it will also include
     *                                          the tokens as variables in TWIG
     *
     * @return string
     */
    public function processTemplate($content, CommonEvent $event)
    {
        $this->logger->debug('TemplateProcessor: Processing template');
        if ($event->getLead()) {
            $this->logger->debug('LEAD: ' . var_export($event->getLead(), true));
        }

        if ($event->getTokens()) {
            $this->logger->debug('TOKENS: ' . var_export($event->getTokens(), true));
        }

        $content = preg_replace_callback_array([
            TemplateProcessor::$matchTwigBlockRegex => $this->processTwigBlock($event)
        ], $content);
        $this->logger->debug('TemplateProcessor: Template processed');
        return $content;
    }

    protected function configureTwig(\Twig_Environment $twig)
    {
        // You might want to register some custom TWIG tags or functions here

        // TWIG filter json_decode
        $twig->addFilter(new \Twig_SimpleFilter('json_decode', function ($string) {
            return json_decode($string, true);
        }));

        $twig->addFilter(new \Twig_SimpleFilter('rss', function () {
            return $this->feedFactory->getItems($this->lead['id'], func_get_args());
        }));
    }

    /**
     * @param CommonEvent|EmailSendEvent $event If you send EmailSendEvent, it will also include
     *                                          the tokens as variables in TWIG
     *
     * @return \Closure
     */
    private function processTwigBlock(CommonEvent $event)
    {
        $lead = $event->getLead();
        $this->lead = $lead;
        return function ($matches) use ($event) {
            $templateSource = $matches[1];
            $this->logger->debug('BLOCK SOURCE: ' . var_export($templateSource, true));
            $template = $this->twigEnv->createTemplate($templateSource);
            $twigVariables = ['lead' => $event->getLead()];
            if ($event instanceof EmailSendEvent) {
                $eventTokens = $event->getTokens();
                foreach ($eventTokens as $key => $token) {
                    if (preg_match('/^{.*?}$/', $key)) {
                        $key = str_replace(['{', '}'], '', $key);
                        $eventTokens[$key] = $token;
                    }
                }

                $twigVariables = array_merge($twigVariables, $eventTokens);
            }
            $renderedTemplate = $template->render($twigVariables);
            $this->logger->debug('RENDERED BLOCK: ' . var_export($renderedTemplate, true));

            return $renderedTemplate;
        };
    }
}
