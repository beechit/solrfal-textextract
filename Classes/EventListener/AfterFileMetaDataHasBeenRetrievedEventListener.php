<?php

namespace BeechIt\SolrfalTextextract\EventListener;

use ApacheSolrForTypo3\Solrfal\Event\Indexing\AfterFileMetaDataHasBeenRetrievedEvent;
use BeechIt\SolrfalTextextract\Aspects\SolrFalAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AfterFileMetaDataHasBeenRetrievedEventListener
{
    public function __invoke(AfterFileMetaDataHasBeenRetrievedEvent $event): void
    {
        $metaData = $event->getMetaData();
        $metaDataArrayObject = new \ArrayObject($metaData);
        $aspect = GeneralUtility::makeInstance(SolrFalAspect::class);
        $aspect->fileMetaDataRetrieved($event->getFileIndexQueueItem(), $metaDataArrayObject);
        $event->overrideMetaData($metaDataArrayObject->getArrayCopy());
    }
}
