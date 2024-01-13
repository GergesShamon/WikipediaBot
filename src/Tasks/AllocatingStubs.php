<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\MediawikiFactory;
use WikiConnect\MediawikiApi\Client\Action\ActionApi;
use WikiConnect\MediawikiApi\Client\Action\Request\ActionRequest;
use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Bot\IO\QueryDB;
use Bot\Model\Stubs as ModelStubs;
use Exception;
use mysqli;

class AllocatingStubs extends Task
{
    private ModelStubs $model;
    public function __construct(ActionApi $api, MediawikiFactory $services, mysqli $mysqli) {
        $this->model = new ModelStubs();
        parent::__construct($api, $services, $mysqli);
    }
    private function getPages(): array {
        return $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/AllocatingStubs.sql"));
    }
    public function allocating(array $data): void {
        $chunks = array_chunk($data, 20);
        foreach ($chunks as $chunk){
            $pages = $this->model->predict($chunk);
            foreach ($pages as $page){
                $_probability = $page["probability"];
                $title = $page["title"];
                $stub = $page["stub"];
                $probability = intval($_probability * 1000);
                if($probability > 995){
                    $this->addStub($title, $stub);
                    $this->log->info("$stub $_probability prediction for a $title");
                }
            }
        }
    }
    private function addStub(string $title, string $stub): void {
        $page = $this->services->newPageGetter()->getFromTitle($title);
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $content = new Content(str_replace("{{بذرة}}", "{{".$stub."}}", $text));
        $revision = new Revision($content, $page->getPageIdentifier());
        $editInfo = new EditInfo("بوت: تخصيص بذرة", true,  true);
        $this->services->newRevisionSaver()->save($revision, $editInfo);
    }
    public function RUN(): void {
        try {
            $pages = $this->getPages();
            $this->allocating($pages);
            $this->log->info("Task RequestsReviewEdits succeeded to execute.");
        } catch (Exception $error) {
            $this->log->debug("Task RequestsReviewEdits failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            $this->log->debug("Task RequestsReviewEdits failed to execute.", $error->getApiResult());
        }
    }
}