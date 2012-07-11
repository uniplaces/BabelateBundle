<?php

namespace Uniplaces\BabelateBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="translations")
 */
class Translation
{
    /**
     * @ODM\Id 
     */
    private $id;
    
    /**
     * @ODM\String
     */
    protected $translation_key;
    
    /**
     * @ODM\String
     */
    protected $domain;
    
    /**
     * @ODM\String 
     */
    protected $bundlename;
    
    /**
     * @ODM\Hash 
     */
    protected $message_collection;
    

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set translation_key
     *
     * @param string $translationKey
     * @return Message
     */
    public function setTranslationKey($translationKey)
    {
        $this->translation_key = $translationKey;
        return $this;
    }

    /**
     * Get translation_key
     *
     * @return string $translationKey
     */
    public function getTranslationKey()
    {
        return $this->translation_key;
    }

    /**
     * Set domain
     *
     * @param string $domain
     * @return Message
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get domain
     *
     * @return string $domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set bundlename
     *
     * @param string $bundlename
     * @return Message
     */
    public function setBundlename($bundlename)
    {
        $this->bundlename = $bundlename;
        return $this;
    }

    /**
     * Get bundlename
     *
     * @return string $bundlename
     */
    public function getBundlename()
    {
        return $this->bundlename;
    }

    /**
     * Set message_collection
     *
     * @param hash $messageCollection
     * @return Message
     */
    public function setMessageCollection($messageCollection)
    {
        $this->message_collection = $messageCollection;
        return $this;
    }

    /**
     * Get message_collection
     *
     * @return hash $messageCollection
     */
    public function getMessageCollection()
    {
        return $this->message_collection;
    }
}
