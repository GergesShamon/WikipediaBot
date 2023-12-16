<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Exception;


class RemoveMissingFiles extends Task
{
    
    private function Remove(string $text, string $image): string {
        $patterns = [
            "/(\n?\[\[.*".preg_quote($image).".*?\]\])/u",
            "/(\n?.*=.*".preg_quote($image).".*?)/u"
        ];
        $output = $text;
        foreach ($patterns as $pattern){
            if (preg_match($pattern, $text, $matches)) {
                $this->log->info("The ${image} file is removed");
                $output = preg_replace($pattern, "",$output);
            }
        }
        return $output;
    }
    private function RunRemover(string $name, string $imagesP): void {
        $run = false;
        $page = $this->services->newPageGetter()->getFromTitle($name);
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $reformedText = $text;
        $chunks = array_chunk(explode("#,#", $imagesP), 30);
        foreach ($chunks as $chunk){
            $images = $this->services->newImageInfo()->get($chunk);
            foreach($images as $image){
                if (!isset($image["imageinfo"]) && empty($image["imageinfo"]) && !array_key_exists("imageinfo", $image)) {
                    $reformedText = $this->Remove($text, explode(":",$image["title"])[1]);
                }
            }
        }
        if ($text != $reformedText) {
            $content = new Content($reformedText);
            $editInfo = new EditInfo("بوت: إزالة ملفات معطوبة (تجربة)");
            $revision = new Revision($content, $page->getPageIdentifier());
            $this->services->newRevisionSaver()->save($revision, $editInfo);
            $this->log->info("The bot removed missing files on a page ${name}.");
        }
    }
    private function init(){
        $OFFSET = 1;
        while (true){
            $query = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/PagesWithMissingFiles.sql", [
                "LIMIT" => 100,
                "OFFSET" => $OFFSET
            ]));
            if (empty($query)){
                break;
            }
            foreach ($query as $page) {
                $this->RunRemover($page["page_title"], $page["linked_images"]);
            }
            $OFFSET=+100;
        }
    }
    public function RUN(): void {
        try {
            $this->init();
            $this->log->info("Task RemoveMissingFiles succeeded to execute.");
        } catch (Exception $error) {
            $this->log->debug("Task RemoveMissingFiles failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            $this->log->debug("Task RemoveMissingFiles failed to execute.", $error->getApiResult());
        }
    }
}