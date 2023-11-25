<?php
namespace Bot\Tasks;

use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Bot\IO\Util;
use Bot\IO\ReduceImage;
use Bot\IO\Logger;
use Exception;
class ReduceImages extends Task
{

    private function getImages() : array {
        return $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/Non-free_images.sql"));
    }

    public function ReduceImage(string $filename, int $width, int $height) : void {
        Logger::info("The bot reduces the file ${filename} size.");
        $ImageInfo = Util::getImageInfo($this->api, $filename, "url");
        $reducer = new ReduceImage($ImageInfo["url"]);
        $reducer->reduce(400, $filename);
        if ($this->services->newFileUploader()->upload(
            $filename,
            FOLDER_TMP."/".urldecode($filename),
            "",
            "بوت: تصغير حجم الصور غير حرة (تجربة)",
            "preferences",
            true
        )) {
            Logger::info("File ${filename} uploaded successfully.");
        } else {
            Logger::info("The file ${filename} was not uploaded.");
        }
    }
    public function removeFile($filename) : void {
        if (file_exists(FOLDER_TMP."/${filename}")) {
            // Check if the file exists before attempting to delete
            if (unlink(FOLDER_TMP."/${filename}")) {
                Logger::info("File ${filename} deleted successfully.");
            } else {
                Logger::info("Unable to delete the file ${filename}.");
            }
        } else {
            Logger::info("File ${filename} does not exist.");
        }

    }
    public function RUN() : void {
        try {
            $images = $this->getImages();
            $i = 0;
            foreach ($images as $image) {
                $this->ReduceImage($image["img_name"], $image["img_width"], $image["img_height"]);
                $this->removeFile($image["img_name"]);
                $i++;
            }
            Logger::info("Task ReduceImages succeeded to execute.");
        } catch (Exception $error) {
            Logger::fatal("Task ReduceImages failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            Logger::fatal("Task ReduceImages failed to execute.", $error->getApiResult());
        }
    }
}