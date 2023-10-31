<?php

namespace PAGEmachine\Searchable\Utility;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Helper class to measure time and show an error if necessary
 */
class TimeMeasurementUtility
{
    /**
     * @var float
     */
    protected $start;

    /**
     * @var float
     */
    protected $totalTime;

    /**
     * @param int $timeout
     */
    protected $timeout;

    public function __construct($timeout = null)
    {
        $this->timeout = $timeout;
    }

    public function start()
    {
        $this->start = microtime(true);

        return $this;
    }

    public function stop()
    {
        $this->totalTime = microtime(true) - $this->start;

        if ($this->timeout && $this->totalTime > $this->timeout) {
            $this->showError($this->totalTime);
        }
    }

    protected function showError($time)
    {
        $text = $GLOBALS['LANG']->sL('LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:searchable_module_timeout');

        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $text . ' (' . number_format($time, 2) . 's)',
            '',
            FlashMessage::WARNING,
            true
        );

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }
}
