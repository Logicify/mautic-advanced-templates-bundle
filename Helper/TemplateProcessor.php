<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

use MauticPlugin\MauticAdvancedTemplatesBundle\Feed\FeedFactory;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;
use Psr\Log\LoggerInterface;

use Twig\Environment;

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
        $this->twigEnv = new \Twig\Environment(new \Twig\Loader\ChainLoader([
            $twigDynamicContentLoader, new \Twig\Loader\ArrayLoader([])
        ]));
        $this->configureTwig($this->twigEnv);
        $this->feedFactory = $feedFactory;
    }


    /**
     * @param string $content
     * @param array $lead
     * @return string
     * @throws \Throwable
     */
    public function processTemplate($content, $lead)
    {
        $this->logger->debug('TemplateProcessor: Processing template');
        $content = preg_replace_callback_array([
            TemplateProcessor::$matchTwigBlockRegex => $this->processTwigBlock($lead)
        ], $content);
        $this->logger->debug('TemplateProcessor: Template processed');
        return $content;
    }

    protected function configureTwig(\Twig\Environment $twig)
    {
        // You might want to register some custom TWIG tags or functions here

        // TWIG filter json_decode
        $twig->addFilter(new \Twig\TwigFilter('json_decode', function ($string) {
            return json_decode($string, true);
        }));

        $twig->addFilter(new \Twig\TwigFilter('rss', function () {
            return $this->feedFactory->getItems($this->lead['id'], func_get_args());
        }));
    }

    private function processTwigBlock($templateVars)
    {
        $this->lead = $templateVars['lead'];
        return function ($matches) use ($templateVars) {
            $templateSource = $matches[1];
            $this->logger->debug('BLOCK SOURCE: ' . var_export($templateSource, true));
            $template = $this->twigEnv->createTemplate($templateSource);
            $renderedTemplate = $template->render($templateVars);
            $this->logger->debug('RENDERED BLOCK: ' . var_export($renderedTemplate, true));
            return $renderedTemplate;
        };
    }
}
