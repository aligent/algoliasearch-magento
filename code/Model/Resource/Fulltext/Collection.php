<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
    /**
     * Intercept query
     */
    public function addSearchFilter($query)
    {
        $storeId = Mage::app()->getStore()->getId();
        $config = Mage::helper('algoliasearch/config');

        if ($config->isEnabledFrontEnd($storeId) === false)
            return parent::addSearchFilter($query);

        $data = array();

        // This method of filtering the product collection by the search result does not use the catalogsearch_result table
        try {
            if ($config->isInstantEnabled($storeId) === false || $config->makeSeoRequest($storeId))
            {
                $algolia_query = $query !== '__empty__' ? $query : '';
                $data = Mage::helper('algoliasearch')->getSearchResult($algolia_query, $storeId);
            }

        } catch (Exception $e) {
            Mage::getSingleton('catalog/session')->addError(Mage::helper('algoliasearch')->__('Search failed. Please try again.'));
            $this->getSelect()->columns(array('relevance' => new Zend_Db_Expr("e.entity_id")));
            $this->getSelect()->where('e.entity_id = 0');
            return $this;
        }

        $sortedIds = array_reverse(array_keys($data));

        $this->getSelect()->columns(array('relevance' => new Zend_Db_Expr("FIND_IN_SET(e.entity_id, '".implode(',',$sortedIds)."')")));
        $this->getSelect()->where('e.entity_id IN (?)', $sortedIds);

        return $this;
    }
}
