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
    private function getMIMEType($content) : string {
        if ($content !== false) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_buffer($finfo, $content);
            finfo_close($finfo);
            return $mime_type;
        }
        return "";
    }
    private function checkMIMEType($mime_type) : bool {
        if (in_array($mime_type, array("image/jpeg", "image/png", "image/gif", "image/bmp", "image/webp"))) {
            return true;
        }
        return false;
    }
    public function ReduceImage(string $filename, int $width, int $height) : void {
        Logger::info("The bot reduces the file ${filename} size.");
        $ImageInfo = Util::getImageInfo($this->api, $filename, "url");

        $imageData = file_get_contents($ImageInfo["url"], false, stream_context_create([
            'http' => [
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            ],
        ]));
        $MIMEType = $this->getMIMEType($imageData);

        if ($this->checkMIMEType($MIMEType)) {
            $reducer = new ReduceImage($imageData);
            $reducer->reduce(400, $filename);
            $fileUploader = new \Bot\Service\FileUploader($this->api);
            if ($fileUploader->upload(
                $filename,
                fopen(FOLDER_TMP."/".$filename, "r"),
                "",
                "بوت: تصغير حجم الصور غير حرة",
                "preferences",
                true
            )) {
                Logger::info("File ${filename} uploaded successfully.");
            } else {
                Logger::info("The file ${filename} was not uploaded.");
            }
        } else {
            Logger::warning("The file ${filename} format ${MIMEType} is not supported.");
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
        } catch (Warning $warning) {
            Logger::warning("Task ReduceImages issued a warning.", [$warning->getMessage()]);
        }
    }
}