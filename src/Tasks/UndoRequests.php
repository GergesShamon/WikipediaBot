<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\Client\Action\Request\ActionRequest;
use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Exception;


class UndoRequests extends Task
{
    private function getEditsUser1(string $username): array {
        return $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/getEditsUser1.sql", [
            "Name" => $username
        ]));
    }
    private function getEditsUser2(string $username, $from, $to): array {
        return $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/getEditsUser2.sql", [
            "Name" => $username,
            "From" => $from,
            "To" => $to
        ]));
    }
    private function getRequests(string $text): array {
        $lines = explode("\n", $text);
        $requests = [];
        foreach ($lines as $line) {
            $match = @preg_match_all("/\{\{(.+?)\|([^|]+)\|([^|]+)\|([^|]+)\}\}/", $line, $matches);
            if ($match === false) {
                $error = preg_last_error();
                if ($error == PREG_INTERNAL_ERROR) {
                    $this->log->error("Invalid regular expression.", [ $error ]);
                }
            } elseif ($match) {
                $requests[] = [
                  "username" => $matches[2][0],
                  "from" => intval($matches[3][0]),
                  "to" => intval($matches[4][0]),
                ];
            }
        }
           return $requests;
    }
    private function userCheck(string $username): bool {
        $user = $this->services->newUserGetter()->getFromUsername($username);
        return in_array("editor", $user->getGroups());
    }
    private function revertedCheck($revid): bool {
        $result = $this->api->request(
			ActionRequest::simplePost( "query", [
			    "prop" => "revisions",
			    "revids" => $revid,
			    "rvprop" =>"tags"
			])
		);
		$tags = reset($result["query"]["pages"])["revisions"][0]["tags"];
		return in_array("mw-reverted", $tags);
    }
    private function moveRequestToTalk(string $input): void {
        $page = $this->services->newPageGetter()->getFromTitle("نقاش ويكيبيديا:طلبات استرجاع التخريب الكمي");
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $content = new Content(str_replace("{{/ترويسة}}", "", $input));
        $revision = new Revision($content, $page->getPageIdentifier());
        $editInfo = new EditInfo("بوت: طلب", true,  true);
        $this->services->newRevisionSaver()->save($revision, $editInfo);
        
    }
    private function init(): void {
        $page = $this->services->newPageGetter()->getFromTitle("ويكيبيديا:طلبات استرجاع التخريب الكمي");
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $requests = $this->getRequests($text);
        if(!empty($requests)){
            $latestUser = $page->getRevisions()->getLatest()->getUser();
            if($this->userCheck($latestUser)){
                $content = new Content("{{/ترويسة}}");
                $revision = new Revision($content, $page->getPageIdentifier());
                $editInfo = new EditInfo("بوت: جاري تنفيذ...", true,  true);
                $this->services->newRevisionSaver()->save($revision, $editInfo);
                foreach ($requests as $request){
                    $username = $request["username"];
                    if($request["from"] == 0){
                        $edits = $this->getEditsUser1($username);
                    } else {
                        $edits = $this->getEditsUser1($username, $request["from"], $request["to"]);
                    }
                    foreach ($edits as $edit){
                        if(!$this->revertedCheck($edit["rev_undo"])){
                            $_page = $this->services->newPageGetter()->getFromPageId($edit["page_id"]);
                            $pagetitle = $_page->getPageIdentifier()->getTitle()->getText();
                            $undoafter = $edit["rev_undoafter"];
                            if($this->services->newRevisionUndoer()->undo($_page->getRevisions()->getLatest(), new EditInfo("بوت استرجاع تخريب كمي: استرجاع تعديلات ${username}", true,  true), $undoafter)){
                                $this->log->info("The ${username} user edits on ${pagetitle} have been undone.");
                            }
                        }
                    }
                }
            } else {
                $content = new Content("{{/ترويسة}}");
                $revision = new Revision($content, $page->getPageIdentifier());
                $editInfo = new EditInfo("بوت: نقل الطلب إلى نقاش لأن مُقدم الطلب لا يحمل صلاحيات محرر", true,  true);
                $this->services->newRevisionSaver()->save($revision, $editInfo);
                $this->moveRequestToTalk($text);
            }
        }
    }
    public function RUN(): void {
        try {
            $this->init();
            $this->log->info("Task UndoRequests succeeded to execute.");
        } catch (Exception $error) {
            $this->log->debug("Task UndoRequests failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            $this->log->debug("Task UndoRequests failed to execute.", $error->getApiResult());
        }
    }
}