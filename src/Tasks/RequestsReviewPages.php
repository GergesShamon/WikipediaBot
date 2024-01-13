<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Exception;


class RequestsReviewPages extends Task
{
    private $pattern1 = "/\{\{وضع طلب\|(.+?)\}\}/";
    private $pattern2 = "/مراجع الطلب =(.*)\n\}\}/s";
    private $pattern3 = "/سبب الرفض =(.*)/";
    private function getRedLinks() : array {
        return array_column($this->query->getArray(Util::ReadFile(FOLDER_SQL . "/RedPages.sql", [
            "Name" => "طلبات_مراجعة_المقالات",
            "FromNamespace" => 4,
            "Namespace" => 0
        ])), "page_title");
    
    }
    private function getAcceptedPages() : array {
        return $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/AcceptedPagesOnReviewPages.sql"));
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
    private function DuplicateRequest(string $text) : string {
        return preg_replace($this->pattern1, "{{وضع طلب|0}}", preg_replace($this->pattern2, "مراجع الطلب = {{فاصل}}--~~~~ \n}}", preg_replace($this->pattern3, "سبب الرفض = {{لمذ}}، طلب مكرر.", $text, 1), 1), 1);
    }
    private function AcceptablePages(string $text, string $user_reviewed) : string {
        return preg_replace($this->pattern1, "{{وضع طلب|1}}", preg_replace($this->pattern2, "مراجع الطلب =$1\n {{تم}}، راجع{{ذث||ت||مستخدم=".$user_reviewed."}}ها الزميل{{ذث||ة||مستخدم=".$user_reviewed."}} ".$user_reviewed." {{فاصل}}--~~~~ \n}}", $text, 1), 1);
    }
    
    private function init(){
        $page = $this->services->newPageGetter()->getFromTitle("ويكيبيديا:طلبات مراجعة المقالات");
        $sections = $this->getSections($page);
        $redLinks = $this->getRedLinks();
        $acceptedPages = $this->getAcceptedPages();
        $lines = array_count_values(array_column($sections, "line"));
        $sectionsR = [];
        $duplicateRequests = [];
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
                foreach ($acceptedPages as $acceptedPage){
                    if ($section["line"] == $acceptedPage["page_title"]){
                        $sectionsR[$i] = [];
                        $sectionsR[$i]["original"] = $wikitext;
                        $sectionsR[$i]["replace"] = $this->AcceptablePages($wikitext, $acceptedPage["user_reviewed"]);
                        $i++;
                    }
                }
                if ($lines[$section["line"]] > 1 ){
                    if (in_array($section["line"],$duplicateRequests)){
                        $sectionsR[$i] = [];
                        $sectionsR[$i]["original"] = $wikitext;
                        $sectionsR[$i]["replace"] = $this->DuplicateRequest($wikitext);
                        $i++;
                    } else {
                        $duplicateRequests[] = $section["line"];
                    }
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