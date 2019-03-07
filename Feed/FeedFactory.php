<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Feed;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class FeedFactory
{

    /** @var  string */
    private $feed;

    /** @var  string|null */
    private $type;

    /**
     * @var FeedProcessor
     */
    private $feedProcessor;

    /**
     * FeedFactory constructor.
     *
     * @param FeedProcessor $feedProcessor
     */
    public function __construct(FeedProcessor $feedProcessor)
    {
        $this->feedProcessor = $feedProcessor;
    }

    /**
     * @param int $leadId
     * @param array $args
     *
     * @return \SimpleXMLElement|void
     */
    public function getItems($leadId, array $args)
    {
        $this->feed = new Feed($args[0]);
        $this->type = isset($args[1]) ? $args[1] : null;

        switch ($this->type) {
            case 'segments':
                return $this->feedProcessor->getSegmentsRelatedFeedItems($leadId, $this->feed);
                break;
            default:
                return $this->feed->getItems();
                break;
        }


    }
}