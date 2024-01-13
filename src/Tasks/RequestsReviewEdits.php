<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Exception;


class RequestsReviewEdits extends Task
{
    private $pattern1 = "/\{\{وضع طلب\|(.+?)\}\}/";
    private $pattern2 = "/مراجع الطلب =(.*)\n\}\}/s";
    private $pattern3 = "/سبب الرفض =(.*)/";
    private function getRedLinks() : array {
        return array_column($this->query->getArray(Util::ReadFile(FOLDER_SQL . "/RedPages.sql", [
            "Name" => "طلبات_مراجعة_التعديلات",
            "FromNamespace" => 4,
            "Namespace" => 0
        ])), "page_title");
    
    }
    private function getAcceptedPages() : array {
        return array_column($this->query->getArray(Util::ReadFile(FOLDER_SQL . "/AcceptedPagesOnReviewEdits.sql")), "page_title");
    }
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
    private function PagesNotFound(string $text) : string {
        return preg_replace($this->pattern1, "{{وضع طلب|0}}", preg_replace($this->pattern2, "مراجع الطلب = {{فاصل}}--~~~~ \n}}", preg_replace($this->pattern3, "سبب الرفض = {{لمذ}}، صفحة غير موجودة.", $text, 1), 1), 1);
    }
    private function AcceptablePages(string $text) : string {
        return preg_replace($this->pattern1, "{{وضع طلب|1}}", preg_replace($this->pattern2, "مراجع الطلب =$1\n {{تم|رُوجِعت التعديلات}} --~~~~ \n}}", $text, 1), 1);
    }
    
    private function init(){
        $page = $this->services->newPageGetter()->getFromTitle("ويكيبيديا:طلبات مراجعة التعديلات");
        $sections = $this->getSections($page);
        $redLinks = $this->getRedLinks();
        $acceptedPages = $this->getAcceptedPages();
        $sectionsR = [];
        $i = 0;
        foreach ($sections as $section) {
            $wikitext = $this->getSection($page, $section["index"]);
            if(strpos($wikitext, "{{وضع طلب|انتظار}}")) {
                if (in_array($section["line"], $redLinks)){
                    $sectionsR[$i] = [];
                    $sectionsR[$i]["original"] = $wikitext;
                    $sectionsR[$i]["replace"] = $this->PagesNotFound($wikitext);
                    $i++;
                }
                if (in_array($section["line"], $acceptedPages)){
                    $sectionsR[$i] = [];
                    $sectionsR[$i]["original"] = $wikitext;
                    $sectionsR[$i]["replace"] = $this->AcceptablePages($wikitext);
                    $i++;
                }
            }
        }
       
        $newWikitext = array_reduce($sectionsR, function($text, $section) {
            return str_replace($section["original"], $section["replace"], $text);
        }, $page->getRevisions()->getLatest()->getContent()->getData());

        $revision = new Revision(new Content($newWikitext),$page->getPageIdentifier());
        $editInfo = new EditInfo("بوت: طلبات مُنجزة", true,  true);
        $this->services->newRevisionSaver()->save($revision, $editInfo);
    }
    public function RUN(): void {
        try {
            $this->init();
            $this->log->info("Task RequestsReviewEdits succeeded to execute.");
        } catch (Exception $error) {
            $this->log->debug("Task RequestsReviewEdits failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            $this->log->debug("Task RequestsReviewEdits failed to execute.", $error->getApiResult());
        }
    }
}