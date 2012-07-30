<?php

namespace Uniplaces\BabelateBundle\Command;

// Message
use Uniplaces\BabelateBundle\Document\Message;
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
use Uniplaces\BabelateBundle\Yaml\Dumper;
// Doctrine/MongoDB
use Symfony\Component\HttpFoundation\Response;

class BabelateExportCommand extends ContainerAwareCommand
{   
    private function validLocale($locale)
    {
        $target_languages = $this->getContainer()->getParameter('babelate_target_languages');
        $main_language = $this->getContainer()->getParameter('babelate_main_language');
        if(strcmp($locale, $main_language) == 0) {
            return false;
        }
        foreach($target_languages as $language) {
            if(strcmp($locale, $language) == 0) {
                return true;
            }
        }
        return false;
    }
    
    protected function configure()
    {
        //
        $this
            ->setName('uniplaces:babelate:export')
            ->setDescription('Export localization data from mongoDB')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundles = $this->getContainer()->getParameter('babelate_bundles');
        $languages = $this->getContainer()->getParameter('babelate_target_languages');
        $domains = $this->getContainer()->getParameter('babelate_target_domains');
                
        foreach($bundles as $target_bundle => $dir) {
            foreach($domains as $target_domain) {
                foreach($languages as $target_locale) {
                    $filename = "$dir/Resources/translations/$target_domain.$target_locale.yml";
                    if (!$this->exportContents($filename, $target_bundle, $target_locale, $target_domain)) {
                        $output->writeln("<error>Target: $target_bundle.$target_domain.$target_locale does not exist in the database!</error>");
                    } else {
                        $output->writeln("<info>$filename exported Ok!</info>");
                    }
                }
            }
        }
    }
    
    /**
     *
     * @param type $filename
     * @param type $bundlename
     * @param type $locale
     * @param type $domain
     * @return boolean 
     */
    protected function exportContents($filename, $bundlename, $locale, $domain = "messages")
    {
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.translation_document_manager');
        $translation_entries = $dm->getRepository('UniplacesBabelateBundle:Translation')
                ->findBy(array('bundlename' => $bundlename,
                               'domain' => $domain));
        $messages_to_dump = array();
        foreach($translation_entries as $specific_message) {
            $message_collection = $specific_message->getMessageCollection();
            foreach ($message_collection as $specific_locale => $translated_message) {
                if (strcmp($specific_locale, $locale) == 0) {
                    $messages_to_dump[$specific_message->getTranslationKey()] = $translated_message;
                }
            }
        }
        ksort($messages_to_dump);
        if(count($messages_to_dump) > 0) {
            Dumper::dump($messages_to_dump, $filename);
            return true;
        } else {
            return false;
        }
    }
}

