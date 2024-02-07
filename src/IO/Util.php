<?php
namespace Bot\IO;

use WikiConnect\MediawikiApi\Client\Action\ActionApi;
use WikiConnect\MediawikiApi\Client\Action\Request\ActionRequest;

class Util
{
    public static function ReadFile(string $File, array $Array = array()) {
        $FileStream = file_get_contents($File);
        foreach ($Array as $key => $value) {
            $FileStream = str_replace("{{" . $key . "}}", $value, $FileStream);
        }
        return $FileStream;
    }
    public static function getImageInfo(ActionApi $api, string $filename, string $iiprop = null): array | false {
        $query = [
            "format" => "json",
            "titles" => "File:${filename}",
            "prop" => "imageinfo"
        ];

        if ($iiprop != null) {
            $query["iiprop"] = $iiprop;
        }
        $data = $api->request(ActionRequest::simpleGet("query", $query));
        if (isset($data["query"]["pages"]) && !empty($data["query"]["pages"])) {
            $firstPageKey = array_key_first($data["query"]["pages"]);
            $page = $data["query"]["pages"][$firstPageKey];
            if (isset($page["imageinfo"]) && isset($page["imageinfo"][0])) {
                if ($iiprop !== null) {
                    $page["imageinfo"][0]["iiprop"] = $iiprop;
                }
                return $page["imageinfo"][0];
            }
        }
        return false;
    }
    public static function getYearMonth(): string {
        $months = array(
            "January" => "يناير",
            "February" => "فبراير",
            "March" => "مارس",
            "April" => "أبريل",
            "May" => "مايو",
            "June" => "يونيو",
            "July" => "يوليو",
            "August" => "أغسطس",
            "September" => "سبتمبر",
            "October" => "أكتوبر",
            "November" => "نوفمبر",
            "December" => "ديسمبر"
        );
        return $months[strftime("%B")] . " " . strftime("%Y");
    }
    public static function PregReplace($Text, $Array = array()): string {
        foreach ($Array as $Row) {
            $Text = preg_replace($Row[0], $Row[1], $Text);
        }
        return $Text;
    }
    public static function calculateDaysFromToday($targetDate) {
        $months = array(
            "يناير" => "January",
            "فبراير" => "February",
            "مارس" => "March",
            "أبريل" => "April",
            "مايو" => "May",
            "يونيو" => "June",
            "يوليو" => "July",
            "أغسطس" => "August",
            "سبتمبر" => "September",
            "أكتوبر" => "October",
            "نوفمبر" => "November",
            "ديسمبر" => "December"
        );
        $today = new DateTime();
        $targetDateTime = new DateTime(str_replace(array_keys($months), array_values($months), $targetDate));

        $interval = $today->diff($targetDateTime);

        return $interval->d;
    }

}