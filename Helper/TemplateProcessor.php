<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

use MauticPlugin\MauticAdvancedTemplatesBundle\Feed\FeedFactory;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;
use Psr\Log\LoggerInterface;
use Twig\Environment as Twig_Environment;
use Twig\Loader\ChainLoader as Twig_Loader_Chain;
use Twig\Loader\ArrayLoader as Twig_Loader_Array;
use Twig\TwigFilter as Twig_SimpleFilter;
use Twig\Error\Error as TwigError;

class TemplateProcessor
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Twig_Environment
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
        $this->twigEnv = new Twig_Environment(new Twig_Loader_Chain([
            $twigDynamicContentLoader, new Twig_Loader_Array([])
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
    public function processTemplate($content, $lead, $tokens = null)
    {
        $this->logger->debug('TemplateProcessor: Processing template');
        // This was causing huge memory usage. Uncomment to debug.
        // $this->logger->debug('LEAD: ' . var_export($lead, true));
        $content = preg_replace_callback_array([
            TemplateProcessor::$matchTwigBlockRegex => $this->processTwigBlock($lead, $tokens)
        ], $content);
        $this->logger->debug('TemplateProcessor: Template processed');
        return $content;
    }

    protected function configureTwig(Twig_Environment $twig)
    {
        // You might want to register some custom TWIG tags or functions here

        // TWIG filter json_decode
        $twig->addFilter(new Twig_SimpleFilter('json_decode', function ($string) {
            return json_decode($string, true);
        }));

        $twig->addFilter(new Twig_SimpleFilter('json_encode', function ($obj) {
            return json_encode($obj);
        }));

        $twig->addFilter(new Twig_SimpleFilter('rss', function () {
            return $this->feedFactory->getItems($this->lead['id'], func_get_args());
        }));
    }

    private function processTwigBlock($lead, $tokens = null)
    {
        $this->lead = $lead;
        return function ($matches) use ($lead, $tokens) {
            $templateSource = $matches[1];
            // Uncomment to debug. This causes high memory usage with var_export.
            // $this->logger->debug('BLOCK SOURCE: ' . var_export($templateSource, true));
            try{
                $template = $this->twigEnv->createTemplate($templateSource);
            }catch(\Exception $error){
                $this->logger->error("Invalid syntax: ".$error->getMessage());
                return '';
            }

            try{
                $renderedTemplate = $template->render([
                    'lead' => $lead,
                    'tokens' => $tokens
                ]);
            }catch(\Exception $error){
                $this->logger->error("Error render template: ".$error->getMessage());
                return '';
            }            
            // Uncomment to debug. This causes high memory usage with var_export.
            // $this->logger->debug('RENDERED BLOCK: ' . var_export($renderedTemplate, true));
            return $renderedTemplate;
        };
    }
    
    public function addTrackingPixel($content)
    {
        // Append tracking pixel
        $trackingImg = '<img height="1" width="1" src="{tracking_pixel}" alt="" />';
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $trackingImg.'</body>', $content);
        } else {
            $content .= $trackingImg;
        }

        return $content;
    }
}
