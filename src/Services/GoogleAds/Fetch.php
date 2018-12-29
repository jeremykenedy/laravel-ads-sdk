<?php namespace LaravelAds\Services\GoogleAds;

use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;

class Fetch
{
    /**
     * $service
     *
     */
    protected $service = null;

    /**
     * __construct()
     *
     *
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * getCampaigns()
     *
     * @reference
     * https://github.com/googleads/googleads-php-lib/blob/master/src/Google/AdsApi/AdWords/v201809/cm/Campaign.php
     * https://developers.google.com/adwords/api/docs/reference/v201809/CampaignService.Campaign
     *
     * @return object Collection
     */
    public function getCampaigns()
    {
        $selector = new Selector();
        $selector->setFields([
            'Id',
            'Name',
            'Amount',
            'CampaignStatus',
            'AdvertisingChannelType'
        ]);

        $page = $this->service->service(CampaignService::class)->get($selector);
        $items = $page->getEntries();

        $r = [];
        foreach ($items as $item)
        {
            $budget = $item->getBudget()->getAmount()->getMicroAmount() ?? 0;

            $r[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'status' => $item->getStatus(),
                'channel' => $item->getAdvertisingChannelType(),
                'budget' => ($budget) ? round( intval($budget) / 1000000,2) : 0
            ];
        }

        return collect($r);
    }



    /**
     * getAdGroups()
     *
     * @reference
     * https://github.com/googleads/googleads-php-lib/blob/master/src/Google/AdsApi/AdWords/v201809/cm/AdGroup.php
     * https://developers.google.com/adwords/api/docs/reference/v201809/AdGroupService.AdGroup
     *
     * @return object Collection
     */
    public function getAdGroups()
    {
        $selector = new Selector();
        $selector->setFields([
            'Id',
            'Name',
            'CampaignName',
            'CampaignId',
            'Status',
            'BiddingStrategyType',
            'CpcBid',
            'CpmBid',
            'TargetCpaBid'
        ]);

        $page  = $this->service->service(AdGroupService::class)->get($selector);
        $items = $page->getEntries();

        $r = [];
        foreach ($items as $item)
        {
            $bidType = $item->getBiddingStrategyConfiguration()->getBiddingStrategyType() ?? '';
            $bids = $item->getBiddingStrategyConfiguration()->getBids() ?? '';

            $realBid = 0;

            foreach($bids as $bid)
            {
                if ($bid->getBidsType() == 'CpcBid' && $bidType == "MANUAL_CPC")
                {
                    $realBid = $bid->getbid()->getMicroAmount();
                    break;
                }

                if ($bid->getBidsType() == 'CpmBid' && $bidType == "MANUAL_CPM")
                {
                    $realBid = $bid->getbid()->getMicroAmount();
                    break;
                }

                if ($bid->getBidsType() == 'CpaBid' && $bidType == "TARGET_CPA")
                {
                    $realBid = $bid->getbid()->getMicroAmount();
                    break;
                }
            }

            $r[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'status' => $item->getStatus(),
                'campaign_id' => $item->getCampaignId(),
                'campaign_name' => $item->getCampaignName(),
                'bid_type' => $bidType,
                'bid' => ($realBid) ? round( intval($realBid) / 1000000,2) : 0
            ];
        }

        return collect($r);
    }

}