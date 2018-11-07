<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

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

    /**
     * TemplateProcessor constructor.
     * @param LoggerInterface $logger
     * @param Twig_Loader_DynamicContent $twigDynamicContentLoader
     */
    public function __construct(LoggerInterface $logger, Twig_Loader_DynamicContent $twigDynamicContentLoader)
    {
        $this->logger = $logger;
        $this->twigDynamicContentLoader = $twigDynamicContentLoader;
        $logger->debug('TemplateProcessor: created $twigDynamicContentLoader');
        $this->twigEnv = new \Twig_Environment(new \Twig_Loader_Chain([
            $twigDynamicContentLoader, new \Twig_Loader_Array([])
        ]));
        $this->configureTwig($this->twigEnv);
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
        $this->logger->debug('LEAD: ' . var_export($lead, true));
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
    }

    private function processTwigBlock($lead)
    {
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