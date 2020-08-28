<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

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
     * @param string $content
     * @param array $lead
     * @return string
     * @throws \Throwable
     */
    public function processTemplate($content, $lead)
    {
        $this->logger->debug('TemplateProcessor: Processing template');
        // this was causing huge memory useage. 
        //         [2020-08-28 20:18:03] mautic.WARNING: PHP Warning - var_export does not handle circular references - in file /var/www/html/plugins/MauticAdvancedTemplatesBundle/Helper/TemplateProcessor.php - at line 62 {"content":"test message","lead":"[object] (Mautic\\LeadBundle\\Entity\\Lead: Mautic\\LeadBundle\\Entity\\Lead with ID #1)"} []
        // [2020-08-28 20:18:03] mautic.ERROR: PHP Error: Allowed memory size of 2147483648 bytes exhausted (tried to allocate 1071648768 bytes) - in file /var/www/html/plugins/MauticAdvancedTemplatesBundle/Helper/TemplateProcessor.php - at line 62 [] []

        // $this->logger->debug('LEAD: ' . var_export($lead, true));
        $content = preg_replace_callback_array([
            TemplateProcessor::$matchTwigBlockRegex => $this->processTwigBlock($lead)
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

    private function processTwigBlock($lead)
    {
        $this->lead = $lead;
        return function ($matches) use ($lead) {
            $templateSource = $matches[1];
            $this->logger->debug('BLOCK SOURCE: ' . var_export($templateSource, true));
            $template = $this->twigEnv->createTemplate($templateSource);
            $renderedTemplate = $template->render([
                'lead' => $lead
            ]);
            $this->logger->debug('RENDERED BLOCK: ' . var_export($renderedTemplate, true));
            return $renderedTemplate;
        };
    }
}