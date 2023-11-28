<?php
namespace Bot\IO;

class ReduceImage
{
    private $imageData;
    public function __construct($imageData) {
        $this->imageData = $imageData;
    }

    public function reduce(int $maxDimension = 400, string $filename) {
        // Create a GD image from the data
        $originalImage = imagecreatefromstring($this->imageData);

        // Get the original dimensions
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

        // Calculate the new dimensions while maintaining the aspect ratio
        if ($originalWidth > $originalHeight) {
            $newWidth = $maxDimension;
            $newHeight = $originalHeight * ($maxDimension / $originalWidth);
        } else {
            $newHeight = $maxDimension;
            $newWidth = $originalWidth * ($maxDimension / $originalHeight);
        }

        // Create a new blank GD image with the calculated dimensions and alpha channel
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Enable alpha blending
        imagesavealpha($resizedImage, true);

        // Fill the background with a transparent color
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefill($resizedImage, 0, 0, $transparent);

        // Resize the original image to the new dimensions
        imagecopyresampled($resizedImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);


        // Output the resized image directly to the browser
        $originalImageInfo = getimagesizefromstring($this->imageData);
        $mime = $originalImageInfo["mime"];

        switch ($mime) {
            case "image/jpeg":
                imagejpeg($resizedImage, FOLDER_TMP."/${filename}");
                break;
            case "image/png":
                imagepng($resizedImage, FOLDER_TMP."/${filename}");
                break;
            case "image/gif":
                imagegif($resizedImage, FOLDER_TMP."/${filename}");
                break;
            case "image/bmp":
                imagewbmp($resizedImage, FOLDER_TMP."/${filename}");
                break;
            case "image/webp":
                imagewebp($resizedImage, FOLDER_TMP."/${filename}");
                break;
        }
        // Clean up resources
        imagedestroy($originalImage);
        imagedestroy($resizedImage);
    }
}
?>