<?php

namespace Uniplaces\BabelateBundle\Command;

// Message
use Uniplaces\BabelateBundle\Document\Translation;
// Command stuff
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
// Yaml...
//use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Loader\YamlFileLoader;
// Doctrine/MongoDB
use Symfony\Component\HttpFoundation\Response;

class BabelateImportCommand extends ContainerAwareCommand
{   
    protected function configure()
    {
        //
        $this
            ->setName('uniplaces:babelate:import')
            ->setDescription('Import localization data into mongoDB')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.translation_document_manager');
        
        $bundles = $this->getContainer()->getParameter('babelate_bundles');
        $target_languages = $this->getContainer()->getParameter('babelate_target_languages');
        $main_language = $this->getContainer()->getParameter('babelate_main_language');
        $target_domains = $this->getContainer()->getParameter('babelate_target_domains');
        $clear_db = $this->getContainer()->getParameter('babelate_clear_db');
        
        if($clear_db) {
            $all_msgs = $dm->getRepository("UniplacesBabelateBundle:Translation")->findAll();
            foreach($all_msgs as $msg) {
                $dm->remove($msg);
            }
            $dm->flush();
        }
        array_push($target_languages, $main_language);
        foreach($bundles as $bundlename => $dir) {
            foreach($target_languages as $locale ) {
                foreach($target_domains as $domain) {
                    $filename = "$dir/Resources/translations/$domain.$locale.yml";
                    if(file_exists($filename)) {
                        $this->importContents($filename, $locale, $bundlename, $domain);
                    }
                }
            }
        }
    }
    
    /**
     * Import a localization yaml file into mongoDB
     * 
     * @param type $filename
     * @param type $locale
     * @param type $bundlename
     * @param type $domain
     */
    protected function importContents($filename, $locale, $bundlename, $domain = 'messages')
    {
        // Test if file has been imported ?
        // Got a file to import
        $loader = new YamlFileLoader();
        $locale_file_contents = $loader->load($filename, $locale, $domain);
        $repository = $locale_file_contents->all();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.translation_document_manager');
        
        // Import all the messages for this domain into the document manager
        foreach ($repository[$domain] as $translation_key => $translation) {
            // Check if it's already in?
            $translation_entry = $dm->getRepository('UniplacesBabelateBundle:Translation')
                    ->findOneBy(array('translation_key' => $translation_key,
                        'domain' => $domain,
                        'bundlename' => $bundlename));
            // Escape escaped double quotes (from our exportContents)
            $translation = str_replace('\"', '"', $translation);
            if (!empty($translation_entry)) {
                $msgs = $translation_entry->getMessageCollection();
                //$msgs = $msgs + array($locale => $translation);
                if(!isset($msgs[$locale])) {
                    $msgs[$locale] = $translation;
                    $translation_entry->setMessageCollection($msgs);
                }
                //print_r($msgs);
                
            } else {
                //print_r("Inserting for $translation_key\n");
                $entry = new Translation();
                $entry->setTranslationKey($translation_key);
                $entry->setDomain($domain);
                $entry->setBundlename($bundlename);
                $entry->setMessageCollection(array($locale => $translation));
                $dm->persist($entry);
            }
        }
        
        $dm->flush();
    }
}
