<?php

namespace Lowres\JsonRendering\Utils;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class DataProcessingUtil
{
    const TOKEN='__|__';

    private $contentResult = null;
    private $backendConfigurationArr;

    public function __construct ()
    {
        $this->backendConfigurationArr = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('t3ext_json_rendering');
    }


    /**
     * @param array $contentElements
     * @return string
     */
    public function processContentElements ( array $contentElements )
    {
        $this->processGridelementsContainers( $contentElements );
        $this->processFiles( $contentElements );
        $this->contentResult = $this->stripUnwantedKeysFromArray($this->contentResult, 'tt_content');
        return json_encode( $this->contentResult ).self::TOKEN;
    }


    /**
     * @param $pageData
     */
    public function processPage ( array $pageData )
    {
        // first we need to split the string of content elements back to single ces
        $rawContentString = $pageData['contentElements'];
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
    private function processGridelementsContainers( $contentElements ) {
        if(array_key_exists('tx_gridelements_view_children', $contentElements)) {
            //die( var_dump($contentElement['tx_gridelements_view_children']) );
            //unset( $contentElement['tx_gridelements_view_children'] );
        }
        if(array_key_exists('tx_gridelements_view_columns', $contentElements)) {
            $c = $contentElements['tx_gridelements_view_columns'];
            $contentElements['tx_gridelements_view_columns'] = self::processGridelementsViewColumns($c);
        }

        $keys = array_keys($contentElements);
        $matchingKeys1 = preg_grep('/tx_gridelements_view_child_(.*)/', $keys);
        $matchingKeys2 = preg_grep('/tx_gridelements_view_column_(.*)/', $keys);
        $matchingKeys = array_merge($matchingKeys1,$matchingKeys2);
        foreach ($matchingKeys as $childKey) {
            $contentElements[$childKey] = self::decodeJSON( $contentElements[$childKey] );
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
    private function processFiles( array $contentElements ) {
        if(array_key_exists('files', $contentElements)) {
            $filereferences = $contentElements['files'];
            $files = [];
            foreach ( $filereferences as $tmpFileRef ) {
                $singleFile = $tmpFileRef->getProperties();
                $files[] = $this->stripUnwantedKeysFromArray($singleFile,'file');
            }
            $this->contentResult['files'] = $files;
        }
    }


    /**
     * takes JSON string of CE and converts it back to PHP array
     * @param $jsonstr
     * @return array|mixed
     */
    private function decodeJSON( $jsonstr ) {
        $jsonstr = str_replace(self::TOKEN,'',$jsonstr);
        $jsonstr = trim($jsonstr);
        if(!empty($jsonstr)) $decoded = \GuzzleHttp\json_decode($jsonstr,true);
        else $decoded = [];
        return $decoded;
    }


}