<?php

namespace Uniplaces\BabelateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Uniplaces\BabelateBundle\Document\Translation;


class BabelateController extends Controller
{
    
    public function indexAction()
    {
        return $this->forward('UniplacesBabelateBundle:Babelate:showBundles');
    }
    
    public function showBundlesAction()
    {
        $bundles = $this->container->getParameter('babelate_bundles');
        asort($bundles);
        return $this->render('UniplacesBabelateBundle:Babelate:show_bundles.html.twig', array('bundles' => $bundles));
    }
    
    public function showDomainsAction($bundle)
    {
        $target_bundles = $this->container->getParameter('babelate_bundles');
        $target_languages = $this->container->getParameter('babelate_target_languages');
        $target_domains = $this->container->getParameter('babelate_target_domains');
        
        if(!isset($target_bundles[$bundle])) {
            return $this->forward('UniplacesBabelateBundle:Babelate:showBundles');
        }
        
        $entries = $this->get('doctrine.odm.mongodb.translation_document_manager')
                ->getRepository('UniplacesBabelateBundle:Translation')
                ->findBy(array('bundlename' => $bundle));
        $domains = array();
        
        $missing_entries = array();
        foreach($target_domains as $target_domain) {
            $missing_entries[$target_domain] = 0;
        }
        
        foreach($entries as $specific_entry) {
            if(!in_array($specific_entry->getDomain(), $domains)) {
                array_push($domains, $specific_entry->getDomain());
            }
            //$message_collection = $specific_entry->getMessageCollection();
            /*foreach($target_languages as $target_locale) {
                if(!isset($message_collection[$target_locale])) {
                    $missing_entries[$specific_entry->getDomain()]++;
                }
            }*/
            //$missing_entries[$specific_entry->getDomain()] += count($target_languages) - count($specific_entry->getMessageCollection()) + 1;
        }
        // Sort by value
        asort($domains);
        //ksort($missing_entry);
        return $this->render('UniplacesBabelateBundle:Babelate:show_domains.html.twig', array('bundlename' => $bundle, 'domains' => $domains));
    }
    
    public function showKeysAction($bundle, $domain)
    {
        $target_bundles = $this->container->getParameter('babelate_bundles');
        if(!isset($target_bundles[$bundle]) ||
           !in_array($domain, $this->container->getParameter('babelate_target_domains'))) {
            return $this->forward('UniplacesBabelateBundle:Babelate:showBundles');
        }
        
        $entries = $this->get('doctrine.odm.mongodb.translation_document_manager')
                ->getRepository('UniplacesBabelateBundle:Translation')
                ->findBy(array('bundlename' => $bundle, 'domain' => $domain));
        $babelate_languages = $this->container->getParameter('babelate_target_languages');
        array_push($babelate_languages, $this->container->getParameter('babelate_main_language'));
        // For a bundle we want to show each translation_key and which languages it has a translation for
        $processed_entries = array();
        foreach($entries as $specific_entry) {
            //$processed_entries[$specific_entry->getTranslationKey()];
            $languages = array();
            foreach($specific_entry->getMessageCollection() as $locale => $translation) {
                array_push($languages, $locale);
            }
            $processed_entries[$specific_entry->getTranslationKey()] = array_diff($babelate_languages, $languages);
        }
        $target_locales = $this->container->getParameter('babelate_target_languages');
        // Sort by key (not value).
        ksort($processed_entries);
        return $this->render('UniplacesBabelateBundle:Babelate:show_keys.html.twig', array('bundlename' => $bundle, 'domain' => $domain, 'target_locales' => $target_locales, 'entries' => $processed_entries));
    }
    
