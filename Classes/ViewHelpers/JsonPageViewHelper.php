<?php
namespace Lowres\JsonRendering\ViewHelpers;

use InvalidArgumentException;

class JsonPageViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * @param mixed $pageData
     * @return string
     */
    public function render($pageData) {

        // first we need to split the string of content elements back to single ces
        $rawContentString = $pageData['contentElements'];

        $contentelements = explode('__|__', $rawContentString);

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


        $result['contentElements'] = $decodedContentElements;
        $result['page'] = $pageData['data'];
        $result['structure'] = \GuzzleHttp\json_decode( $pageData['structure'] );


        return json_encode($result, JSON_PRETTY_PRINT);
    }

}