<?php

namespace Lowres\JsonRendering\Utils;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class DataProcessingUtil
{
    const TOKEN = '__|__';

    private $contentResult = null;
    private $backendConfigurationArr;


    public function __construct ()
    {
        $this->backendConfigurationArr  = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('t3ext_json_rendering');
        $this->contentObject            = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->contentObject->start([], '');
    }


    /**
     * @param array $contentElements
     * @return string
     */
    public function processContentElements ( array $contentElements )
    {
        $this->contentResult = array_map( function( $tmpCe ){
            return $this->processTypolinks( $tmpCe );
        }, $contentElements);
        $this->processGridelementsContainers( );
        $this->processFiles();
        $this->contentResult = $this->stripUnwantedKeysFromArray($this->contentResult, 'tt_content');
        return json_encode( $this->contentResult ).self::TOKEN;
    }


    /**
     * @param $pageData
     */
    public function processPage ( array $pageData )
    {
        // first we need to split the string of content elements back to single ces
        $rawContentString       = $pageData['contentElements'];
        $decodedContentElements = $this->decodeContentElementsFromJsonString( $rawContentString );

        // remove unwanted fields from page
        $page = $this->stripUnwantedKeysFromArray($pageData['data'], 'page');
        $result['page']             = $page;
        $result['contentElements']  = $decodedContentElements;
        $result['structure']        = \GuzzleHttp\json_decode( $pageData['structure'] );

        return json_encode($result, JSON_PRETTY_PRINT);
    }


    private function stripUnwantedKeysFromArray( array $inputArr,  string $excludeFieldsConfig ) {
        $remove = explode(',',$this->backendConfigurationArr['excludefields'][$excludeFieldsConfig]);
        $remove = array_map( function ($item) {
            return trim($item);
        }, $remove);

        foreach ($remove as $removeField) {
            $path = explode(".", $removeField);
            // support different depth of nesting.
            // no idea yet for a smarter solution yet :(
            switch( count($path) ) {
                case 2:
                    unset($inputArr[$path[0]][$path[1]]);
                    break;
                case 3:
                    unset($inputArr[$path[0]][$path[1]][$path[2]]);
                    break;
                case 4:
                    unset($inputArr[$path[0]][$path[1]][$path[2]][$path[3]]);
                    break;
                case 5:
                    unset($inputArr[$path[0]][$path[1]][$path[2]][$path[3]][$path[4]]);
                    break;
                default:
                    unset($inputArr[$removeField]);
            }
        }
        return $inputArr;
    }


    /**
     * @param $rawContentString
     * @return array
     */
    private function decodeContentElementsFromJsonString( $rawContentString ) {

        $contentelements = explode(self::TOKEN, $rawContentString);
        // the last element is always empty, so we can remove it
        array_pop( $contentelements );

        // json_decode the JSON-strings of every single ce
        $decodedContentElements = array_map(function( $tmpCe ) {
            $res = null;
            try {
                $res = \GuzzleHttp\json_decode( $tmpCe );
            } catch( InvalidArgumentException $e){
                $res = ['error'=>'can not json decode '.$tmpCe ];
            }
            return $res;
        }, $contentelements);

        return $decodedContentElements;
    }


    /**
     *
     * @param $contentElements
     * @return mixed
     */
    private function processGridelementsContainers( ) {
        $contentElements = $this->contentResult;
        if(array_key_exists('tx_gridelements_view_columns', $contentElements)) {
            $c = $contentElements['tx_gridelements_view_columns'];
            $contentElements['tx_gridelements_view_columns'] = $this->processGridelementsViewColumns($c);
        }

        $keys           = array_keys($contentElements);
        $matchingKeys1  = preg_grep('/tx_gridelements_view_child_(.*)/', $keys);
        $matchingKeys2  = preg_grep('/tx_gridelements_view_column_(.*)/', $keys);
        $matchingKeys   = array_merge($matchingKeys1,$matchingKeys2);
        foreach ($matchingKeys as $childKey) {
            $contentElements[$childKey] = $this->decodeJSON( $contentElements[$childKey] );
        }
        $this->contentResult = $contentElements;
    }


    private function processGridelementsViewColumns( $vcArray ) {
        $keys = array_keys( $vcArray );
        foreach ($keys as $tmpKey) {
            $tmpContent = $vcArray[$tmpKey];
            if(!empty($tmpContent)) {
                $vcArray[$tmpKey] = $this->decodeJSON($tmpContent);
            }
        }
        return $vcArray;
    }


    /**
     *
     * @param array $filereferences
     */
    private function processFiles() {
        $contentElements = $this->contentResult;

        // files array
        unset( $this->contentResult['files'] );
//        if(array_key_exists('files', $contentElements)) {
//            $filereferences = $contentElements['files'];
//            $files = [];
//            foreach ( $filereferences as $tmpFileRef ) {
//                $files[] = $this->processFileReference($tmpFileRef);
//            }
//            $this->contentResult['files'] = $files;
//        }

        // gallery
        if(array_key_exists('gallery', $contentElements)) {
            $processedGallery = [];
            $processedGallery['settings'] = [
                'position'  => $contentElements['gallery']['position'],
                'width'     => $contentElements['gallery']['width'],
                'border'    => $contentElements['gallery']['border']
            ];
            $processedGallery['media'] = [];
            foreach ( $contentElements['gallery']['rows'] as $rows ) {
                foreach ( $rows as $row ) {
                    foreach ( $row as $col ) {
                        $item                           = [];
                        $item['file']                   = $this->processFileReference( $col['media'] );
                        $item['dimensions']             = $col['dimensions'];
                        $processedGallery['media'][]    = $item;
                    }
                }
            }
            $this->contentResult['gallery'] = $processedGallery;
        }
    }


    private function processFileReference( $fileRef ) {
        $singleFile = $fileRef->getProperties();
        $singleFile = $this->stripUnwantedKeysFromArray($singleFile,'file');
        $singleFile = $this->processTypolinks($singleFile);
        return $singleFile;
    }


    /**
     * takes JSON string of CE and converts it back to PHP array
     * @param $jsonstr
     * @return array|mixed
     */
    private function decodeJSON( $jsonstr ) {
        $jsonstr = str_replace(self::TOKEN,'',$jsonstr);
        $jsonstr = trim($jsonstr);
        if(!empty($jsonstr)) {
            $decoded = \GuzzleHttp\json_decode($jsonstr,true);
            $decoded = $this->processTypolinks( $decoded );
        }
        else $decoded = [];
        return $decoded;
    }


    private function processTypolinks( &$contentElement ) {
        foreach ($contentElement as $key => $value) {
            if(is_string($value)) {
                $transformed = preg_replace_callback("/t3:\/\/[^\"\s]+/", function($match) {
                    return $this->resolveT3Urn($match[0]);
                }, $value);
                $contentElement[$key] = $transformed;
            }
        }
        return $contentElement;
    }


    private function resolveT3Urn( $urn ) {
        return $this->contentObject->stdWrap(
            "",
            [
                'typolink.' => [
                    'parameter' => $urn,
                    'forceAbsoluteUrl' => true,
                    'returnLast' => 'url'
                ]
            ]
        );
    }
}