    public function showKeysMissingLocaleAction($bundle, $domain, $locale)
    {
        $target_bundles = $this->container->getParameter('babelate_bundles');
        if(!isset($target_bundles[$bundle]) ||
           !in_array($domain, $this->container->getParameter('babelate_target_domains')) ||
           !in_array($locale, $this->container->getParameter('babelate_target_languages'))) {
            return $this->forward('UniplacesBabelateBundle:Babelate:showBundles');
        }
        
        $entries = $this->get('doctrine.odm.mongodb.translation_document_manager')
                ->getRepository('UniplacesBabelateBundle:Translation')
                ->findBy(array('bundlename' => $bundle, 'domain' => $domain));
        
        $keys_to_show = array();
        foreach($entries as $specific_entry) {
            $message_collection = $specific_entry->getMessageCollection();
            if(!isset($message_collection[$locale])) {
                array_push($keys_to_show, $specific_entry->getTranslationKey());
            }
        }
        asort($keys_to_show);
        return $this->render('UniplacesBabelateBundle:Babelate:show_keys_missing_locale.html.twig', array('bundlename' => $bundle, 'domain' => $domain, 'locale' => $locale, 'keys' => $keys_to_show));
    }
    
    public function showKeyAction($bundle, $domain, $key)
    {
        $target_bundles = $this->container->getParameter('babelate_bundles');
        if(!isset($target_bundles[$bundle]) ||
           !in_array($domain, $this->container->getParameter('babelate_target_domains'))) {
            return $this->forward('UniplacesBabelateBundle:Babelate:showBundles');
        }
        
        $entry_to_edit = $this->get('doctrine.odm.mongodb.translation_document_manager')
                ->getRepository('UniplacesBabelateBundle:Translation')
                ->findOneBy(array('bundlename' => $bundle, 'domain' => $domain, 'translation_key' => $key));
        // Always has to be in the same order
        // So, first to appear will be the main language (un-edittable)
        // Next the remaining languages, sorted alphabetically
        $message_collection = $entry_to_edit->getMessageCollection();
        $main_language = $this->container->getParameter('babelate_main_language');
        $target_languages = $this->container->getParameter('babelate_target_languages');
        $main_message = $message_collection[$main_language];
        unset($message_collection[$main_language]);
        // Need to add in the array all the languages
        foreach($target_languages as $target_locale) {
            if(!isset($message_collection[$target_locale])) {
                $message_collection[$target_locale] = '';
            }
        }
        
        ksort($message_collection);
        return $this->render('UniplacesBabelateBundle:Babelate:show_key.html.twig', array('bundlename' => $bundle, 'domain' => $domain, 'key' => $key, 'main_language' => $main_language, 'main_message' => $main_message, 'message_collection' => $message_collection));
    }
    
    public function updateKeyAction(Request $request, $bundle, $domain, $key)
    {
        $target_bundles = $this->container->getParameter('babelate_bundles');
        if(!isset($target_bundles[$bundle]) ||
           !in_array($domain, $this->container->getParameter('babelate_target_domains'))) {
            return $this->forward('UniplacesBabelateBundle:Babelate:showBundles');
        }
        
        $dm = $this->get('doctrine.odm.mongodb.translation_document_manager');
        $entry_to_update = $dm->getRepository('UniplacesBabelateBundle:Translation')
                              ->findOneBy(array('bundlename' => $bundle, 'domain' => $domain, 'translation_key' => $key));
        $target_languages = $this->container->getParameter('babelate_target_languages');
        $message_collection = $entry_to_update->getMessageCollection();
        foreach($target_languages as $target_locale) {
            $new_translation = $request->get($target_locale, null);
            if((strcmp($new_translation, '') == 0) && isset($message_collection[$target_locale])) {
                unset($message_collection[$target_locale]);
            } else if(!is_null($new_translation)) {
                $message_collection[$target_locale] = $new_translation;
            }
        }
        //file_put_contents('/tmp/checkme', $new_translation);
        $entry_to_update->setMessageCollection($message_collection);
        $dm->flush();
        
        return $this->forward('UniplacesBabelateBundle:Babelate:showKey', array('bundle' => $bundle, 'domain' => $domain, 'key' => $key));
    }
    
    //$json = json_decode(file_get_contents('https://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=' . urlencode($text) . '&langpair=' . $from_lan . '|' . $to_lan));
    //$translated_text = $json->responseData->translatedText;
}
