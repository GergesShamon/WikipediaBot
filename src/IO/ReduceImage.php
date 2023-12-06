<?php
namespace Bot\IO;

use Imagick;

class ReduceImage
{
    private Imagick $image;

    public function __construct($imageData)
    {
        $this->image = new Imagick();
        $this->image->readImageBlob($imageData);
    }

    public function reduce(int $maxDimension = 400, string $filename)
    {
        $originalWidth = $this->image->getImageWidth();
        $originalHeight = $this->image->getImageHeight();

        list($newWidth, $newHeight) = $this->calculateNewDimensions($originalWidth, $originalHeight, $maxDimension);

        $this->image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);

        // Save the modified image
        $this->image->writeImage(FOLDER_TMP . "/{$filename}");

        // Destroy the Imagick object
        $this->image->destroy();
    }

    private function calculateNewDimensions($originalWidth, $originalHeight, $maxDimension)
    {
        if ($originalWidth > $originalHeight) {
            $newWidth = $maxDimension;
            $newHeight = $originalHeight * ($maxDimension / $originalWidth);
        } else {
            $newHeight = $maxDimension;
            $newWidth = $originalWidth * ($maxDimension / $originalHeight);
        }

        return [$newWidth, $newHeight];
    }
}
?>
