<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Feed;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class FeedProcessor
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * RSSProcessor constructor.
     *
     * @param LeadModel $leadModel
     * @param Feed      $feed
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;

    }

    /**
     * @param int $leadId
     * @param Feed $feed
     *
     * @return \SimpleXMLElement
     */
    public function getSegmentsRelatedFeedItems($leadId, Feed $feed)
    {
        if ($this->lead = $this->leadModel->getEntity($leadId)) {
            $contactPrefsSegmentsAliases =  array_column($this->leadModel->getLists($this->lead, true, true, true), 'alias');
            $items = [];
            foreach ($feed->getItems()  as $item) {
                foreach ($item->category as $category) {
                    if (in_array($category, $contactPrefsSegmentsAliases)) {
                        $items[] = $item;
                        continue 2;
                    }
                }
            }
            return $items;
        }
    }

}