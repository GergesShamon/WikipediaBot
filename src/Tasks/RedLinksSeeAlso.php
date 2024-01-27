<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Exception;


class RedLinksSeeAlso extends Task
{
    private function getSections(Page $page) : array {
        return $this->services->newParser()->parsePage($page->getPageIdentifier(), [
            "prop" => "sections"
        ])["sections"];
    }
     private function getSection(Page $page, $index) : string {
        return $this->services->newParser()->parsePage($page->getPageIdentifier(), [
            "prop" => "wikitext",
            "section" => $index
        ])["wikitext"]["*"];
    }

    private function Remove(string $text, string $link): string {
        $patterns = [
            "/(\n?.*?\[\[".preg_quote($link)."\]\](.*$)?)/um",
            "/(\n?.*?\[\[".preg_quote($link)."\|.*\]\](.*$)?)/um"
        ];
        $output = $text;
        foreach ($patterns as $pattern){
            if (preg_match($pattern, $text, $matches)) {
                $output = preg_replace($pattern, "", $output);
            }
        }
        return $output;
    }
    private function RunRemover(string $name): void {
        $links = $this->services->newPageListGetter()->getLinksFromHere($name);
        $page = $this->services->newPageGetter()->getFromTitle($name);
        $redLinks = [];
        foreach ($links->toArray() as $link){
            if ($link->getPageIdentifier()->getId() <= 0) {
                $redLinks[] = $link->getPageIdentifier()->getTitle()->getText();
            }
        }
        if(!empty($redLinks)){
            $sections = $this->getSections($page);
            foreach ($sections as $section){
                if (preg_match("/[آإاأ]نظر [آإاأ]يضا/u", preg_replace("/[\x{0617}-\x{061A}\x{064B}-\x{0652}]/u", "", $section["line"]), $matches)) {
                    $text = $this->getSection($page, $section["index"]);
                    foreach ($redLinks as $redLink){
                        $textR = $this->Remove($text, $redLink);
                    }
                    if ($text != $textR){
                        $_text = $page->getRevisions()->getLatest()->getContent()->getData();
                        $editInfo = new EditInfo("بوت: إزالة وصلات مكسورة من انظر أيضا (تجربة)", true,  true);
                        $content = new Content(str_replace($text, $textR, $_text));
                        $revision = new Revision($content, $page->getPageIdentifier());
                        $this->services->newRevisionSaver()->save($revision, $editInfo);
                        $this->log->info("Broken links have been removed from the page ${name}");
                        break;
                    }
                }
            }
        }
    }
    private function init(){
        $OFFSET = 0;
        while (true){
            $query = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/getPages_GrammarlyRepair.sql", [
                "LIMIT" => 1000,
                "OFFSET" => $OFFSET
            ]));
            if (empty($query)){
                break;
            }
            $pages = array_column($query, "page_title");
            foreach ($pages as $page) {
                $this->RunRemover($page);
            }
            sleep(1);
            $OFFSET=+1000;
        }
    }
    public function RUN(): void {
        $this->running(function(){
            $this->init();
        });
    }
}