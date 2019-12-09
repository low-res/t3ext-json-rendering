<?php
namespace Lowres\JsonRendering\ViewHelpers;

class JsonContentElementViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    private $result;

    /**
     * @param mixed $contentElement
     * @return string
     */
    public function render( $contentElement ) {

        $this->result = $this->filterOutGridelementsStuff( $contentElement );
        //var_dump( $contentElement['files'][0] );
        if(array_key_exists('files', $contentElement)) $this->processFiles( $contentElement['files'] );

        return json_encode( $this->result ).'__|__';
    }


    /**
     *
     * @param $contentElement
     * @return mixed
     */
    private function filterOutGridelementsStuff( $contentElement ) {
        if(array_key_exists('tx_gridelements_view_children', $contentElement)) {
            //die( var_dump($contentElement['tx_gridelements_view_children']) );
            //unset( $contentElement['tx_gridelements_view_children'] );
        }
        if(array_key_exists('tx_gridelements_view_columns', $contentElement)) {
            $c = $contentElement['tx_gridelements_view_columns'];
            $contentElement['tx_gridelements_view_columns'] = $this->processGEviewColumns($c);
        }

        $keys = array_keys($contentElement);
        $matchingKeys1 = preg_grep('/tx_gridelements_view_child_(.*)/', $keys);
        $matchingKeys2 = preg_grep('/tx_gridelements_view_column_(.*)/', $keys);
        $matchingKeys = array_merge($matchingKeys1,$matchingKeys2);
        foreach ($matchingKeys as $childKey) {
            $contentElement[$childKey] = $this->decodeJSON( $contentElement[$childKey] );
        }
        return $contentElement;
    }


    private function processGEviewColumns( $vcArray ) {
        $keys = array_keys( $vcArray );
        foreach ($keys as $tmpKey) {
            $tmpContent = $vcArray[$tmpKey];
            if(!empty($tmpContent)) {
                $vcArray[$tmpKey] = $this->decodeJSON($tmpContent);
            }
        }
        return $vcArray;
    }


    private function decodeJSON($jsonstr) {
        $jsonstr = str_replace('__|__','',$jsonstr);
        $jsonstr = trim($jsonstr);
        if(!empty($jsonstr)) $decoded = \GuzzleHttp\json_decode($jsonstr,true);
        else $decoded = [];
        return $decoded;
    }


    /**
     *
     * @param array $filereferences
     */
    private function processFiles( array $filereferences ) {
        $files = [];
        foreach ( $filereferences as $tmpFileRef ) {
            $files[] = $tmpFileRef->getProperties();
        }
        $this->result['files'] = $files;
    }
}