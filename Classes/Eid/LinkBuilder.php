<?php
namespace PAGEmachine\Searchable\Eid;

use PAGEmachine\Searchable\Utility\TsfeUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * eID Link Builder
 */
class LinkBuilder
{
    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    public function __construct()
    {
        \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();
        TsfeUtility::createTSFE();
        $this->contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Processes a autosuggest request (< TYPO3 V8)
     *
     * @return void
     */
    public function processRequestLegacy()
    {
        $configuration = GeneralUtility::_POST('configuration');

        $links = [];

        if ($configuration) {
            foreach ($configuration as $key => $recordConfig) {
                $links[$key] = $this->getLink($recordConfig['conf']);
            }
        }

        header('Content-type: application/json');
        echo json_encode($links);
    }

    /**
     * Process request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return null|ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $configuration = $request->getParsedBody()['configuration'];

        $links = [];

        if ($configuration) {
            foreach ($configuration as $key => $recordConfig) {
                $links[$key] = $this->getLink($recordConfig['conf']);
            }
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response->getBody()->write(json_encode($links));
        return $response;
    }

    /**
     * Builds a single link
     *
     * @param  string $title
     * @param  array $configuration TypoLink configuration
     * @return string
     */
    public function getLink($configuration)
    {
        if ($configuration['parameter']) {
            $url = $this->contentObjectRenderer->typolink_URL($configuration);

            if ($url == '') {
                $url = '/';
            }
            return $url;
        }
    }

    /**
     * Initializes TSFE. This is necessary to have proper environment for typoLink.
     *
     * @return    void
     */
    protected function createTSFE()
    {
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], 1, '');
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();
        // Set linkVars, absRefPrefix, etc
        \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
    }
}
