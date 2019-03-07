<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Feed;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class Feed
{
    /** @var  string */
    private $feed;

    /** @var \SimpleXMLElement  */
    private $rss;

    public function __construct($feed)
   {
       $this->feed = $feed;
       $this->rss = simplexml_load_file($feed);
   }

    /**
     * @return \SimpleXMLElement
     */
    public function getItems(): \SimpleXMLElement
    {
        return $this->rss->channel->item;
    }
}