<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google\Parser\Evaluated\Rule\Adwords;

use Serps\Core\Serp\BaseResult;
use Serps\Core\Serp\IndexedResultSet;
use Serps\SearchEngine\Google\AdwordsResultType;
use Serps\SearchEngine\Google\Css;
use Serps\SearchEngine\Google\NaturalResultType;
use Serps\SearchEngine\Google\Page\GoogleDom;
use Serps\SearchEngine\Google\Parser\ParsingRuleInterace;

class Shopping implements ParsingRuleInterace
{

    public function match(GoogleDom $dom, \DOMElement $node)
    {
        $class = $node->getAttribute('class');

        if (strpos(' ' . $class . ' ', ' _oc ')) {
            return self::RULE_MATCH_MATCHED;
        }
        return self::RULE_MATCH_NOMATCH;
    }
    public function parse(GoogleDom $googleDOM, \DomElement $node, IndexedResultSet $resultSet)
    {
        $item = [
            'products' => function () use ($googleDOM, $node) {
                $items = [];
                $xpathCards = Css::toXPath('.pla-unit');
                $productNodes = $googleDOM->getXpath()->query($xpathCards, $node);
                foreach ($productNodes as $productNode) {
                    $items[] = $this->parseItem($googleDOM, $productNode);
                }
                return $items;
            }
        ];


        $resultSet->addItem(new BaseResult(AdwordsResultType::SHOPPING_GROUP, $item));
    }

    public function parseItem(GoogleDom $googleDOM, \DOMNode $node)
    {

        return new BaseResult(AdwordsResultType::SHOPPING_GROUP_PRODUCT, [
            'title' => function () use ($googleDOM, $node) {
                $aTag = $googleDOM->getXpath()->query(Css::toXPath('.pla-unit-title-link'), $node)->item(0);
                if (!$aTag) {
                    return null;
                }
                return $aTag->nodeValue;
            },
            'url' => function () use ($node, $googleDOM) {
                $aTag = $googleDOM->getXpath()->query(Css::toXPath('.pla-unit-title-link'), $node)->item(0);
                if (!$aTag) {
                    return $googleDOM->getUrl()->resolve('/');
                }
                return $googleDOM->getUrl()->resolve($aTag->getAttribute('href'), 'string');
            },
            'image' => function () use ($node, $googleDOM) {
                $imgTag = $googleDOM->getXpath()->query(
                    Css::toXPath('.pla-unit-img-container-link img'),
                    $node
                )->item(0);

                if (!$imgTag) {
                    return null;
                }
                return $imgTag->getAttribute('src');
            },
            'target' => function () use ($node, $googleDOM) {
                $aTag = $googleDOM->getXpath()->query(
                    Css::toXPath('div._mC span.a'),
                    $node
                )->item(0);

                if (!$aTag) {
                    return null;
                }
                return $aTag->nodeValue;
            },
            'price' => function () use ($node, $googleDOM) {
                $priceTag = $googleDOM->getXpath()->query(
                    Css::toXPath('._QD._pvi'),
                    $node
                )->item(0);

                if (!$priceTag) {
                    return null;
                }
                return $priceTag->nodeValue;
            }
        ]);
    }
}
