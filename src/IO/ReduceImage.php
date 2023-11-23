<?php
namespace Bot\IO;

class ReduceImage
{
    private $imageUrl;
    public function __construct($imageUrl) {
        $this->imageUrl = $imageUrl;
    }

    public function reduce(int $maxDimension = 400, string $filename) : void {
        // Get the image data
        $imageData = file_get_contents($this->imageUrl, false, stream_context_create([
            'http' => [
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            ],
        ]));
        // Create a GD image from the data
        $originalImage = imagecreatefromstring($imageData);

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

        // Create a new blank GD image with the calculated dimensions
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Resize the original image to the new dimensions
        imagecopyresampled($resizedImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Output the resized image directly to the browser
        $originalImageInfo = getimagesizefromstring($imageData);
        $mime = $originalImageInfo["mime"];
        
        switch ($mime) {
            case "image/jpeg":
                imagejpeg($resizedImage, FOLDER_TMP."/${filename}", 100);
                break;
            case "image/png":
                imagepng($resizedImage, FOLDER_TMP."/${filename}", 100);
                break;
            case "image/gif":
                imagegif($resizedImage, FOLDER_TMP."/${filename}", 100);
                break;
            case "image/bmp":
                imagewbmp($resizedImage, FOLDER_TMP."/${filename}", 100);
                break;
            case "image/webp":
                imagewebp($resizedImage, FOLDER_TMP."/${filename}", 100);
                break;
        }
        // Clean up resources
        imagedestroy($originalImage);
        imagedestroy($resizedImage);
    }
}
?>