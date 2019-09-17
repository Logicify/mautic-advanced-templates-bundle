<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Psr\Log\LoggerInterface;
use Twig_Error_Loader;
use Twig_Source;

class Twig_Loader_DynamicContent implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface, \Twig_SourceContextLoaderInterface
{
    private static $NAME_PREFIX = 'dc:';

    /**
     * @var ModelFactory
     */
    private $modelFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Twig_Loader_DynamicContent constructor.
     * @param LoggerInterface $logger
     * @param ModelFactory $modelFactory
     */
    public function __construct(LoggerInterface $logger, ModelFactory $modelFactory)
    {
        $this->modelFactory = $modelFactory;
        $this->logger = $logger;
    }


    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Twig_Error_Loader When $name is not found
     *
     * @deprecated since 1.27 (to be removed in 2.0), implement Twig_SourceContextLoaderInterface
     */
    public function getSource($name)
    {
        @trigger_error(sprintf('Calling "getSource" on "%s" is deprecated since 1.27. Use getSourceContext() instead.', get_class($this)), E_USER_DEPRECATED);
        return $this->getSourceContext($name)->getCode();
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     *
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     *
     */
    public function isFresh($name, $time)
    {
        // TODO: Implement isFresh() method.
        $this->logger->debug('Twig_Loader_DynamicContent: Is Fresh: ' . $time . ', ' . $name);
        return false;
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @return Twig_Source
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function getSourceContext($name)
    {
        $dynamicContent = $this->findTemplate($this->aliasForTemplateName($name));
        if ($dynamicContent == null) {
            throw new Twig_Error_Loader('Template ' . $name . ' does not exist');
        }
        return new Twig_Source($dynamicContent->getContent(), $name);

    }

    private function aliasForTemplateName($name)
    {
        return str_replace(Twig_Loader_DynamicContent::$NAME_PREFIX, '', $name);
    }

    /**
     * @param $resourceAlias
     * @return null|DynamicContent
     */
    private function findTemplate($resourceAlias)
    {
        $model = $this->modelFactory->getModel('dynamicContent');
        $result = $model->getEntities(
            [
                'filter' => [
                    'where' => [
                        [
                            'col' => 'e.name',
                            'expr' => 'eq',
                            'val' => $resourceAlias,
                        ],
                        [
                            'col'  => 'e.isPublished',
                            'expr' => 'eq',
                            'val'  => 1,
                        ]
                    ]
                ],
                'ignore_paginator' => true,
            ]);

        if (count($result) === 0) {
            return null;
        }

        /**** The result array key is the dynamic content ID - So use array_keys and get the first (and only) found key  ****/
        $keys = array_keys($result);     
        
        return $result[$keys[0]]; 
    }
    
    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists($name)
    {
        return $this->supports($name) && $this->findTemplate($this->aliasForTemplateName($name)) !== null;
    }

    /**
     * @param $name
     * @return bool
     */
    public function supports($name)
    {
        return strpos($name, Twig_Loader_DynamicContent::$NAME_PREFIX) === 0;
    }
}